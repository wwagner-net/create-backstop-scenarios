# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a BackstopJS scenario generator tool that automates visual regression testing by crawling websites and generating test scenarios. It consists of PHP scripts for URL collection and scenario generation, plus a Node.js-based BackstopJS configuration.

## Architecture

### Four-Phase Workflow with Queue Management

1. **URL Collection (crawler.php)**
   - Crawls a reference domain recursively
   - Extracts and filters URLs (excludes files, parameters, anchors, tel/mailto links)
   - Outputs to `crawled_urls.csv`

2. **Scenario Generation (create-backstop-scenarios.php)**
   - Reads URLs from CSV file
   - Chunks URLs into groups of 40
   - Generates `scenarioUrls_N.js` files in `scenarios/pending/`
   - Maps reference URLs to test URLs by domain replacement
   - Creates directory structure: `pending/`, `active/`, `done/`

3. **Scenario Management (manage-scenarios.php)**
   - Moves scenarios through three states: pending → active → done
   - Ensures only one scenario is active at a time for performance
   - Tracks progress and provides status information
   - Supports automatic workflow mode

4. **Test Execution (backstop.js)**
   - Dynamically loads scenario files only from `scenarios/active/`
   - Generates BackstopJS scenarios with configured parameters
   - Each scenario includes both test URL and reference URL

### Scenario File States

- **pending**: Newly generated files waiting to be tested
- **active**: Currently being tested (only one at a time)
- **done**: Completed tests, archived with timestamp

### Key Configuration Points

**backstop.js:**
- `removeSelectors`: Array of CSS selectors to remove before screenshots (e.g., cookie banners)
- `delay`: Wait time in milliseconds before capture (default: 5000ms)
- `misMatchThreshold`: Acceptable difference percentage (default: 10%)
- `viewports`: Phone (320x480), tablet (1024x768), desktop (1280x1024)
- `asyncCaptureLimit`: Parallel screenshot limit (default: 5)
- `engine`: Uses Puppeteer with `--no-sandbox` flag

**create-backstop-scenarios.php:**
- `--test`: The domain under test (e.g., DDEV local site)
- `--reference`: The production/reference domain
- `--csv`: Optional path to CSV file (default: crawled_urls.csv)
- URL chunking size: 40 URLs per file

## Common Commands

### Initial Setup
```bash
ddev start
backstop init  # Creates backstop_data structure (backstop.json can be deleted)
```

### Complete Workflow (Recommended)

1. **Crawl Reference Domain**
```bash
ddev exec php crawler.php --url https://referencedomain.com
```
Creates `crawled_urls.csv` in the root directory.

2. **Generate Scenario Files**
```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com
```
Creates multiple `scenarioUrls_N.js` files in `scenarios/pending/`.

Optional: Specify a different CSV file:
```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com \
  --csv=custom_urls.csv
```

3. **Activate First Scenario**
```bash
ddev exec php manage-scenarios.php next
```
Moves the next pending scenario to `scenarios/active/`.

4. **Run BackstopJS Tests**
```bash
backstop reference --config ./backstop.js
backstop test --config ./backstop.js
```

5. **Mark as Done and Continue**
```bash
ddev exec php manage-scenarios.php done
```
Archives the current scenario and automatically activates the next one.

6. **Repeat steps 4-5** until all scenarios are processed.

### Scenario Management Commands

```bash
# Show current status
ddev exec php manage-scenarios.php status

# List all scenarios with details
ddev exec php manage-scenarios.php list

# Skip current scenario (move back to pending)
ddev exec php manage-scenarios.php skip

# Reset all scenarios back to pending
ddev exec php manage-scenarios.php reset

# Automatic mode (process all scenarios interactively)
ddev exec php manage-scenarios.php auto
```

### Open Test Reports
```bash
backstop openReport
```

## Development Environment

**DDEV Configuration:**
- Project type: PHP 8.2
- Webserver: nginx-fpm
- Database: MariaDB 10.11 (included but not actively used)
- Access: `https://create-backstop-scenarios.ddev.site`

**No package.json:** BackstopJS should be installed globally or via DDEV.

## Project-Specific Workflow

When testing a specific project:
1. Create project branch: `git checkout -b projectname`
2. Adjust test parameters in `backstop.js` (removeSelectors, delay, etc.)
3. Run crawler with reference domain
4. Generate scenarios with `--test` and `--reference` parameters
5. Follow the scenario management workflow
6. Optionally commit results: `git add . && git commit -m "Tested projectname"`
7. Return to main: `git checkout main`
8. Clean up: `git branch -D projectname`

## File Structure

- **scenarios/pending/**: Newly generated scenario files waiting to be tested
- **scenarios/active/**: Currently active scenario file (only one at a time)
- **scenarios/done/**: Archived completed scenarios with timestamps
- **backstop_data/bitmaps_reference/**: Reference screenshots
- **backstop_data/bitmaps_test/**: Test run screenshots and diffs
- **backstop_data/html_report/**: HTML comparison reports
- **backstop_data/engine_scripts/**: Puppeteer/Playwright hooks and cookie files

## Important Notes

- **Scenario files are managed in three directories** (pending/active/done) to ensure performance
- Only one scenario file is active at a time, preventing memory issues with large test sets
- The `backstop.js` configuration only loads files from `scenarios/active/`
- Completed scenarios are archived with timestamps in `scenarios/done/`
- Manual CSV review is recommended after crawling, as the crawler may miss some URLs
- The crawler filters out file extensions, but edge cases may require manual CSV cleanup
- Test and reference domains are swapped during scenario generation for side-by-side comparison
- Use `manage-scenarios.php auto` for a streamlined interactive workflow