<?php
require_once dirname(__DIR__) . '/common.php';

$allowed = ['reference', 'test'];
$action  = $_GET['action'] ?? '';

if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'Ungültige Aktion (reference oder test)']) . "\n\n";
    exit;
}

startSSE();

$cmd = 'backstop ' . escapeshellarg($action) . ' --config ' . escapeshellarg(BASE_DIR . '/backstop.js');

runCommandSSE($cmd);
