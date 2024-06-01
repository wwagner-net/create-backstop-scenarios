
# BackstopJS Scenario Generator

This repository contains scripts for automatically generating BackstopJS scenarios from a CSV file with URLs.

## Prerequisites

- PHP or DDEV is installed on your system.
- Node.js is installed on your system.
- BackstopJS is installed on your system.

## Files

1. **PHP Script (create-backstop-scenarios.php)**
2. **BackstopJS Configuration File (backstop.js)**
3. **PHP Crawler Script (crawler.php)**

### 1. PHP Crawler Script (crawler.php)

This script crawls the specified reference domain, extracts all relevant URLs, and saves them in a CSV file. It filters out links to files, JavaScript, tel:, mailto:, and URLs with parameters.

It can be used as an alternative to tools like the Screaming Frog SEO Spider.

**Important:** The generated CSV file should be reviewed and cleaned if necessary. It is also possible that the list of URLs is not complete.

### 2. PHP Script (create-backstop-scenarios.php)

This script reads the URLs from a CSV file, divides them into blocks of 40 URLs each, and generates JavaScript files containing these URLs.

### 3. BackstopJS Configuration File (backstop.js)
This file imports the generated JavaScript files and uses the URLs to configure the BackstopJS scenarios.

## Using the Scripts

1. Optionally, run the PHP script `crawler.php` to create a CSV file with a list of reference URLs.
```shell
ddev exec php crawler.php
```
The collected URLs will be saved in the file `crawled_urls.csv`. You can then manually check and clean this file before using it for the tests.

2. Generate a CSV file: If you do not use `crawler.php`, you can use a tool like the "Screaming Frog SEO Spider" to generate a CSV file with URLs. Make sure the file contains only one column with valid URLs.
3. Adjust various parameters in the `backstop.js` file if necessary, such as removeSelectors, delay, etc.
4. Run the PHP script: Execute the PHP script to generate the JavaScript files.
```shell
ddev exec php create-backstop-scenarios.php
```
5. Run BackstopJS scenarios: Execute the BackstopJS commands to create the reference images and start the tests.
```shell
backstop reference --config ./backstop.js && backstop test --config ./backstop.js
```
## Using in Projects

The repository can also be used directly to test projects. Example procedure:

1. Clone the repository
2. `ddev start`
3. Create a new branch for the project: `git checkout -b projectname`
4. `backstop init`
5. The file `backstop.json` created by this command can be deleted immediately
6. Then adjust the reference and test domains in the files and proceed as described above
7. Check and adjust test parameters in backstop.js if necessary (delay, removeSelectors, etc.)
8. In the end, you could commit the generated files in the project branch: `git add . && git commit -m "Tested projectname"`
9. Then switch back to the main branch: `git checkout main`
10. If the test branch is no longer needed, simply delete it: `git branch -D projectname`
