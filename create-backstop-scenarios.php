<?php

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
    echo "BackstopJS Scenario Generator\n\n";
    echo "Usage: ddev exec php create-backstop-scenarios.php [options]\n\n";
    echo "Options:\n";
    echo "  --urls=FILE             Path to file with URLs (default: crawled_urls.txt)\n";
    echo "  --test=URL              Test domain URL (required)\n";
    echo "  --reference=URL         Reference domain URL (required)\n";
    echo "  --help                  Show this help message\n\n";
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
    echo "Error: Both --test and --reference domains are required.\n\n";
    showHelp();
    exit(1);
}

$testDomain = rtrim($options['test'], '/');
$referenceDomain = rtrim($options['reference'], '/');

// Validate URLs
if (!filter_var($testDomain, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid test domain URL: $testDomain\n";
    exit(1);
}

if (!filter_var($referenceDomain, FILTER_VALIDATE_URL)) {
    echo "Error: Invalid reference domain URL: $referenceDomain\n";
    exit(1);
}

// Output directory for scenario files
$outputDir = 'scenarios/pending';

// Array to save the URLs
$urls = [];

echo "Configuration:\n";
echo "  URLs File:        $urlsFile\n";
echo "  Test Domain:      $testDomain\n";
echo "  Reference Domain: $referenceDomain\n";
echo "  Output Directory: $outputDir\n\n";

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
    echo "Error: URLs file not found: $urlsFile\n";
    echo "Please run the crawler first:\n";
    echo "  ddev exec php crawler.php --url $referenceDomain\n";
    exit(1);
}

// Import URLs file
$urls = file($urlsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if (empty($urls)) {
    echo "Error: No URLs found in file: $urlsFile\n";
    exit(1);
}

// Split URLs into blocks
$chunks = array_chunk($urls, 40);

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

echo "JavaScript files have been successfully created in scenarios/pending/.\n";
echo "Total files created: " . count($chunks) . "\n";
echo "\nNext steps:\n";
echo "  1. Run: ddev exec php manage-scenarios.php next\n";
echo "  2. Run: backstop reference --config ./backstop.js\n";
echo "  3. Run: backstop test --config ./backstop.js\n";

