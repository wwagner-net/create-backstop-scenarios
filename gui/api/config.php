<?php
require_once __DIR__ . '/common.php';

$configPath = BASE_DIR . '/config.json';

$defaultConfig = [
    'projectId' => 'my-project',
    'chunkSize' => 40,
    'scenarios' => [
        'removeSelectors' => ['#CybotCookiebotDialog'],
        'hideSelectors' => [],
        'delay' => 5000,
        'misMatchThreshold' => 10,
        'requireSameDimensions' => true,
    ],
    'viewports' => [
        ['label' => 'phone',   'width' => 320,  'height' => 480],
        ['label' => 'tablet',  'width' => 1024, 'height' => 768],
        ['label' => 'desktop', 'width' => 1280, 'height' => 1024],
    ],
    'engine' => [
        'asyncCaptureLimit' => 5,
        'asyncCompareLimit' => 50,
        'debug' => false,
        'debugWindow' => false,
    ],
    'report' => ['browser'],
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (file_exists($configPath)) {
        $raw = file_get_contents($configPath);
        $data = json_decode($raw, true);
        if ($data === null) {
            jsonResponse(['error' => 'config.json ist ungültig'], 500);
        }
        // Remove _comments key if present
        unset($data['_comments']);
        jsonResponse(['config' => $data, 'exists' => true]);
    } else {
        jsonResponse(['config' => $defaultConfig, 'exists' => false]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if ($body === null) {
        jsonResponse(['error' => 'Ungültige JSON-Daten'], 400);
    }

    $config = $body['config'] ?? null;
    if (!$config) {
        jsonResponse(['error' => 'Kein config-Objekt vorhanden'], 400);
    }

    // Basic validation
    if (empty($config['projectId'])) {
        jsonResponse(['error' => 'Projekt-ID fehlt'], 400);
    }

    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($configPath, $json) === false) {
        jsonResponse(['error' => 'Datei konnte nicht gespeichert werden'], 500);
    }

    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
