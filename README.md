# BackstopJS Scenario Generator

Automated visual regression testing tool that crawls websites, generates test scenarios, and manages them efficiently in batches.

## Features

- 🕷️ **Automated URL Crawling**: Recursively crawl websites and extract all relevant URLs
- 📦 **Batch Management**: Process large websites in manageable chunks (40 URLs per batch)
- 🎯 **Smart Queue System**: Only test one batch at a time for optimal performance
- 🔄 **Workflow Automation**: Easy-to-use commands for the entire test lifecycle
- 📊 **Progress Tracking**: Always know which scenarios are pending, active, or completed

## Prerequisites

- [DDEV](https://ddev.com/) installed on your system (or PHP 8.2+)
- [Node.js](https://nodejs.org/) installed on your system
- [BackstopJS](https://github.com/garris/BackstopJS) installed globally: `npm install -g backstopjs`

## Quick Start

### 1. Setup
```bash
# Clone and start DDEV
ddev start

# Initialize BackstopJS (creates directory structure)
backstop init
# Note: You can delete the generated backstop.json file
```

### 2. Crawl Your Website
```bash
ddev exec php crawler.php --url https://www.example.com
```
This creates `crawled_urls.csv` with all discovered URLs.

**Optional parameters:**
```bash
# Limit number of URLs
ddev exec php crawler.php --url https://www.example.com --max-urls=500

# Custom output file
ddev exec php crawler.php --url https://www.example.com --output=custom.csv

# Include URLs with query parameters
ddev exec php crawler.php --url https://www.example.com --include-params

# Show help
ddev exec php crawler.php --help
```

### 3. Generate Test Scenarios
```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com
```
This creates scenario files in `scenarios/pending/` directory.

### 4. Process Scenarios

#### Option A: Manual Mode (Step-by-Step)
```bash
# Activate next scenario
ddev exec php manage-scenarios.php next

# Run tests
backstop reference --config ./backstop.js
backstop test --config ./backstop.js

# Review results in browser
backstop openReport

# Mark as done and move to next
ddev exec php manage-scenarios.php done

# Repeat until all scenarios are processed
```

#### Option B: Auto Mode (Interactive)
```bash
ddev exec php manage-scenarios.php auto
```
This guides you through all scenarios interactively.

## Scripts Overview

### 1. crawler.php
Crawls a website and extracts all page URLs.

**Features:**
- Recursive crawling with proper error handling
- User-Agent header to avoid bot blocking
- Smart URL resolution (handles relative paths, ../, ./)
- Filters out files (PDF, images, etc.)
- Excludes URLs with parameters (optional)
- Removes fragments and trailing slashes
- Timeout and connection validation
- Real-time progress display with error tracking

**Usage:**
```bash
# Basic usage
ddev exec php crawler.php --url https://www.example.com

# With options
ddev exec php crawler.php \
  --url https://www.example.com \
  --output=urls.csv \
  --max-urls=1000 \
  --include-params
```

**Options:**
- `--url` (required): Domain to crawl
- `--output`: Custom output file (default: crawled_urls.csv)
- `--max-urls`: Maximum URLs to crawl (default: 10000)
- `--max-depth`: Maximum crawl depth (not yet implemented)
- `--include-params`: Include URLs with query parameters
- `--help`: Show help message

**Output:** CSV file with one URL per line

### 2. create-backstop-scenarios.php
Generates BackstopJS scenario files from the CSV.

**Features:**
- Splits URLs into batches of 40
- Maps reference URLs to test URLs
- Creates organized directory structure
- Validates input and provides helpful error messages

**Usage:**
```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com \
  [--csv=custom_urls.csv]
```

**Options:**
- `--test` (required): Your test domain
- `--reference` (required): The reference/production domain
- `--csv` (optional): Path to CSV file (default: crawled_urls.csv)
- `--help`: Show help message

**Output:** Scenario files in `scenarios/pending/`

### 3. manage-scenarios.php
Manages the scenario workflow and keeps track of progress.

**Commands:**
```bash
# Show current status
ddev exec php manage-scenarios.php status

# Move next scenario to active
ddev exec php manage-scenarios.php next

# Mark current scenario as done
ddev exec php manage-scenarios.php done

# Skip current scenario
ddev exec php manage-scenarios.php skip

# List all scenarios with details
ddev exec php manage-scenarios.php list

# Reset all scenarios to pending
ddev exec php manage-scenarios.php reset

# Interactive auto mode
ddev exec php manage-scenarios.php auto

# Show help
ddev exec php manage-scenarios.php help
```

**Scenario States:**
- **pending**: Waiting to be tested
- **active**: Currently being tested (only one at a time)
- **done**: Completed and archived with timestamp

### 4. backstop.js
BackstopJS configuration that loads scenarios from `scenarios/active/`.

**Key Settings:**
- `removeSelectors`: Elements to remove (e.g., cookie banners)
- `delay`: Wait time before screenshots (default: 5000ms)
- `misMatchThreshold`: Acceptable difference percentage (default: 10%)
- `viewports`: Phone, tablet, desktop
- `asyncCaptureLimit`: Parallel screenshots (default: 5)

## Configuration

### Customize Test Parameters
Edit `backstop.js` to adjust:
- `removeSelectors`: Hide cookie banners, popups, etc.
- `delay`: Increase for slow-loading pages
- `misMatchThreshold`: Adjust sensitivity
- `viewports`: Add/remove screen sizes
- `hideSelectors`: Temporarily hide elements

Example:
```javascript
"removeSelectors": ["#CybotCookiebotDialog", ".cookie-banner"],
"delay": 5000,
"misMatchThreshold": 10,
```

## Project-Based Testing

Test different projects using branches:

```bash
# Create project branch
git checkout -b projectname

# Adjust backstop.js settings for this project
# (removeSelectors, delay, etc.)

# Run the workflow
ddev exec php crawler.php --url https://www.project.com
ddev exec php create-backstop-scenarios.php \
  --test=https://project.ddev.site \
  --reference=https://www.project.com

# Process all scenarios
ddev exec php manage-scenarios.php auto

# Optional: Commit results
git add . && git commit -m "Tested projectname"

# Return to main branch
git checkout main

# Clean up branch if no longer needed
git branch -D projectname
```

## Directory Structure

```
.
├── crawler.php                      # URL crawler script
├── create-backstop-scenarios.php    # Scenario generator
├── manage-scenarios.php             # Workflow manager
├── backstop.js                      # BackstopJS config
├── crawled_urls.csv                 # Crawled URLs (generated)
│
├── scenarios/
│   ├── pending/                     # Generated scenarios (waiting)
│   ├── active/                      # Current scenario being tested
│   └── done/                        # Completed scenarios (archived)
│
└── backstop_data/
    ├── bitmaps_reference/           # Reference screenshots
    ├── bitmaps_test/                # Test screenshots & diffs
    ├── html_report/                 # Visual comparison reports
    └── engine_scripts/              # Puppeteer/Playwright scripts
```

## Tips & Best Practices

1. **Review Crawled URLs**: Always check `crawled_urls.csv` before generating scenarios. The crawler might miss some URLs or include unwanted ones.

2. **Adjust for Your Site**: Each website is different. Tune `backstop.js` settings:
   - Add cookie banners to `removeSelectors`
   - Increase `delay` for AJAX-heavy pages
   - Adjust `misMatchThreshold` based on acceptable variance

3. **Use Auto Mode**: The `manage-scenarios.php auto` command provides the smoothest workflow for processing many scenarios.

4. **Monitor Performance**: Process one scenario at a time to avoid memory issues and slow performance.

5. **Archive Results**: Completed scenarios are timestamped and stored in `scenarios/done/` for future reference.

6. **Alternative to Crawler**: You can use tools like [Screaming Frog SEO Spider](https://www.screamingfrogseoseo.com/) to generate the CSV file instead of using `crawler.php`.

## Troubleshooting

**No scenarios found in active directory:**
```bash
ddev exec php manage-scenarios.php next
```

**Need to restart from scratch:**
```bash
ddev exec php manage-scenarios.php reset
```

**Want to skip a problematic scenario:**
```bash
ddev exec php manage-scenarios.php skip
```

**Check current progress:**
```bash
ddev exec php manage-scenarios.php status
```

## Resources

- [BackstopJS Documentation](https://github.com/garris/BackstopJS)
- [DDEV Documentation](https://ddev.readthedocs.io/)
- [Visual Regression Testing Guide](https://www.browserstack.com/guide/visual-regression-testing)

## License

This project is provided as-is for visual regression testing purposes.
