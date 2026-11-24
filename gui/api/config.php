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
            jsonResponse(['error' => apiT('api.error.config_invalid')], 500);
        }
        unset($data['_comments']);
        jsonResponse(['config' => $data, 'exists' => true, 'locale' => I18n::getLocale()]);
    } else {
        jsonResponse(['config' => $defaultConfig, 'exists' => false, 'locale' => I18n::getLocale()]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    bootstrapApiLocale(is_array($body) ? $body : null);
    if ($body === null) {
        jsonResponse(['error' => apiT('api.error.invalid_json')], 400);
    }

    $config = $body['config'] ?? null;
    if (!$config) {
        jsonResponse(['error' => apiT('api.error.config_missing')], 400);
    }

    if (empty($config['projectId'])) {
        jsonResponse(['error' => apiT('api.error.project_id_missing')], 400);
    }

    if (isset($config['locale'])) {
        $norm = I18n::normalize((string)$config['locale']);
        if ($norm === null) {
            unset($config['locale']);
        } else {
            $config['locale'] = $norm;
            I18n::setLocale($norm);
        }
    }

    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($configPath, $json) === false) {
        jsonResponse(['error' => apiT('api.error.save_failed')], 500);
    }

    jsonResponse(['success' => true, 'locale' => I18n::getLocale()]);
}

jsonResponse(['error' => apiT('api.error.method_not_allowed')], 405);
