# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**Current Version:** 1.0.0

## Version Management

This project uses [Semantic Versioning](https://semver.org/):
- **MAJOR** (1.x.x): Incompatible API changes or major rewrites
- **MINOR** (x.1.x): New features, backward-compatible
- **PATCH** (x.x.1): Bug fixes, backward-compatible

### When Making Changes

**ALWAYS update CHANGELOG.md** following [Keep a Changelog](https://keepachangelog.com/) format:

1. **For new features** (bump MINOR version):
   - Add to `### Added` section under `## [Unreleased]`
   - Example: "Added --timeout parameter to crawler.php"

2. **For bug fixes** (bump PATCH version):
   - Add to `### Fixed` section under `## [Unreleased]`
   - Example: "Fixed memory leak in URL duplicate checking"

3. **For breaking changes** (bump MAJOR version):
   - Add to `### Changed` section under `## [Unreleased]`
   - Mark as **[BREAKING]**
   - Example: "[BREAKING] Changed command-line parameter format"

4. **For deprecations**:
   - Add to `### Deprecated` section
   - Example: "Deprecated --max-depth parameter (to be removed in v2.0.0)"

5. **For removals**:
   - Add to `### Removed` section
   - Example: "Removed deprecated --legacy-mode flag"

6. **For security fixes**:
   - Add to `### Security` section
   - Example: "Fixed XSS vulnerability in error output"

### Release Process

When ready to release:
1. Move `## [Unreleased]` entries to new version section with date
2. Update version number in README.md header
3. Update "Current Version" in this file
4. Create git tag: `git tag -a v1.1.0 -m "Release v1.1.0"`
5. Push tag: `git push origin v1.1.0`

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

**Crawler options:**
- `--max-urls=N`: Limit number of URLs (default: 10000)
- `--output=FILE`: Custom output file (default: crawled_urls.csv)
- `--include-params`: Include URLs with query parameters
- `--verbose`: Show detailed error messages during crawling
- `--help`: Show help

**Important crawler features:**
- Streams URLs directly to CSV (no memory issues with large sites)
- Smart URL filtering (tel:, mailto:, javascript:, etc.)
- Detailed error reporting with categorization
- Real-time progress display

2. **Generate Scenario Files**
```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com
```
Creates multiple `scenarioUrls_N.js` files in `scenarios/pending/`.

**Optional: Use custom CSV file**
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
- **Crawler writes URLs in real-time** - safe to interrupt, no data loss
- Crawler automatically filters invalid URLs (tel:, mailto:, javascript:, malformed URLs)
- Error summary shows categorized failures (404, 403, 500, etc.) with examples
- Manual CSV review recommended - check for missed or unwanted URLs
- Test and reference domains are swapped during scenario generation for side-by-side comparison
- Use `manage-scenarios.php auto` for a streamlined interactive workflow