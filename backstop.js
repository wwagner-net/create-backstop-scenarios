// Create reference images:
// backstop reference --config ./backstop.js
//
// Run test:
// backstop test --config ./backstop.js

const fs = require('fs');
const path = require('path');

// Path to the directory in which the active scenario files are stored
const scenariosDir = path.resolve(__dirname, 'scenarios', 'active');

// Load configuration from config.json or use defaults
function loadConfig() {
    const configPath = path.resolve(__dirname, 'config.json');

    // Default configuration
    const defaultConfig = {
        projectId: 'example-project',
        scenarios: {
            removeSelectors: ['#CybotCookiebotDialog'],
            hideSelectors: [],
            delay: 5000,
            misMatchThreshold: 10,
            requireSameDimensions: true
        },
        viewports: [
            { label: 'phone', width: 320, height: 480 },
            { label: 'tablet', width: 1024, height: 768 },
            { label: 'desktop', width: 1280, height: 1024 }
        ],
        engine: {
            asyncCaptureLimit: 5,
            asyncCompareLimit: 50,
            debug: false,
            debugWindow: false
        },
        report: ['browser']
    };

    // Try to load config.json
    if (fs.existsSync(configPath)) {
        try {
            const userConfig = JSON.parse(fs.readFileSync(configPath, 'utf8'));
            console.log('✓ Loaded configuration from config.json');

            // Merge user config with defaults (user config takes precedence)
            return {
                projectId: userConfig.projectId || defaultConfig.projectId,
                scenarios: { ...defaultConfig.scenarios, ...userConfig.scenarios },
                viewports: userConfig.viewports || defaultConfig.viewports,
                engine: { ...defaultConfig.engine, ...userConfig.engine },
                report: userConfig.report || defaultConfig.report
            };
        } catch (error) {
            console.warn('⚠ Error reading config.json, using defaults:', error.message);
            return defaultConfig;
        }
    } else {
        console.warn('⚠ config.json not found, using default configuration');
        console.warn('  Copy config.example.json to config.json to customize settings');
        return defaultConfig;
    }
}

const config = loadConfig();

// Function to load all files that match the name pattern
function loadScenarioUrls() {
    const scenarioUrls = [];

    // Check if active directory exists
    if (!fs.existsSync(scenariosDir)) {
        console.warn('Warning: scenarios/active/ directory does not exist or is empty.');
        console.warn('Run: ddev exec php manage-scenarios.php next');
        return scenarioUrls;
    }

    const files = fs.readdirSync(scenariosDir);

    files.forEach(file => {
        if (file.match(/^scenarioUrls_\d+\.js$/)) {
            const scenarioModule = require(path.join(scenariosDir, file)).scenarioUrls;
            scenarioUrls.push(...scenarioModule);
        }
    });

    if (scenarioUrls.length === 0) {
        console.warn('Warning: No scenario files found in scenarios/active/');
        console.warn('Run: ddev exec php manage-scenarios.php next');
    }

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
        "delay": config.scenarios.delay,
        "hideSelectors": config.scenarios.hideSelectors,
        "removeSelectors": config.scenarios.removeSelectors,
        "hoverSelector": "",
        "clickSelector": "",
        "postInteractionWait": 0,
        "selectors": [],
        "selectorExpansion": true,
        "expect": 0,
        "misMatchThreshold": config.scenarios.misMatchThreshold,
        "requireSameDimensions": config.scenarios.requireSameDimensions
    };
});

module.exports =
    {
        "id": config.projectId,
        "viewports": config.viewports,
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
        "report": config.report,
        "engine": "puppeteer",
        "engineOptions": {
            "args": ["--no-sandbox"]
        },
        "asyncCaptureLimit": config.engine.asyncCaptureLimit,
        "asyncCompareLimit": config.engine.asyncCompareLimit,
        "debug": config.engine.debug,
        "debugWindow": config.engine.debugWindow
    };
