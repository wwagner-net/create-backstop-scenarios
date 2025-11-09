# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- **Interactive Setup Wizard (setup.php)**
  - Step-by-step guided configuration process
  - User-friendly prompts for all settings
  - Input validation and helpful explanations
  - Colorized terminal output for better UX
  - Creates properly formatted config.json automatically
  - Asks for project ID, chunk size, selectors, delays, thresholds, viewports
  - Advanced settings (asyncCaptureLimit, debug mode)
  - Warns if config.json already exists before overwriting
  - Provides "Next Steps" guide after completion

- **Configurable Chunk Size**
  - New `chunkSize` setting in config.json (default: 40)
  - Controls how many URLs are grouped per scenario batch file
  - Configurable via setup.php wizard
  - create-backstop-scenarios.php now reads chunk size from config.json
  - Allows optimization for different system resources (lower for limited memory, higher for faster processing)
  - Displays chunk size in configuration output

- **External configuration system via config.json**
  - New `config.example.json` template file with all configurable options
  - Configuration documentation with detailed explanations
  - Automatic config loading in `backstop.js` with fallback to defaults
  - Support for project-specific settings without modifying core files
  - Configuration options: projectId, removeSelectors, hideSelectors, delay, misMatchThreshold, viewports, engine settings

- **Contributing guidelines and community templates**
  - Comprehensive `CONTRIBUTING.md` with contribution workflow
  - `.github/PULL_REQUEST_TEMPLATE.md` for standardized pull requests
  - `.github/ISSUE_TEMPLATE/bug_report.md` for bug reports
  - `.github/ISSUE_TEMPLATE/feature_request.md` for feature requests
  - `.github/ISSUE_TEMPLATE/config.yml` for issue template configuration
  - Code of Conduct section in CONTRIBUTING.md
  - Detailed testing checklist for contributors

- **Enhanced .gitignore**
  - Ignores `config.json` (project-specific, created from example)
  - Ignores `crawled_urls.txt` and other generated text files
  - Ignores `scenarios/` directory (generated scenario files)

### Changed
- **backstop.js**: Refactored to load configuration from external `config.json` file
  - Moved all configurable values to config system
  - Added helpful console warnings when config.json is missing
  - Maintains backward compatibility with default values
  - Configuration now separated from code for easier updates

- **README.md**: Expanded documentation
  - Added comprehensive "Configuration" section explaining config.json usage
  - Added configuration options reference table
  - Updated "Quick Start" to include config.json setup
  - Enhanced "Contributing" section with links to new templates
  - Added tips for common configuration scenarios

### Improved
- Developer experience: No more merge conflicts on `backstop.js` updates
- Project workflow: Easier to maintain project-specific settings
- Collaboration: Standardized contribution process
- Documentation: Clearer setup and configuration instructions

## [1.1.1] - 2025-11-03

### Changed
- Changed output format from CSV to TXT for URL storage (simpler, more readable)
- Updated `crawler.php` to write plain text files (one URL per line)
- Updated `create-backstop-scenarios.php` to read plain text files
- Renamed `--csv` parameter to `--urls` in create-backstop-scenarios.php
- Changed default filename from `crawled_urls.csv` to `crawled_urls.txt`
- Updated `.gitignore` to include generated files and scenario directories

## [1.1.0] - 2025-10-31

### Added
- **Sitemap parsing mode for crawler.php**
  - New `--sitemap=URL` parameter as alternative to `--url` crawling
  - Parses sitemap.xml files (much faster than crawling)
  - Supports both regular sitemaps and sitemap index files
  - Automatically follows and parses all referenced sub-sitemaps
  - Applies same filtering as crawler (file URLs, special protocols, query parameters)
  - Real-time CSV streaming (same as crawler mode)
  - Ideal for large websites with comprehensive sitemaps

### Changed
- Updated help message and documentation to include sitemap mode
- CLI argument validation now ensures only one mode (--url or --sitemap) is used

## [1.0.0] - 2025-10-30

### Added
- Initial release of BackstopJS Scenario Generator
- **crawler.php**: Automated URL crawling with smart filtering
  - Recursive website crawling with proper error handling
  - Real-time CSV streaming (memory-efficient for large sites)
  - Advanced URL filtering (tel:, mailto:, javascript:, malformed URLs)
  - Detailed error reporting with categorization (404, 403, 500, etc.)
  - Performance optimization using hash tables (O(1) lookups)
  - Verbose mode for debugging
  - User-Agent header to avoid bot blocking
  - Connection validation and timeout handling
  - Support for relative URLs, fragments, and query parameters

- **create-backstop-scenarios.php**: Scenario file generation
  - Reads URLs from CSV and generates BackstopJS scenario files
  - Chunks URLs into batches of 40 for manageable testing
  - Command-line parameters for test and reference domains
  - Automatic directory structure creation
  - URL validation and error handling

- **manage-scenarios.php**: Workflow management system
  - Three-state scenario management (pending/active/done)
  - Commands: next, done, skip, status, list, reset
  - Interactive auto mode for batch processing
  - Timestamp-based archiving of completed scenarios
  - Progress tracking and status reporting

- **backstop.js**: BackstopJS configuration
  - Dynamic scenario loading from active directory
  - Support for multiple viewports (phone, tablet, desktop)
  - Configurable selectors, delays, and thresholds
  - Integration with Puppeteer engine

- **Documentation**
  - Comprehensive README.md with quick start guide
  - CLAUDE.md for AI-assisted development
  - Examples and troubleshooting section
  - Tips and best practices

### Features
- Handles unlimited URLs without memory issues (streaming architecture)
- Safe to interrupt - no data loss during crawling
- Project-based workflow using Git branches
- DDEV integration for consistent PHP environment
- Detailed error summaries with actionable insights

### Technical Details
- PHP 8.2+ required
- Node.js and BackstopJS integration
- Streaming CSV writing for scalability
- Hash table optimization for performance
- Real-time progress display

## Version History

[1.1.0]: https://github.com/yourusername/create-backstop-scenarios/releases/tag/v1.1.0
[1.0.0]: https://github.com/yourusername/create-backstop-scenarios/releases/tag/v1.0.0
