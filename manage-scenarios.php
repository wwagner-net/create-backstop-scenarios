<?php
/**
 * Manage BackstopJS scenario files
 *
 * This script manages the workflow of scenario files through three states:
 * - pending: Newly generated files waiting to be tested
 * - active: Currently being tested
 * - done: Already tested and archived
 */

$pendingDir = 'scenarios/pending';
$activeDir = 'scenarios/active';
$doneDir = 'scenarios/done';

// Ensure directories exist
foreach ([$pendingDir, $activeDir, $doneDir] as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Get command from arguments
$command = $argv[1] ?? 'status';

switch ($command) {
    case 'next':
        moveNextScenario();
        break;

    case 'done':
        markCurrentAsDone();
        break;

    case 'skip':
        skipCurrentScenario();
        break;

    case 'status':
        showStatus();
        break;

    case 'list':
        listScenarios();
        break;

    case 'reset':
        resetScenarios();
        break;

    case 'auto':
        autoWorkflow();
        break;

    default:
        showHelp();
        break;
}

/**
 * Move the next pending scenario to active
 */
function moveNextScenario() {
    global $pendingDir, $activeDir;

    // Check if there's already an active scenario
    $activeFiles = glob("$activeDir/scenarioUrls_*.js");
    if (!empty($activeFiles)) {
        echo "Error: There is already an active scenario file:\n";
        echo "  " . basename($activeFiles[0]) . "\n\n";
        echo "Please run one of the following commands first:\n";
        echo "  ddev exec php manage-scenarios.php done  (mark as done and move to next)\n";
        echo "  ddev exec php manage-scenarios.php skip  (skip and move to next)\n";
        return;
    }

    // Get pending files
    $pendingFiles = glob("$pendingDir/scenarioUrls_*.js");

    if (empty($pendingFiles)) {
        echo "No pending scenario files found.\n";
        echo "All scenarios have been processed!\n";
        return;
    }

    // Sort files naturally
    natsort($pendingFiles);
    $nextFile = reset($pendingFiles);
    $filename = basename($nextFile);

    // Move to active
    rename($nextFile, "$activeDir/$filename");

    echo "✓ Moved to active: $filename\n";
    echo "  URLs in this scenario: " . countUrlsInFile("$activeDir/$filename") . "\n\n";

    showStatus();

    echo "\nNext steps:\n";
    echo "  backstop reference --config ./backstop.js\n";
    echo "  backstop test --config ./backstop.js\n";
}

/**
 * Mark current active scenario as done
 */
function markCurrentAsDone() {
    global $activeDir, $doneDir;

    $activeFiles = glob("$activeDir/scenarioUrls_*.js");

    if (empty($activeFiles)) {
        echo "No active scenario file found.\n";
        return;
    }

    $activeFile = $activeFiles[0];
    $filename = basename($activeFile);

    // Add timestamp to filename
    $timestamp = date('Ymd-His');
    $newFilename = str_replace('.js', "_$timestamp.js", $filename);

    rename($activeFile, "$doneDir/$newFilename");

    echo "✓ Marked as done: $filename\n";
    echo "  Archived as: $newFilename\n\n";

    // Automatically move next scenario to active
    echo "Moving next scenario to active...\n\n";
    moveNextScenario();
}

/**
 * Skip current active scenario (move back to pending)
 */
function skipCurrentScenario() {
    global $activeDir, $pendingDir;

    $activeFiles = glob("$activeDir/scenarioUrls_*.js");

    if (empty($activeFiles)) {
        echo "No active scenario file found.\n";
        return;
    }

    $activeFile = $activeFiles[0];
    $filename = basename($activeFile);

    rename($activeFile, "$pendingDir/$filename");

    echo "✓ Skipped: $filename\n";
    echo "  Moved back to pending.\n\n";

    // Automatically move next scenario to active
    echo "Moving next scenario to active...\n\n";
    moveNextScenario();
}

/**
 * Show status of all scenarios
 */
function showStatus() {
    global $pendingDir, $activeDir, $doneDir;

    $pendingFiles = glob("$pendingDir/scenarioUrls_*.js");
    $activeFiles = glob("$activeDir/scenarioUrls_*.js");
    $doneFiles = glob("$doneDir/scenarioUrls_*.js");

    echo "=== Scenario Status ===\n\n";

    echo "Pending: " . count($pendingFiles) . " file(s)\n";
    echo "Active:  " . count($activeFiles) . " file(s)\n";
    echo "Done:    " . count($doneFiles) . " file(s)\n\n";

    if (!empty($activeFiles)) {
        echo "Currently active:\n";
        foreach ($activeFiles as $file) {
            $urlCount = countUrlsInFile($file);
            echo "  → " . basename($file) . " ($urlCount URLs)\n";
        }
        echo "\n";
    }

    if (!empty($pendingFiles)) {
        echo "Next up:\n";
        natsort($pendingFiles);
        $nextFile = reset($pendingFiles);
        $urlCount = countUrlsInFile($nextFile);
        echo "  → " . basename($nextFile) . " ($urlCount URLs)\n";
        echo "\n";
    }
}

/**
 * List all scenarios with details
 */
function listScenarios() {
    global $pendingDir, $activeDir, $doneDir;

    echo "=== All Scenarios ===\n\n";

    echo "PENDING:\n";
    $pendingFiles = glob("$pendingDir/scenarioUrls_*.js");
    if (empty($pendingFiles)) {
        echo "  (none)\n";
    } else {
        natsort($pendingFiles);
        foreach ($pendingFiles as $file) {
            $urlCount = countUrlsInFile($file);
            echo "  - " . basename($file) . " ($urlCount URLs)\n";
        }
    }
    echo "\n";

    echo "ACTIVE:\n";
    $activeFiles = glob("$activeDir/scenarioUrls_*.js");
    if (empty($activeFiles)) {
        echo "  (none)\n";
    } else {
        foreach ($activeFiles as $file) {
            $urlCount = countUrlsInFile($file);
            echo "  - " . basename($file) . " ($urlCount URLs)\n";
        }
    }
    echo "\n";

    echo "DONE:\n";
    $doneFiles = glob("$doneDir/scenarioUrls_*.js");
    if (empty($doneFiles)) {
        echo "  (none)\n";
    } else {
        natsort($doneFiles);
        foreach ($doneFiles as $file) {
            echo "  - " . basename($file) . "\n";
        }
    }
}

/**
 * Reset: Move all scenarios back to pending
 */
function resetScenarios() {
    global $pendingDir, $activeDir, $doneDir;

    echo "This will move all active and done scenarios back to pending.\n";
    echo "Are you sure? (yes/no): ";

    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirm = trim(strtolower($line));
    fclose($handle);

    if ($confirm !== 'yes') {
        echo "Cancelled.\n";
        return;
    }

    $moved = 0;

    // Move active files
    $activeFiles = glob("$activeDir/scenarioUrls_*.js");
    foreach ($activeFiles as $file) {
        $filename = basename($file);
        // Remove timestamp if present
        $filename = preg_replace('/_\d{8}-\d{6}\.js$/', '.js', $filename);
        rename($file, "$pendingDir/$filename");
        $moved++;
    }

    // Move done files
    $doneFiles = glob("$doneDir/scenarioUrls_*.js");
    foreach ($doneFiles as $file) {
        $filename = basename($file);
        // Remove timestamp if present
        $filename = preg_replace('/_\d{8}-\d{6}\.js$/', '.js', $filename);

        // Don't overwrite if file already exists in pending
        if (!file_exists("$pendingDir/$filename")) {
            rename($file, "$pendingDir/$filename");
            $moved++;
        } else {
            echo "Skipped (already exists): $filename\n";
        }
    }

    echo "\n✓ Reset complete. Moved $moved file(s) back to pending.\n\n";
    showStatus();
}

/**
 * Automatic workflow: Process all scenarios one by one
 */
function autoWorkflow() {
    echo "=== Automatic Workflow Mode ===\n\n";
    echo "This will process all pending scenarios automatically.\n";
    echo "You'll be prompted to review each test result.\n";
    echo "\nPress Enter to start...";

    $handle = fopen("php://stdin", "r");
    fgets($handle);

    while (true) {
        // Move next to active
        moveNextScenario();

        $activeFiles = glob("scenarios/active/scenarioUrls_*.js");
        if (empty($activeFiles)) {
            echo "\n✓ All scenarios processed!\n";
            break;
        }

        echo "\nRun the BackstopJS tests now:\n";
        echo "  backstop reference --config ./backstop.js && backstop test --config ./backstop.js\n\n";

        echo "After reviewing the results, choose an action:\n";
        echo "  [d] Mark as done and continue\n";
        echo "  [s] Skip this scenario\n";
        echo "  [q] Quit auto mode\n";
        echo "Choice: ";

        $line = fgets($handle);
        $choice = trim(strtolower($line));

        switch ($choice) {
            case 'd':
                markCurrentAsDone();
                break;
            case 's':
                skipCurrentScenario();
                break;
            case 'q':
                echo "Exiting auto mode.\n";
                fclose($handle);
                return;
            default:
                echo "Invalid choice. Exiting.\n";
                fclose($handle);
                return;
        }

        echo "\n" . str_repeat("=", 50) . "\n\n";
    }

    fclose($handle);
}

/**
 * Count URLs in a scenario file
 */
function countUrlsInFile($filepath) {
    $content = file_get_contents($filepath);
    preg_match_all('/"label":\s*"[^"]*"/', $content, $matches);
    return count($matches[0]);
}

/**
 * Show help
 */
function showHelp() {
    echo "BackstopJS Scenario Manager\n\n";
    echo "Usage: ddev exec php manage-scenarios.php [command]\n\n";
    echo "Commands:\n";
    echo "  next      Move the next pending scenario to active\n";
    echo "  done      Mark active scenario as done and move to next\n";
    echo "  skip      Skip active scenario (move back to pending) and move to next\n";
    echo "  status    Show current status (default)\n";
    echo "  list      List all scenarios with details\n";
    echo "  reset     Move all scenarios back to pending\n";
    echo "  auto      Start automatic workflow mode (process all scenarios)\n";
    echo "  help      Show this help message\n\n";
    echo "Typical workflow:\n";
    echo "  1. ddev exec php create-backstop-scenarios.php\n";
    echo "  2. ddev exec php manage-scenarios.php next\n";
    echo "  3. backstop reference --config ./backstop.js\n";
    echo "  4. backstop test --config ./backstop.js\n";
    echo "  5. ddev exec php manage-scenarios.php done\n";
    echo "  6. Repeat from step 3 until all scenarios are processed\n";
}
