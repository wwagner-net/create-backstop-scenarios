<?php
require_once __DIR__ . '/common.php';

$urlsFile = BASE_DIR . '/crawled_urls.txt';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($urlsFile)) {
        jsonResponse(['urls' => [], 'exists' => false, 'count' => 0]);
    }
    $content = file_get_contents($urlsFile);
    $lines = array_filter(array_map('trim', explode("\n", $content)), fn($l) => $l !== '');
    $urls = array_values($lines);
    jsonResponse(['urls' => $urls, 'exists' => true, 'count' => count($urls)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    bootstrapApiLocale(is_array($body) ? $body : null);
    if ($body === null || !isset($body['urls'])) {
        jsonResponse(['error' => apiT('api.error.invalid_data')], 400);
    }
    $urls = array_filter(array_map('trim', (array)$body['urls']), fn($u) => $u !== '');
    $content = implode("\n", $urls) . (count($urls) ? "\n" : '');
    if (file_put_contents($urlsFile, $content) === false) {
        jsonResponse(['error' => apiT('api.error.save_failed')], 500);
    }
    jsonResponse(['success' => true, 'count' => count($urls)]);
}

jsonResponse(['error' => apiT('api.error.method_not_allowed')], 405);
