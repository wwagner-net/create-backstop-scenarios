<?php
// Path to the CSV file
$csvFile = 'crawled_urls.csv';

// Adjust test domain
$testDomain = 'https://cobra.ddev.site';

// Adjust reference domain
$referenceDomain = 'https://www.cobra.de';

// Output directory for scenario files
$outputDir = 'scenarios/pending';

// Array to save the URLs
$urls = [];

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

// Import CSV file
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $urls[] = $data[0];
    }
    fclose($handle);
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

