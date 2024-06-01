<?php
// Path to the CSV file
$csvFile = 'crawled_urls.csv';

// Adjust test domain
$testDomain = 'https://example.ddev.site';

// Adjust reference domain
$referenceDomain = 'https://www.example.com';

// Array to save the URLs
$urls = [];

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
    $filename = 'scenarioUrls_' . ($index + 1) . '.js';
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

echo "JavaScript files have been successfully created.\n";

