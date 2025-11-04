# BackstopJS Scenario Generator

**Version 1.1.1**

Automated visual regression testing tool that crawls websites, generates test scenarios, and manages them efficiently in batches.

## What Does This Tool Do?

**The Simple Explanation:**
Imagine you have a website running locally on your computer (e.g., `http://mysite.ddev.site`) and you want to make sure it looks **exactly** like your live website (e.g., `https://www.mysite.com`). This tool:

1. **Collects all pages** from your live website
2. **Takes screenshots** of both the live site AND your local site
3. **Compares them side-by-side** and shows you the differences
4. **Manages everything** in small batches so your computer doesn't explode

## When do you need BackstopJS as a TYPO3 integrator?

Visual Regression Testing is especially important for:
- TYPO3 Core upgrades (e.g., from v12 to v13)
- Extension updates with template changes
- Template migrations (e.g., from Mask to Content Blocks)
- Responsive design adjustments
- Large TYPO3 instances with 100+ pages

**Rule of thumb:** For 30+ pages or critical updates, the effort is worthwhile.

**Real-World Example:**
```
You're upgrading www.example.com locally at example.ddev.site
→ This tool visits every page on www.example.com
→ Takes screenshots of www.example.com (these are your "reference" images)
→ Takes screenshots of example.ddev.site (these are your "test" images)
→ Shows you exactly what's different between them
```

## Features

- 🗺️ **Sitemap Parsing**: Fast URL collection from sitemap.xml files (recommended)
- 🕷️ **Automated URL Crawling**: Recursively crawl websites and extract all relevant URLs
- 📦 **Batch Management**: Process large websites in manageable chunks (40 URLs per batch)
- 🎯 **Smart Queue System**: Only test one batch at a time for optimal performance
- 🔄 **Workflow Automation**: Easy-to-use commands for the entire test lifecycle
- 📊 **Progress Tracking**: Always know which scenarios are pending, active, or completed

## Prerequisites

**You Need:**

1. **DDEV** (recommended) - [Install here](https://ddev.com/)
   - OR PHP 8.2+ if you want to run scripts without DDEV
   - This README assumes you use DDEV (all commands use `ddev exec`)

2. **Node.js** - [Install here](https://nodejs.org/)

3. **BackstopJS** - Install globally:
   ```bash
   npm install -g backstopjs
   ```

**Quick Check - Do You Have Everything?**
```bash
ddev version      # Should show DDEV version
node --version    # Should show Node version
backstop --version # Should show BackstopJS version
```

## Quick Start (The Complete Workflow)

**Understanding the Workflow:**
```
Step 1: Setup          → Prepare your environment
Step 2: Collect URLs   → Get list of pages from your website
Step 3: Generate Tests → Create test scenarios
Step 4: Run Tests      → Take screenshots and compare
Step 5: Review         → Look at the differences
```

### Step 1: Setup

```bash
# Clone this repository
git clone https://github.com/wwagner-net/create-backstop-scenarios.git
cd create-backstop-scenarios

# Start DDEV (this starts a PHP environment)
ddev start

# Initialize BackstopJS (creates folders for screenshots)
backstop init
```

**What just happened?**
- BackstopJS created a `backstop_data/` folder where screenshots will be saved
- It also created a `backstop.json` file - you can delete this, we use `backstop.js` instead

---

### Step 2: Collect URLs (Get All Pages From Your Website)

**Which method should I use?**
- ✅ **Use Sitemap** if your website has a sitemap.xml (most modern sites do)
- ⚠️ **Use Crawler** if there's no sitemap or it's incomplete

#### Method 1: Parse Sitemap (RECOMMENDED - Takes Seconds!)

```bash
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml
```

**What this does:**
- Reads your sitemap.xml file
- Extracts all URLs listed there
- Saves them to `crawled_urls.txt`

**Why is this better?**
- ⚡ Much faster (seconds vs minutes)
- 📋 More complete (gets ALL pages the site owner wants indexed)
- 🚀 Doesn't stress your server

**How do I find my sitemap?**
Most websites have it at one of these locations:
- `https://www.example.com/sitemap.xml`
- `https://www.example.com/sitemap_index.xml`
- Check `https://www.example.com/robots.txt` for the sitemap location

#### Method 2: Crawl Website (Slower, But Works Without Sitemap)

```bash
ddev exec php crawler.php --url https://www.example.com
```

**What this does:**
- Visits your homepage
- Follows every link it finds
- Visits those pages and follows their links (recursive)
- Saves all discovered URLs to `crawled_urls.txt`

**When to use this:**
- No sitemap available
- You want to test what a real user would discover by clicking around

**Common Options (Both Methods):**
```bash
# Limit number of URLs (useful for testing)
--max-urls=500

# Custom output filename
--output=my_urls.txt

# Include URLs with ?parameters (usually you don't want this)
--include-params

# Show detailed errors (crawler only)
--verbose
```

**Example:**
```bash
ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml --max-urls=100
```

**After running:** Check the generated `crawled_urls.txt` file. It should contain one URL per line.

---

### Step 3: Generate Test Scenarios (Tell The Tool What To Compare)

```bash
ddev exec php create-backstop-scenarios.php \
  --test=https://example.ddev.site \
  --reference=https://www.example.com
```

**IMPORTANT - Understanding the Parameters:**

- `--reference`: This is your **live/production website** (the "correct" version)
- `--test`: This is your **local/staging site** (the version you want to check)

**Real Example:**
```bash
# I want to compare my local DDEV site against the live production site
ddev exec php create-backstop-scenarios.php \
  --test=https://mysite.ddev.site \
  --reference=https://www.mysite.com
```

**What this does:**
- Reads `crawled_urls.txt`
- Creates test scenarios (40 URLs per batch)
- Saves them in `scenarios/pending/` folder

**Why batches of 40?** Large websites can have thousands of pages. Testing 1000 pages at once would crash your computer. Batches keep things manageable.

---

### Step 4: Run The Tests (The Actual Screenshot Comparison)

**You Have Two Options:**

#### Option A: Manual Mode (Full Control, Step-by-Step)

Best for: First-time users, when you want to see each batch

```bash
# 1. Activate the next batch (moves one batch from pending → active)
ddev exec php manage-scenarios.php next

# 2. Take reference screenshots (from your LIVE site)
backstop reference --config ./backstop.js

# 3. Take test screenshots (from your LOCAL site) and compare
backstop test --config ./backstop.js

# 4. Open the comparison report in your browser
backstop openReport

# 5. Mark this batch as done and activate the next one
ddev exec php manage-scenarios.php done

# Repeat steps 2-5 until all batches are done
```

**What each command does:**
- `backstop reference`: Takes screenshots of your live site (these are the "correct" images)
- `backstop test`: Takes screenshots of your local site and compares them to reference
- `backstop openReport`: Opens a visual report showing differences side-by-side

#### Option B: Auto Mode (Faster, Guided)

Best for: When you know what you're doing, processing many batches

```bash
ddev exec php manage-scenarios.php auto
```

**What this does:**
- Shows you the current batch
- Waits for you to run `backstop reference` and `backstop test`
- Asks if you want to continue to the next batch
- Automatically manages the queue for you

---

### Step 5: Review Results

After running `backstop test`, BackstopJS creates an HTML report.

**Open it with:**
```bash
backstop openReport
```

**What you'll see:**
- Side-by-side screenshots (reference vs test)
- Differences highlighted in pink/magenta
- Pass/fail status for each page
- Different viewports (phone, tablet, desktop)

**Understanding the results:**
- ✅ **Green/Pass**: Pages look identical (or differences below threshold)
- ❌ **Red/Fail**: Pages look different (check if intentional or a bug)

---

## How Does It Actually Work? (Behind The Scenes)

**The Technical Flow:**

1. **URL Collection Phase:**
   ```
   crawler.php reads sitemap.xml
   → Extracts all <loc> URLs
   → Filters out invalid URLs (files, tel:, mailto:, etc.)
   → Writes to crawled_urls.txt
   ```

2. **Scenario Generation Phase:**
   ```
   create-backstop-scenarios.php reads crawled_urls.txt
   → Splits into chunks of 40 URLs
   → Creates scenarioUrls_1.js, scenarioUrls_2.js, etc.
   → Each scenario contains URL pairs:
     - referenceUrl: https://www.example.com/page
     - url: https://example.ddev.site/page
   → Saves to scenarios/pending/
   ```

3. **Scenario Management Phase:**
   ```
   manage-scenarios.php moves files between folders:

   scenarios/pending/     → Waiting to be tested
   scenarios/active/      → Currently being tested (only 1 at a time!)
   scenarios/done/        → Archived with timestamp
   ```

4. **BackstopJS Execution Phase:**
   ```
   backstop reference:
   → Loads scenarios/active/scenarioUrls_N.js
   → Opens referenceUrl in a headless browser (Puppeteer)
   → Takes screenshots at 3 viewports (phone, tablet, desktop)
   → Saves to backstop_data/bitmaps_reference/

   backstop test:
   → Loads scenarios/active/scenarioUrls_N.js
   → Opens test url in headless browser
   → Takes screenshots at 3 viewports
   → Compares pixel-by-pixel with reference images
   → Generates HTML report with differences highlighted
   ```

**Why Only One Active Scenario?**
- Testing 40 pages × 3 viewports = 120 screenshots
- Testing 1000 pages at once = 3000 screenshots = computer crash
- One batch at a time = stable performance

**What Are Those .js Files?**
Each `scenarioUrls_N.js` file looks like this:
```javascript
module.exports = [
  {
    label: "homepage",
    referenceUrl: "https://www.example.com/",
    url: "https://example.ddev.site/"
  },
  {
    label: "about",
    referenceUrl: "https://www.example.com/about",
    url: "https://example.ddev.site/about"
  }
  // ... up to 40 URLs
];
```

---

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

### For Beginners

1. **Start Small**
   - Use `--max-urls=10` for your first test
   - Test the workflow with just 10 pages before running the full site
   - Example: `ddev exec php crawler.php --sitemap https://www.example.com/sitemap.xml --max-urls=10`

2. **Check Your URLs File**
   - Open `crawled_urls.txt` after crawling
   - Make sure the URLs look correct
   - Each line should be a complete URL starting with `http://` or `https://`

3. **Understand Test vs Reference**
   - **Reference** = The "correct" version (usually your live site)
   - **Test** = The version you're checking (usually your local site)
   - Think of it like: "I want to test my local site against the reference (live site)"

4. **What If Everything Fails?**
   - Cookie banners can cause false failures
   - Add them to `removeSelectors` in `backstop.js`
   - Example: `"removeSelectors": ["#cookie-banner", ".gdpr-notice"]`

5. **Crawler Taking Forever?**
   - Stop it with Ctrl+C (your URLs are already saved!)
   - Use `--max-urls=500` to limit it
   - Or switch to `--sitemap` mode (much faster!)

### For Advanced Users

6. **Crawler is Safe to Interrupt**: URLs are written in real-time. If you need to stop crawling (Ctrl+C), all discovered URLs are already saved.

7. **Understanding Error Rates**: Check the error summary after crawling:
   - < 10% error rate: Normal, usually harmless broken links
   - 10-25% error rate: Moderate, some broken links to fix
   - > 25% error rate: High, possible connectivity or server issues

8. **Fine-Tune Test Parameters**: Edit `backstop.js` to adjust:
   - `removeSelectors`: Hide cookie banners, popups, chat widgets
   - `delay`: Increase for slow-loading or JavaScript-heavy pages (default: 5000ms)
   - `misMatchThreshold`: Lower = stricter, higher = more forgiving (default: 10%)
   - `hideSelectors`: Temporarily hide dynamic elements (like timestamps)

9. **Use Auto Mode for Bulk Testing**: The `manage-scenarios.php auto` command provides the smoothest workflow for processing many batches.

10. **Alternative URL Collection**: You can use tools like [Screaming Frog SEO Spider](https://www.screamingfrog.co.uk/) to generate the URLs file instead of using `crawler.php`.

### Common Mistakes

❌ **Wrong:** Using your local site as `--reference`
```bash
# DON'T DO THIS
ddev exec php create-backstop-scenarios.php \
  --reference=https://local.ddev.site \
  --test=https://www.production.com
```

✅ **Correct:** Live site is always the reference
```bash
# DO THIS
ddev exec php create-backstop-scenarios.php \
  --reference=https://www.production.com \
  --test=https://local.ddev.site
```

---

❌ **Wrong:** Testing 1000 pages at once (by modifying chunk size)

✅ **Correct:** Stick with 40 URLs per batch (or your computer will struggle)

---

❌ **Wrong:** Running `backstop test` without `backstop reference` first

✅ **Correct:** Always run `backstop reference` first to create baseline images

## Troubleshooting

### Common Problems & Solutions

**Problem: "No scenarios found in active directory"**
```bash
# Solution: Activate the next batch
ddev exec php manage-scenarios.php next
```

**Problem: BackstopJS says "No reference images found"**
```bash
# Solution: You need to run backstop reference first
backstop reference --config ./backstop.js

# Then run the test
backstop test --config ./backstop.js
```

**Problem: All tests are failing**
- Cookie banners or popups are probably causing differences
- Add them to `removeSelectors` in `backstop.js`
- Example: `"removeSelectors": ["#CybotCookiebotDialog", "#cookie-consent"]`

**Problem: Crawler finds no URLs or very few**
- Check if the URL is correct and accessible
- Try with `--verbose` to see errors
- Make sure you're not being blocked (some sites block crawlers)

**Problem: "Cannot connect" or timeout errors**
- Your reference or test site might be down
- Check if the URLs are accessible in a browser
- Increase timeout in `backstop.js` if pages are slow

**Problem: Tests are super slow**
- Reduce `asyncCaptureLimit` in `backstop.js` (default: 5)
- Lower values = fewer parallel screenshots = slower but more stable
- Higher values = faster but might crash

**Problem: Want to start over completely**
```bash
# Reset all scenarios back to pending
ddev exec php manage-scenarios.php reset
```

**Problem: One batch is problematic, want to skip it**
```bash
# Skip current scenario and move to next
ddev exec php manage-scenarios.php skip
```

**Problem: Lost track of where I am**
```bash
# Check current progress
ddev exec php manage-scenarios.php status

# List all scenarios
ddev exec php manage-scenarios.php list
```

**Problem: DDEV commands not working**
```bash
# Make sure DDEV is running
ddev start

# If you don't use DDEV, remove "ddev exec" from all commands
# Instead of: ddev exec php crawler.php --url ...
# Use: php crawler.php --url ...
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
