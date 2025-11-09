<?php

/**
 * Interactive Setup Wizard for BackstopJS Scenario Generator
 *
 * This script helps you create a customized config.json file through
 * an interactive command-line interface.
 */

// Color output for better UX (works in most terminals)
function color($text, $color = 'white') {
    $colors = [
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'magenta' => '0;35',
        'cyan' => '0;36',
        'white' => '0;37',
        'bold' => '1',
    ];

    $code = $colors[$color] ?? $colors['white'];
    return "\033[{$code}m{$text}\033[0m";
}

function printHeader() {
    echo "\n";
    echo color("╔════════════════════════════════════════════════════════════════╗", 'cyan') . "\n";
    echo color("║                                                                ║", 'cyan') . "\n";
    echo color("║          BackstopJS Scenario Generator Setup Wizard           ║", 'cyan') . "\n";
    echo color("║                                                                ║", 'cyan') . "\n";
    echo color("╚════════════════════════════════════════════════════════════════╝", 'cyan') . "\n";
    echo "\n";
}

function ask($question, $default = null, $required = false) {
    if ($default !== null) {
        echo color($question . " ", 'yellow') . color("[default: $default]", 'cyan') . ": ";
    } else {
        echo color($question, 'yellow') . ": ";
    }

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    // Use default if nothing entered
    if (empty($line) && $default !== null) {
        return $default;
    }

    // Validate required fields
    if ($required && empty($line)) {
        echo color("⚠ This field is required. Please try again.\n", 'red');
        return ask($question, $default, $required);
    }

    return $line;
}

function askYesNo($question, $default = true) {
    $defaultText = $default ? 'Y/n' : 'y/N';
    echo color($question . " ", 'yellow') . color("[$defaultText]", 'cyan') . ": ";

    $handle = fopen("php://stdin", "r");
    $line = strtolower(trim(fgets($handle)));
    fclose($handle);

    if (empty($line)) {
        return $default;
    }

    return in_array($line, ['y', 'yes', 'ja', 'j']);
}

function askMultiple($question, $instruction = "Separate multiple selectors with commas") {
    echo color($question, 'yellow') . "\n";
    echo color("  " . $instruction, 'cyan') . "\n";
    echo color("  Example: #cookie-banner, .gdpr-notice, #consent-dialog", 'cyan') . "\n";
    echo "> ";

    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);

    if (empty($line)) {
        return [];
    }

    // Split by comma and trim each selector
    $selectors = array_map('trim', explode(',', $line));
    return array_filter($selectors); // Remove empty values
}

function validateUrl($url) {
    if (empty($url)) {
        return true; // Allow empty (optional fields)
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo color("⚠ Invalid URL format. Please use http:// or https://\n", 'red');
        return false;
    }

    return true;
}

function createConfig($config) {
    $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents('config.json', $json);
}

function printSummary($config) {
    echo "\n";
    echo color("═══════════════════════════════════════════════════════════════", 'green') . "\n";
    echo color("Configuration Summary", 'green') . "\n";
    echo color("═══════════════════════════════════════════════════════════════", 'green') . "\n\n";

    echo color("Project ID: ", 'cyan') . $config['projectId'] . "\n";

    if (!empty($config['scenarios']['removeSelectors'])) {
        echo color("Remove Selectors: ", 'cyan') . implode(', ', $config['scenarios']['removeSelectors']) . "\n";
    }

    if (!empty($config['scenarios']['hideSelectors'])) {
        echo color("Hide Selectors: ", 'cyan') . implode(', ', $config['scenarios']['hideSelectors']) . "\n";
    }

    echo color("Delay: ", 'cyan') . $config['scenarios']['delay'] . "ms\n";
    echo color("Mismatch Threshold: ", 'cyan') . $config['scenarios']['misMatchThreshold'] . "%\n";
    echo color("Viewports: ", 'cyan') . count($config['viewports']) . " configured\n";

    echo "\n" . color("✓ Configuration saved to config.json", 'green') . "\n\n";
}

// ============================================================================
// Main Setup Wizard Flow
// ============================================================================

printHeader();

// Check if config.json already exists
if (file_exists('config.json')) {
    echo color("⚠ Warning: config.json already exists!", 'yellow') . "\n\n";

    if (!askYesNo("Do you want to overwrite it?", false)) {
        echo color("\nSetup cancelled. Your existing config.json was not modified.\n", 'cyan');
        exit(0);
    }

    echo "\n";
}

echo color("This wizard will help you create a custom configuration.\n", 'white');
echo color("Press Enter to use default values (shown in brackets).\n\n", 'white');

// ============================================================================
// Step 1: Project Information
// ============================================================================

echo color("━━━ Step 1: Project Information ━━━\n\n", 'magenta');

$projectId = ask("Project ID (e.g., my-typo3-project)", "my-project");

echo "\n";

// ============================================================================
// Step 2: Selectors (Cookie Banners, etc.)
// ============================================================================

echo color("━━━ Step 2: Element Selectors ━━━\n\n", 'magenta');

echo color("Remove Selectors are CSS selectors for elements to completely remove\n", 'white');
echo color("before taking screenshots (e.g., cookie banners, popups).\n\n", 'white');

$removeSelectors = askMultiple("Enter CSS selectors to remove (or press Enter to skip)");

if (empty($removeSelectors)) {
    $useDefaultRemove = askYesNo("Use default (#CybotCookiebotDialog)?", true);
    if ($useDefaultRemove) {
        $removeSelectors = ['#CybotCookiebotDialog'];
    }
}

echo "\n";

echo color("Hide Selectors are CSS selectors for elements to temporarily hide\n", 'white');
echo color("(e.g., timestamps, counters, live chat widgets).\n\n", 'white');

$hideSelectors = askMultiple("Enter CSS selectors to hide (or press Enter to skip)");

echo "\n";

// ============================================================================
// Step 3: Timing & Thresholds
// ============================================================================

echo color("━━━ Step 3: Timing & Comparison Settings ━━━\n\n", 'magenta');

echo color("Delay: Wait time before taking screenshot (in milliseconds)\n", 'white');
echo color("Increase this for slow-loading or JavaScript-heavy pages.\n\n", 'white');

$delay = (int)ask("Delay in milliseconds", "5000");

echo "\n";

echo color("Mismatch Threshold: Acceptable difference percentage (0-100)\n", 'white');
echo color("Lower = stricter, Higher = more forgiving\n\n", 'white');

$misMatchThreshold = (int)ask("Mismatch threshold", "10");

echo "\n";

// ============================================================================
// Step 4: Viewports
// ============================================================================

echo color("━━━ Step 4: Viewports (Screen Sizes) ━━━\n\n", 'magenta');

$useDefaultViewports = askYesNo("Use default viewports (phone, tablet, desktop)?", true);

if ($useDefaultViewports) {
    $viewports = [
        ['label' => 'phone', 'width' => 320, 'height' => 480],
        ['label' => 'tablet', 'width' => 1024, 'height' => 768],
        ['label' => 'desktop', 'width' => 1280, 'height' => 1024],
    ];
} else {
    echo color("\nCustom viewport configuration is advanced.\n", 'yellow');
    echo color("For now, we'll use the defaults. You can edit config.json later.\n", 'yellow');
    $viewports = [
        ['label' => 'phone', 'width' => 320, 'height' => 480],
        ['label' => 'tablet', 'width' => 1024, 'height' => 768],
        ['label' => 'desktop', 'width' => 1280, 'height' => 1024],
    ];
}

echo "\n";

// ============================================================================
// Step 5: Advanced Settings
// ============================================================================

echo color("━━━ Step 5: Advanced Settings ━━━\n\n", 'magenta');

$configureAdvanced = askYesNo("Configure advanced settings (asyncCaptureLimit, debug)?", false);

if ($configureAdvanced) {
    echo "\n";
    echo color("Async Capture Limit: Number of screenshots taken in parallel\n", 'white');
    echo color("Lower = more stable, Higher = faster\n\n", 'white');

    $asyncCaptureLimit = (int)ask("Async capture limit", "5");

    echo "\n";

    $debug = askYesNo("Enable debug mode?", false);
    $debugWindow = askYesNo("Show browser window during testing?", false);

    $engine = [
        'asyncCaptureLimit' => $asyncCaptureLimit,
        'asyncCompareLimit' => 50,
        'debug' => $debug,
        'debugWindow' => $debugWindow,
    ];
} else {
    $engine = [
        'asyncCaptureLimit' => 5,
        'asyncCompareLimit' => 50,
        'debug' => false,
        'debugWindow' => false,
    ];
}

// ============================================================================
// Build Configuration Object
// ============================================================================

$config = [
    'projectId' => $projectId,
    'scenarios' => [
        'removeSelectors' => $removeSelectors,
        'hideSelectors' => $hideSelectors,
        'delay' => $delay,
        'misMatchThreshold' => $misMatchThreshold,
        'requireSameDimensions' => true,
    ],
    'viewports' => $viewports,
    'engine' => $engine,
    'report' => ['browser'],
    '_comments' => [
        'projectId' => 'Unique identifier for your project',
        'removeSelectors' => 'CSS selectors to remove before screenshots (e.g., cookie banners)',
        'hideSelectors' => 'CSS selectors to temporarily hide (e.g., timestamps)',
        'delay' => 'Wait time in milliseconds before screenshot',
        'misMatchThreshold' => 'Acceptable difference percentage (0-100)',
        'requireSameDimensions' => 'Require same dimensions for reference and test',
        'viewports' => 'Screen sizes to test',
        'asyncCaptureLimit' => 'Parallel screenshots (lower = stable, higher = fast)',
        'asyncCompareLimit' => 'Parallel comparisons',
        'debug' => 'Enable debug mode',
        'debugWindow' => 'Show browser window during testing',
        'report' => 'Report types: browser, CI, json',
    ],
];

// ============================================================================
// Save Configuration
// ============================================================================

createConfig($config);
printSummary($config);

// ============================================================================
// Next Steps
// ============================================================================

echo color("Next Steps:\n", 'cyan');
echo color("═══════════════════════════════════════════════════════════════\n\n", 'cyan');

echo "1. " . color("Collect URLs from your reference site:\n", 'white');
echo "   " . color("ddev exec php crawler.php --sitemap https://example.com/sitemap.xml\n\n", 'green');

echo "2. " . color("Generate test scenarios:\n", 'white');
echo "   " . color("ddev exec php create-backstop-scenarios.php \\\n", 'green');
echo "   " . color("     --test=https://your-test-site.ddev.site \\\n", 'green');
echo "   " . color("     --reference=https://www.example.com\n\n", 'green');

echo "3. " . color("Run BackstopJS tests:\n", 'white');
echo "   " . color("ddev exec php manage-scenarios.php next\n", 'green');
echo "   " . color("backstop reference --config ./backstop.js\n", 'green');
echo "   " . color("backstop test --config ./backstop.js\n\n", 'green');

echo color("For more information, see README.md\n", 'white');
echo color("Happy testing! 🎉\n\n", 'yellow');
