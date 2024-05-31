# BackstopJS Scenario Generator

Dieses Repository enthält Skripte zur automatisierten Erzeugung von BackstopJS-Szenarien aus einer CSV-Datei mit URLs.

## Voraussetzungen

- PHP oder DDEV ist auf deinem System installiert.
- Node.js ist auf deinem System installiert.
- BackstopJS ist auf deinem System installiert.

## Dateien

1. **PHP-Skript (create-backstop-scenarios.php)**
2. **BackstopJS-Konfigurationsdatei (backstop.js)**

### 1. PHP-Skript (create-backstop-scenarios.php)

Dieses Skript liest die URLs aus einer CSV-Datei ein, teilt sie in Blöcke von jeweils 40 URLs auf und generiert JavaScript-Dateien, die diese URLs enthalten.

```php
<?php
// Pfad zur CSV-Datei
$csvFile = 'intern_html.csv';

// Zieldomain anpassen
$testDomain = 'https://example-symlink.ddev.site';

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
?>
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

## Nutzung der Skripte

1. CSV-Datei erzeugen: Verwende ein Tool wie den "Screaming Frog SEO Spider", um eine CSV-Datei mit URLs zu generieren. Stelle sicher, dass die Datei nur eine Spalte mit gültigen URLs enthält.

2. PHP-Skript ausführen: Führe das PHP-Skript aus, um die JavaScript-Dateien zu generieren.

```shell
ddev exec php create-backstop-scenarios.php
```

3. BackstopJS-Szenarien ausführen: Führe die BackstopJS-Befehle aus, um die Referenzbilder zu erstellen und die Tests zu starten.
```shell
backstop reference --config ./backstop.js && backstop test --config ./backstop.js
```
