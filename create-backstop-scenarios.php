<?php
// Pfad zur CSV-Datei
$csvFile = 'crawled_urls.csv';

// Testdomain anpassen
$testDomain = 'https://example.ddev.site';

// Referenz-Domain anpassen
$referenceDomain = 'https://www.example.com';

// Array zum Speichern der URLs
$urls = [];

// CSV-Datei einlesen
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $urls[] = $data[0];
    }
    fclose($handle);
}

// URLs in Blöcke aufteilen
$chunks = array_chunk($urls, 40);

// JavaScript-Dateien erzeugen
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

    // Entferne das letzte Komma und füge die schließende Klammer hinzu
    $fileContent = rtrim($fileContent, ",\n") . "\n    ]\n";
    $fileContent .= "};\n";

    // Datei schreiben
    file_put_contents($filename, $fileContent);
}

echo "JavaScript-Dateien wurden erfolgreich erstellt.\n";

