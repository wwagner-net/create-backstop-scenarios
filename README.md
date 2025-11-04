# BackstopJS Scenario Generator

**Version 1.1.1**

Automated visual regression testing tool that crawls websites, generates test scenarios, and manages them efficiently in batches.

## Features

- 🗺️ **Sitemap Parsing**: Fast URL collection from sitemap.xml files (recommended)
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
# Clone the repository
git clone https://github.com/wwagner-net/create-backstop-scenarios.git
cd create-backstop-scenarios

# Start DDEV
ddev start

# Initialize BackstopJS (creates directory structure)
backstop init
# Note: You can delete the generated backstop.json file
```

### 2. Collect URLs

#### Option A: Parse Sitemap (Recommended - Faster!)
```bash
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml
```
This parses the sitemap.xml and creates `crawled_urls.txt` with all URLs.

**Advantages:**
- Much faster (seconds instead of minutes)
- More complete (all URLs the site owner considers important)
- No load on the server

#### Option B: Crawl Website
```bash
ddev exec php crawler.php --url https://www.example.com
```
This crawls the website recursively and creates `crawled_urls.txt` with all discovered URLs.

**Optional parameters (both modes):**
```bash
# Limit number of URLs
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml --max-urls=500

# Custom output file
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml --output=custom.txt

# Include URLs with query parameters
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml --include-params

# Show detailed error messages (crawler mode only)
ddev exec php crawler.php --url https://www.example.com --verbose

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
Collects URLs either by parsing a sitemap.xml or by crawling a website.

**Two Modes:**

#### Sitemap Mode (Recommended)
Parses sitemap.xml files - much faster than crawling!

**Features:**
- Parses regular sitemaps and sitemap index files
- Automatically follows and processes all sub-sitemaps
- Real-time file streaming (no memory issues)
- Applies same filtering as crawler mode
- Ideal for large websites with comprehensive sitemaps

**Usage:**
```bash
# Basic usage
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml

# With options
ddev exec php crawler.php \
  --sitemap https://www.example.com/sitemap.xml \
  --output=urls.txt \
  --max-urls=1000 \
  --include-params
```

#### Crawler Mode
Recursively crawls websites and extracts URLs.

**Features:**
- Recursive crawling with proper error handling
- User-Agent header to avoid bot blocking
- Smart URL resolution (handles relative paths, ../, ./)
- Filters out files (PDF, images, etc.)
- Advanced URL filtering (tel:, mailto:, javascript:, malformed URLs)
- Excludes URLs with parameters (optional)
- Removes fragments and trailing slashes
- Timeout and connection validation
- Real-time progress display with error tracking
- **Streaming file writing** - writes URLs immediately (no memory issues)
- Detailed error summary with categorization (404, 403, 500, etc.)
- Safe to interrupt - no data loss

**Usage:**
```bash
# Basic usage
ddev exec php crawler.php --url https://www.example.com

# With options
ddev exec php crawler.php \
  --url https://www.example.com \
  --output=urls.txt \
  --max-urls=1000 \
  --include-params \
  --verbose
```

**Options:**
- `--url` or `--sitemap` (required, choose one): URL to crawl or sitemap to parse
- `--output`: Custom output file (default: crawled_urls.txt)
- `--max-urls`: Maximum URLs to process (default: 10000)
- `--max-depth`: Maximum crawl depth (crawler only, not yet implemented)
- `--include-params`: Include URLs with query parameters
- `--verbose`: Show detailed error messages (crawler mode only)
- `--help`: Show help message

**Output:** Text file with one URL per line (written in real-time)

**Error Handling:**
After crawling, you'll see a detailed error summary:
```
--- Error Summary ---

Page not found (404): 8 page(s)
  - https://example.com/old-page
  ... and 5 more

Error rate: 5.7% (6 errors out of 106 pages)
ℹ️  Small number of errors - this is normal for most websites.
```

Common error types:
- **404 (Page not found)**: Broken links or deleted pages - usually harmless
- **403 (Access forbidden)**: Protected pages (admin, private areas)
- **500 (Server error)**: Server-side issues - might need investigation
- **Connection failed**: Timeout or unreachable pages

### 2. create-backstop-scenarios.php
Generates BackstopJS scenario files from the URLs file.

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
  [--urls=custom_urls.txt]
```

**Options:**
- `--test` (required): Your test domain
- `--reference` (required): The reference/production domain
- `--urls` (optional): Path to URLs file (default: crawled_urls.txt)
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
├── crawled_urls.txt                 # Crawled URLs (generated)
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

1. **Review Crawled URLs**: Always check `crawled_urls.txt` before generating scenarios. The crawler automatically filters invalid URLs, but manual review is still recommended.

2. **Crawler is Safe to Interrupt**: URLs are written in real-time. If you need to stop crawling (Ctrl+C), all discovered URLs are already saved.

3. **Understanding Errors**: Check the error summary after crawling:
   - < 10% error rate: Normal, usually harmless broken links
   - 10-25% error rate: Moderate, some broken links to fix
   - > 25% error rate: High, possible connectivity or server issues

4. **Adjust for Your Site**: Each website is different. Tune `backstop.js` settings:
   - Add cookie banners to `removeSelectors`
   - Increase `delay` for AJAX-heavy pages
   - Adjust `misMatchThreshold` based on acceptable variance

5. **Use Auto Mode**: The `manage-scenarios.php auto` command provides the smoothest workflow for processing many scenarios.

6. **Monitor Performance**: Process one scenario at a time (40 URLs) to avoid memory issues and slow performance.

7. **Archive Results**: Completed scenarios are timestamped and stored in `scenarios/done/` for future reference.

8. **Prefer Sitemap Parsing**: If the website has a sitemap.xml, use `--sitemap` instead of `--url` for much faster URL collection. Most modern websites have sitemaps at `/sitemap.xml` or linked from `/robots.txt`.

9. **Alternative Tools**: You can also use tools like [Screaming Frog SEO Spider](https://www.screamingfrog.co.uk/) to generate the URLs file instead of using `crawler.php`.

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

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes and version updates.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

When contributing, please:
1. Update CHANGELOG.md following [Keep a Changelog](https://keepachangelog.com/) format
2. Follow [Semantic Versioning](https://semver.org/) for version numbers
3. Test your changes thoroughly
4. Update documentation as needed

## Author

**Wolfgang Wagner**

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Wolfgang Wagner
