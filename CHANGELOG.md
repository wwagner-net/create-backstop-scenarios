# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
### Changed
### Deprecated
### Removed
### Fixed
### Security

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
