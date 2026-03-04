<?php
define('BASE_DIR', '/var/www/html');

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function startSSE(): void {
    while (ob_get_level()) ob_end_clean();
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');
}

function sendSSEEvent(array $data): void {
    echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    ob_flush();
    flush();
}

function stripAnsi(string $text): string {
    return preg_replace('/\033\[[0-9;]*m/', '', $text);
}

/**
 * Run a shell command and stream output as SSE events.
 * stderr is merged into stdout via '2>&1'.
 * Optionally writes $stdin to the process stdin before reading.
 */
function getNpmGlobalBin(): string {
    $prefix = trim((string)shell_exec('npm prefix -g 2>/dev/null'));
    return $prefix ? $prefix . '/bin' : '';
}

function runCommandSSE(string $cmd, ?string $stdin = null): void {
    $npmBin = getNpmGlobalBin();
    $basePath = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';
    $fullPath = $npmBin ? $npmBin . ':' . $basePath : $basePath;

    $env = array_merge(getenv(), [
        'PATH'                     => $fullPath,
        'HOME'                     => '/tmp',
        'XDG_CONFIG_HOME'          => '/tmp/.config',
        'PUPPETEER_EXECUTABLE_PATH'=> '/usr/bin/chromium',
        'PUPPETEER_SKIP_DOWNLOAD'  => '1',
        'TERM'                     => 'dumb',
    ]);

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
    ];

    $proc = proc_open(['sh', '-c', $cmd . ' 2>&1'], $descriptors, $pipes, BASE_DIR, $env);

    if (!is_resource($proc)) {
        sendSSEEvent(['line' => 'Fehler: Befehl konnte nicht gestartet werden.']);
        sendSSEEvent(['done' => true, 'exit' => 1]);
        return;
    }

    if ($stdin !== null) {
        fwrite($pipes[0], $stdin);
    }
    fclose($pipes[0]);

    while (!feof($pipes[1])) {
        $line = fgets($pipes[1], 4096);
        if ($line !== false && $line !== '') {
            $clean = stripAnsi(rtrim($line));
            if ($clean !== '') {
                sendSSEEvent(['line' => $clean]);
            }
        }
    }
    fclose($pipes[1]);

    $exitCode = proc_close($proc);
    sendSSEEvent(['done' => true, 'exit' => $exitCode]);
}
