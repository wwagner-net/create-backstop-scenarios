<?php
require_once dirname(__DIR__) . '/common.php';

$mode = $_GET['mode'] ?? '';
$url  = $_GET['url'] ?? '';

if (!in_array($mode, ['sitemap', 'crawl'], true) || empty($url)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'Ungültige Parameter']) . "\n\n";
    exit;
}

if (!preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'URL muss mit http:// oder https:// beginnen']) . "\n\n";
    exit;
}

startSSE();

$args = [];
if ($mode === 'sitemap') {
    $args[] = '--sitemap=' . $url;
} else {
    $args[] = '--url=' . $url;
}

if (!empty($_GET['maxUrls']) && is_numeric($_GET['maxUrls'])) {
    $args[] = '--max-urls=' . (int)$_GET['maxUrls'];
}
if (!empty($_GET['includeParams'])) {
    $args[] = '--include-params';
}

$escapedArgs = implode(' ', array_map('escapeshellarg', $args));
$cmd = 'php ' . escapeshellarg(BASE_DIR . '/crawler.php') . ' ' . $escapedArgs;

runCommandSSE($cmd);
