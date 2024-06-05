// Create reference images:
// backstop reference --config ./backstop.js
//
// Run test:
// backstop test --config ./backstop.js

const fs = require('fs');
const path = require('path');

// Path to the directory in which the scenarioUrls files are stored
const scenariosDir = path.resolve(__dirname);

// Function to load all files that match the name pattern
function loadScenarioUrls() {
    const scenarioUrls = [];
    const files = fs.readdirSync(scenariosDir);

    files.forEach(file => {
        if (file.match(/^scenarioUrls_\d+\.js$/)) {
            const scenarioModule = require(path.join(scenariosDir, file)).scenarioUrls;
            scenarioUrls.push(...scenarioModule);
        }
    });

    return scenarioUrls;
}

const allScenarioUrls = loadScenarioUrls();

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
