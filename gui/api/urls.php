<?php
require_once __DIR__ . '/common.php';

$urlsPath = BASE_DIR . '/crawled_urls.txt';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($urlsPath)) {
        jsonResponse(['urls' => [], 'exists' => false, 'count' => 0]);
    }
    $content = file_get_contents($urlsPath);
    $lines = array_filter(array_map('trim', explode("\n", $content)), fn($l) => $l !== '');
    $urls = array_values($lines);
    jsonResponse(['urls' => $urls, 'exists' => true, 'count' => count($urls)]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if ($body === null || !isset($body['urls'])) {
        jsonResponse(['error' => 'Ungültige Daten'], 400);
    }
    $urls = array_filter(array_map('trim', (array)$body['urls']), fn($u) => $u !== '');
    $content = implode("\n", array_values($urls)) . "\n";
    if (file_put_contents($urlsPath, $content) === false) {
        jsonResponse(['error' => 'Datei konnte nicht gespeichert werden'], 500);
    }
    jsonResponse(['success' => true, 'count' => count($urls)]);
}

jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
