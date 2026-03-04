<?php
require_once __DIR__ . '/common.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Methode nicht erlaubt'], 405);
}

$targets = [
    BASE_DIR . '/backstop_data/bitmaps_reference',
    BASE_DIR . '/backstop_data/bitmaps_test',
    BASE_DIR . '/backstop_data/html_report',
    BASE_DIR . '/backstop_data/ci_report',
];

function deleteContents(string $dir): int {
    if (!is_dir($dir)) return 0;
    $deleted = 0;
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteContents($path);
            rmdir($path);
        } else {
            unlink($path);
        }
        $deleted++;
    }
    return $deleted;
}

$deleted = 0;
$errors  = [];

foreach ($targets as $dir) {
    try {
        $deleted += deleteContents($dir);
    } catch (Throwable $e) {
        $errors[] = basename($dir) . ': ' . $e->getMessage();
    }
}

if ($errors) {
    jsonResponse(['error' => implode('; ', $errors)], 500);
}

jsonResponse(['success' => true, 'deleted' => $deleted]);
