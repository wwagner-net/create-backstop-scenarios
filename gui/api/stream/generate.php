<?php
require_once dirname(__DIR__) . '/common.php';

$test      = $_GET['test'] ?? '';
$reference = $_GET['reference'] ?? '';
$urlsFile  = $_GET['urls'] ?? '';

if (empty($test) || empty($reference)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'Test- und Referenz-Domain sind erforderlich']) . "\n\n";
    exit;
}

if (!preg_match('#^https?://#i', $test) || !preg_match('#^https?://#i', $reference)) {
    http_response_code(400);
    echo 'data: ' . json_encode(['error' => 'URLs müssen mit http:// oder https:// beginnen']) . "\n\n";
    exit;
}

startSSE();

$args = [
    '--test=' . $test,
    '--reference=' . $reference,
];

if (!empty($urlsFile)) {
    $args[] = '--urls=' . $urlsFile;
}

$escapedArgs = implode(' ', array_map('escapeshellarg', $args));
$cmd = 'php ' . escapeshellarg(BASE_DIR . '/create-backstop-scenarios.php') . ' ' . $escapedArgs;

runCommandSSE($cmd);
