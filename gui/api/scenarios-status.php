<?php
require_once __DIR__ . '/common.php';

$scenariosDir = BASE_DIR . '/scenarios';

function countScenarioFiles(string $dir): array {
    if (!is_dir($dir)) return ['count' => 0, 'files' => []];
    $files = array_filter(scandir($dir), fn($f) => preg_match('/^scenarioUrls_.*\.js$/', $f));
    return ['count' => count($files), 'files' => array_values($files)];
}

function countUrlsInFile(string $filePath): int {
    if (!file_exists($filePath)) return 0;
    $content = file_get_contents($filePath);
    return substr_count($content, '"label"');
}

$pending = countScenarioFiles($scenariosDir . '/pending');
$active  = countScenarioFiles($scenariosDir . '/active');
$done    = countScenarioFiles($scenariosDir . '/done');

$activeUrlCount = 0;
$activeFile = null;
if ($active['count'] > 0) {
    $activeFile = $active['files'][0];
    $activeUrlCount = countUrlsInFile($scenariosDir . '/active/' . $activeFile);
}

$reportExists = file_exists(BASE_DIR . '/backstop_data/html_report/index.html');

function dirHasPngs(string $dir): bool {
    if (!is_dir($dir)) return false;
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $f) {
        if (strtolower($f->getExtension()) === 'png') return true;
    }
    return false;
}
$referenceExists = dirHasPngs(BASE_DIR . '/backstop_data/bitmaps_reference');

jsonResponse([
    'pending' => $pending['count'],
    'active'  => $active['count'],
    'done'    => $done['count'],
    'activeFile' => $activeFile,
    'activeUrlCount' => $activeUrlCount,
    'pendingFiles' => $pending['files'],
    'reportExists' => $reportExists,
    'referenceExists' => $referenceExists,
]);
