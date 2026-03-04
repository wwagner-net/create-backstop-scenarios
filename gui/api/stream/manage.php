<?php
require_once dirname(__DIR__) . '/common.php';

$allowed = ['next', 'done', 'skip', 'reset', 'status', 'list'];
$action  = $_GET['action'] ?? '';

if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'Ungültige Aktion']) . "\n\n";
    exit;
}

startSSE();

$cmd = 'php ' . escapeshellarg(BASE_DIR . '/manage-scenarios.php') . ' ' . escapeshellarg($action);

// For 'reset', automatically confirm with 'y'
$stdin = ($action === 'reset') ? "y\n" : null;

runCommandSSE($cmd, $stdin);
