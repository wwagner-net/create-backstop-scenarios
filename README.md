# BackstopJS Scenario Generator

Dieses Repository enthält Skripte zur automatisierten Erzeugung von BackstopJS-Szenarien aus einer CSV-Datei mit URLs.

## Voraussetzungen

- PHP oder DDEV ist auf deinem System installiert.
- Node.js ist auf deinem System installiert.
- BackstopJS ist auf deinem System installiert.

## Dateien

1. **PHP-Skript (create-backstop-scenarios.php)**
2. **BackstopJS-Konfigurationsdatei (backstop.js)**
3. **PHP-Crawler-Skript (crawler.php)**

### 1. PHP-Skript (create-backstop-scenarios.php)

Dieses Skript liest die URLs aus einer CSV-Datei ein, teilt sie in Blöcke von jeweils 40 URLs auf und generiert JavaScript-Dateien, die diese URLs enthalten.

```php
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
```

#### Ausführung des PHP-Skripts
Wenn du lokal mit DDEV arbeitest, führe das Skript mit folgendem Befehl aus:
```sh
ddev exec php create-backstop-scenarios.php
```

### 2. BackstopJS-Konfigurationsdatei (backstop.js)
Diese Datei importiert die generierten JavaScript-Dateien und verwendet die URLs zur Konfiguration der BackstopJS-Szenarien.

```javascript
// Create reference images:
// backstop reference --config ./backstop.js
//
// Run test:
// backstop test --config ./backstop.js

const scenarioUrls1 = require('./scenarioUrls_1.js').scenarioUrls;
const scenarioUrls2 = require('./scenarioUrls_2.js').scenarioUrls;
// und so weiter

const allScenarioUrls = [...scenarioUrls1, ...scenarioUrls2];

var scenarios = allScenarioUrls.map(function (scenarioUrl) {
    return {
        "label": scenarioUrl.label,
        "cookiePath": "backstop_data/engine_scripts/cookies.json",
        "url": scenarioUrl.url,
        "referenceUrl": scenarioUrl.referenceUrl,
        "readyEvent": "",
        "readySelector": "",
        "delay": 5000,
        "hideSelectors": [],
        "removeSelectors": ["#CybotCookiebotDialog"],
        "hoverSelector": "",
        "clickSelector": "",
        "postInteractionWait": 0,
        "selectors": [],
        "selectorExpansion": true,
        "expect": 0,
        "misMatchThreshold": 10,
        "requireSameDimensions": true
    };
});

module.exports =
    {
        "id": "example-project",
        "viewports": [
            {
                "label": "phone",
                "width": 320,
                "height": 480
            },
            {
                "label": "tablet",
                "width": 1024,
                "height": 768
            },
            {
                "label": "desktop",
                "width": 1280,
                "height": 1024
            }
        ],
        "onBeforeScript": "puppet/onBefore.js",
        "onReadyScript": "puppet/onReady.js",
        "scenarios": scenarios,
        "paths": {
            "bitmaps_reference": "backstop_data/bitmaps_reference",
            "bitmaps_test": "backstop_data/bitmaps_test",
            "engine_scripts": "backstop_data/engine_scripts",
            "html_report": "backstop_data/html_report",
            "ci_report": "backstop_data/ci_report"
        },
        "report": ["browser"],
        "engine": "puppeteer",
        "engineOptions": {
            "args": ["--no-sandbox"]
        },
        "asyncCaptureLimit": 5,
        "asyncCompareLimit": 50,
        "debug": false,
        "debugWindow": false
    };
```

### 3. PHP-Crawler-Skript (crawler.php)

Dieses Skript crawlt die angegebene Referenz-Domain, extrahiert alle relevanten URLs und speichert sie in einer CSV-Datei. Es filtert dabei Links auf Dateien, JavaScript, tel:, mailto:, und URLs mit Parametern.

Es kann als Alternative zu Tools wie dem Screaming Frog SEO Spider genutzt werden:

**Wichtig:** Die erzeugte CSV-Datei sollte überpfrüft und ggf. bereinigt werden. Es kann auch sein, dass die Liste der URLs nicht vollständig ist.

```php
<?php

function getUrls($domain) {
    $visited = [];
    $toVisit = [$domain];
    $urls = [];
    $totalVisited = 0;

    while ($toVisit) {
        $currentUrl = array_pop($toVisit);

        // Skip if already visited
        if (in_array($currentUrl, $visited)) {
            continue;
        }

        $visited[] = $currentUrl;
        $html = @file_get_contents($currentUrl);
        if ($html === FALSE) {
            continue;
        }

        // Matches all href attributes
        preg_match_all('/href="([^"]*)"/i', $html, $matches);

        foreach ($matches[1] as $url) {
            // Resolve relative URLs
            if (strpos($url, 'http') !== 0) {
                $url = rtrim($domain, '/') . '/' . ltrim($url, '/');
            }

            // Filter out URLs that don't belong to the domain
            if (strpos($url, $domain) !== 0) {
                continue;
            }

            // Filter out URLs that are likely to be files (not pages)
            if (preg_match('/\.(pdf|docx?|xlsx?|pptx?|jpg|jpeg|png|gif|mp4|mp3|zip|rar|7z|tar|gz|ico|svg|webmanifest)$/i', $url)) {
                continue;
            }

            // Filter out URLs with parameters
            if (strpos($url, '?') !== false) {
                continue;
            }

            // Filter out tel:, mailto:, and javascript: links
            if (strpos($url, 'tel:') !== false || strpos($url, 'mailto:') !== false || strpos($url, 'javascript:') !== false) {
                continue;
            }

            // Add to URLs list
            $urls[] = $url;

            // Add to the list of URLs to visit
            if (!in_array($url, $visited) && !in_array($url, $toVisit)) {
                $toVisit[] = $url;
            }
        }

        $totalVisited++;
        echo "Besuchte Seiten: $totalVisited, Noch zu besuchen: " . count($toVisit) . "\r";
    }

    // Remove duplicates and sort
    $urls = array_unique($urls);
    sort($urls);

    echo "\n";

    return $urls;
}

// Referenz-Domain anpassen
$referenceDomain = 'https://www.example.com';

// URLs crawlen
$urls = getUrls($referenceDomain);

// Pfad zur Ausgabe-CSV-Datei
$outputCsvFile = 'crawled_urls.csv';

// CSV-Datei schreiben
if (($handle = fopen($outputCsvFile, 'w')) !== FALSE) {
    foreach ($urls as $url) {
        fputcsv($handle, [$url]);
    }
    fclose($handle);
    echo "CSV-Datei wurde erfolgreich erstellt: $outputCsvFile\n";
} else {
    echo "Fehler beim Schreiben der CSV-Datei.\n";
}
```

## Nutzung der Skripte

1. Führe optional das PHP-Skript `crawler.php` aus, um eine CSV-Datei mit einer Liste der Referenz-URLs zu erstellen.
```shell
ddev exec php crawler.php
```
Die gesammelten URLs werden in der Datei crawled_urls.csv gespeichert. Du kannst diese Datei dann manuell prüfen und bereinigen, bevor du sie für die Tests verwendest.

2. CSV-Datei erzeugen: Wenn du nicht die `crawper.php`verwendest, kannst du ein Tool wie den "Screaming Frog SEO Spider" nutzen, um eine CSV-Datei mit URLs zu generieren. Stelle sicher, dass die Datei nur eine Spalte mit gültigen URLs enthält.

3. PHP-Skript ausführen: Führe das PHP-Skript aus, um die JavaScript-Dateien zu generieren.

```shell
ddev exec php create-backstop-scenarios.php
```

4. BackstopJS-Szenarien ausführen: Führe die BackstopJS-Befehle aus, um die Referenzbilder zu erstellen und die Tests zu starten.
```shell
backstop reference --config ./backstop.js && backstop test --config ./backstop.js
```
