<?php

require_once __DIR__ . '/src/cli_bootstrap.php';
cliBootstrap($argv);

/**
 * Load chunk size from config.json
 */
function getChunkSize() {
    $configPath = 'config.json';
    $defaultChunkSize = 40;

    if (!file_exists($configPath)) {
        return $defaultChunkSize;
    }

    try {
        $config = json_decode(file_get_contents($configPath), true);
        if (isset($config['chunkSize']) && is_numeric($config['chunkSize'])) {
            return (int)$config['chunkSize'];
        }
    } catch (Exception $e) {
        // If config can't be read, use default
    }

    return $defaultChunkSize;
}

/**
 * Parse command line arguments
 */
function getArguments() {
    global $argv;

    $shortopts = "";
    $longopts = array(
        "test:",        // Test domain (optional)
        "reference:",   // Reference domain (optional)
        "urls:",        // URLs file path (optional)
    );
    $options = getopt($shortopts, $longopts);

    // Show help if --help is provided
    if (in_array('--help', $argv)) {
        showHelp();
        exit(0);
    }

    return $options;
}

/**
 * Show help message
 */
function showHelp() {
    echo cliT('cli.create.title') . "\n\n";
    echo cliT('cli.create.usage') . "\n\n";
    echo "Options:\n";
    echo cliT('cli.create.opt_urls') . "\n";
    echo cliT('cli.create.opt_test') . "\n";
    echo cliT('cli.create.opt_reference') . "\n";
    echo "  --help                  Show this help message\n";
    echo cliT('cli.lang.option') . "\n\n";
    echo "Example:\n";
    echo "  ddev exec php create-backstop-scenarios.php \\\n";
    echo "    --test=https://example.ddev.site \\\n";
    echo "    --reference=https://www.example.com\n\n";
}

// Parse arguments
$options = getArguments();

// Path to the URLs file
$urlsFile = $options['urls'] ?? 'crawled_urls.txt';

// Get domains from arguments
if (!isset($options['test']) || !isset($options['reference'])) {
    echo cliT('cli.create.need_domains') . "\n\n";
    showHelp();
    exit(1);
}

$testDomain = rtrim($options['test'], '/');
$referenceDomain = rtrim($options['reference'], '/');

// Validate URLs
if (!filter_var($testDomain, FILTER_VALIDATE_URL)) {
    echo cliT('cli.create.invalid_test', ['url' => $testDomain]) . "\n";
    exit(1);
}

if (!filter_var($referenceDomain, FILTER_VALIDATE_URL)) {
    echo cliT('cli.create.invalid_reference', ['url' => $referenceDomain]) . "\n";
    exit(1);
}

// Output directory for scenario files
$outputDir = 'scenarios/pending';

// Array to save the URLs
$urls = [];

// Get chunk size from config.json (default: 40)
$chunkSize = getChunkSize();

echo cliT('cli.create.config_header') . "\n";
echo "  URLs File:        $urlsFile\n";
echo "  Test Domain:      $testDomain\n";
echo "  Reference Domain: $referenceDomain\n";
echo "  Output Directory: $outputDir\n";
echo "  Chunk Size:       $chunkSize URLs per file\n\n";

// Create directory structure if it doesn't exist
if (!file_exists('scenarios/pending')) {
    mkdir('scenarios/pending', 0755, true);
}
if (!file_exists('scenarios/active')) {
    mkdir('scenarios/active', 0755, true);
}
if (!file_exists('scenarios/done')) {
    mkdir('scenarios/done', 0755, true);
}

// Check if URLs file exists
if (!file_exists($urlsFile)) {
    echo cliT('cli.create.urls_missing', ['file' => $urlsFile]) . "\n";
    echo "Please run the crawler first:\n";
    echo "  ddev exec php crawler.php --url $referenceDomain\n";
    exit(1);
}

// Import URLs file
$urls = file($urlsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (empty($urls)) {
    echo cliT('cli.create.urls_empty', ['file' => $urlsFile]) . "\n";
    exit(1);
}

// Split URLs into blocks (using chunk size from earlier)
$chunks = array_chunk($urls, $chunkSize);

// Create JavaScript files
foreach ($chunks as $index => $chunk) {
    $filename = $outputDir . '/scenarioUrls_' . ($index + 1) . '.js';
    $fileContent = "module.exports = {\n";
    $fileContent .= "    scenarioUrls: [\n";

    foreach ($chunk as $url) {
        $fileContent .= "        {\n";
        $fileContent .= "            \"label\": \"$url\",\n";
        $fileContent .= "            \"url\": \"" . str_replace($referenceDomain, $testDomain, $url) . "\",\n";
        $fileContent .= "            \"referenceUrl\": \"$url\"\n";
        $fileContent .= "        },\n";
    }

    // Remove the last comma and add the closing parenthesis
    $fileContent = rtrim($fileContent, ",\n") . "\n    ]\n";
    $fileContent .= "};\n";

    // Write file
    file_put_contents($filename, $fileContent);
}

echo cliT('cli.create.done') . "\n";
echo cliT('cli.create.total_files', ['count' => count($chunks)]) . "\n";
echo "\n" . cliT('cli.create.next') . "\n";
echo cliT('cli.create.next1') . "\n";
echo cliT('cli.create.next2') . "\n";
echo cliT('cli.create.next3') . "\n";

