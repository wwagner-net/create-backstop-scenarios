<?php

/**
 * Configuration
 */
define('USER_AGENT', 'Mozilla/5.0 (compatible; BackstopJS-Crawler/1.0)');
define('TIMEOUT', 30);
define('MAX_URLS', 10000);

/**
 * Crawl a domain and extract all page URLs
 */
function getUrls($domain, $maxDepth = null, $maxUrls = MAX_URLS, $verbose = false, $csvHandle = null) {
    // Use associative arrays (hash tables) for O(1) lookup instead of O(n)
    $visited = [];
    $toVisit = [$domain => true];
    $urlCount = 0;
    $totalVisited = 0;
    $errorLog = [];

    echo "Starting crawler...\n";
    echo "Domain: $domain\n";
    if ($maxDepth !== null) {
        echo "Max depth: $maxDepth\n";
    }
    echo "Max URLs: $maxUrls\n";

    if ($csvHandle !== null) {
        echo "Writing URLs to file in real-time...\n";
    }
    echo "\n";

    while (!empty($toVisit) && $totalVisited < $maxUrls) {
        // Get next URL from queue (get first key)
        reset($toVisit);
        $currentUrl = key($toVisit);
        unset($toVisit[$currentUrl]);

        // Skip if already visited
        if (isset($visited[$currentUrl])) {
            continue;
        }

        $visited[$currentUrl] = true;

        // Fetch page content
        $result = fetchUrl($currentUrl);
        if ($result['success'] === false) {
            $errorLog[] = [
                'url' => $currentUrl,
                'reason' => $result['error']
            ];

            if ($verbose) {
                echo "\n[ERROR] " . $result['error'] . ": $currentUrl\n";
            }

            echo "\rPages visited: $totalVisited, Pending: " . count($toVisit) . ", Errors: " . count($errorLog) . "    ";
            continue;
        }

        $html = $result['content'];

        // Extract all links from the page
        $extractedUrls = extractLinks($html, $currentUrl, $domain);

        foreach ($extractedUrls as $url) {
            // Normalize URL (remove trailing slash, fragments)
            $normalizedUrl = normalizeUrl($url);

            if (!$normalizedUrl) {
                continue;
            }

            // Filter out URLs that don't belong to the domain
            if (strpos($normalizedUrl, $domain) !== 0) {
                continue;
            }

            // Filter out file URLs
            if (isFileUrl($normalizedUrl)) {
                continue;
            }

            // Filter out special protocol links
            if (isSpecialProtocol($normalizedUrl)) {
                continue;
            }

            // Check if URL is new (avoid duplicates) - O(1) lookup with isset
            if (!isset($visited[$normalizedUrl]) && !isset($toVisit[$normalizedUrl])) {
                // Write to file immediately if handle is provided
                if ($csvHandle !== null) {
                    fwrite($csvHandle, $normalizedUrl . "\n");
                    fflush($csvHandle); // Force write to disk
                }

                $urlCount++;

                // Add to crawl queue
                $toVisit[$normalizedUrl] = true;
            }
        }

        $totalVisited++;
        echo "\rPages visited: $totalVisited, Pending: " . count($toVisit) . ", URLs found: $urlCount, Errors: " . count($errorLog) . "    ";
    }

    echo "\n\n";
    echo "Crawling complete!\n";
    echo "Total pages visited: $totalVisited\n";
    echo "Total URLs found: $urlCount\n";
    echo "Errors encountered: " . count($errorLog) . "\n";

    // Show error summary
    if (!empty($errorLog)) {
        showErrorSummary($errorLog, $totalVisited);
    }

    echo "\n";

    return [
        'urlCount' => $urlCount,
        'visited' => $totalVisited,
        'errors' => $errorLog
    ];
}

/**
 * Get URLs from sitemap.xml
 */
function getSitemapUrls($sitemapUrl, $domain, $maxUrls = MAX_URLS, $csvHandle = null) {
    $visited = [];
    $urlCount = 0;
    $errorLog = [];

    echo "Starting sitemap parser...\n";
    echo "Sitemap URL: $sitemapUrl\n";
    echo "Max URLs: $maxUrls\n";

    if ($csvHandle !== null) {
        echo "Writing URLs to file in real-time...\n";
    }
    echo "\n";

    // Parse the sitemap (handles both sitemap index and regular sitemaps)
    $urls = parseSitemap($sitemapUrl, $domain, $maxUrls, $urlCount, $visited, $errorLog, $csvHandle);

    echo "\n\n";
    echo "Sitemap parsing complete!\n";
    echo "Total URLs found: $urlCount\n";
    echo "Errors encountered: " . count($errorLog) . "\n";

    if (!empty($errorLog)) {
        echo "\n--- Errors ---\n";
        foreach ($errorLog as $error) {
            echo "  - " . $error['reason'] . ": " . $error['url'] . "\n";
        }
    }

    echo "\n";

    return [
        'urlCount' => $urlCount,
        'visited' => 0,
        'errors' => $errorLog
    ];
}

/**
 * Parse a sitemap XML file (handles both sitemap index and regular sitemaps)
 */
function parseSitemap($sitemapUrl, $domain, $maxUrls, &$urlCount, &$visited, &$errorLog, $csvHandle = null) {
    // Fetch sitemap content
    $result = fetchUrl($sitemapUrl);

    if ($result['success'] === false) {
        $errorLog[] = [
            'url' => $sitemapUrl,
            'reason' => $result['error']
        ];
        echo "[ERROR] Could not fetch sitemap: " . $result['error'] . "\n";
        return [];
    }

    $xml = $result['content'];

    // Try to parse XML
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadXML($xml);

    if (!$loaded) {
        $errorLog[] = [
            'url' => $sitemapUrl,
            'reason' => 'Invalid XML format'
        ];
        echo "[ERROR] Invalid XML format in sitemap\n";
        return [];
    }

    // Check if it's a sitemap index (contains <sitemapindex>)
    $sitemapIndexElements = $dom->getElementsByTagName('sitemapindex');

    if ($sitemapIndexElements->length > 0) {
        echo "Detected sitemap index, processing sub-sitemaps...\n";
        return parseSitemapIndex($dom, $domain, $maxUrls, $urlCount, $visited, $errorLog, $csvHandle);
    }

    // It's a regular sitemap, extract URLs
    echo "Processing sitemap: $sitemapUrl\n";
    return extractUrlsFromSitemap($dom, $domain, $maxUrls, $urlCount, $visited, $errorLog, $csvHandle);
}

/**
 * Parse a sitemap index and process all sub-sitemaps
 */
function parseSitemapIndex($dom, $domain, $maxUrls, &$urlCount, &$visited, &$errorLog, $csvHandle = null) {
    $urls = [];
    $sitemapElements = $dom->getElementsByTagName('sitemap');

    echo "Found " . $sitemapElements->length . " sub-sitemap(s)\n\n";

    foreach ($sitemapElements as $sitemapElement) {
        if ($urlCount >= $maxUrls) {
            echo "Max URLs reached ($maxUrls), stopping...\n";
            break;
        }

        $locElements = $sitemapElement->getElementsByTagName('loc');
        if ($locElements->length > 0) {
            $subSitemapUrl = trim($locElements->item(0)->nodeValue);

            // Recursively parse the sub-sitemap
            echo "Loading sub-sitemap: $subSitemapUrl\n";
            $subUrls = parseSitemap($subSitemapUrl, $domain, $maxUrls, $urlCount, $visited, $errorLog, $csvHandle);
            $urls = array_merge($urls, $subUrls);
        }
    }

    return $urls;
}

/**
 * Extract URLs from a regular sitemap
 */
function extractUrlsFromSitemap($dom, $domain, $maxUrls, &$urlCount, &$visited, &$errorLog, $csvHandle = null) {
    $urls = [];
    $urlElements = $dom->getElementsByTagName('url');

    echo "Processing " . $urlElements->length . " URLs from sitemap...\n";

    foreach ($urlElements as $urlElement) {
        if ($urlCount >= $maxUrls) {
            echo "\nMax URLs reached ($maxUrls), stopping...\n";
            break;
        }

        $locElements = $urlElement->getElementsByTagName('loc');
        if ($locElements->length > 0) {
            $url = trim($locElements->item(0)->nodeValue);

            // Normalize URL
            $normalizedUrl = normalizeUrl($url);

            if (!$normalizedUrl) {
                continue;
            }

            // Filter: Check if URL belongs to the domain
            if ($domain && strpos($normalizedUrl, $domain) !== 0) {
                continue;
            }

            // Filter: Skip file URLs
            if (isFileUrl($normalizedUrl)) {
                continue;
            }

            // Filter: Skip special protocols
            if (isSpecialProtocol($normalizedUrl)) {
                continue;
            }

            // Check for duplicates
            if (isset($visited[$normalizedUrl])) {
                continue;
            }

            $visited[$normalizedUrl] = true;

            // Write to file immediately if handle is provided
            if ($csvHandle !== null) {
                fwrite($csvHandle, $normalizedUrl . "\n");
                fflush($csvHandle);
            }

            $urlCount++;
            $urls[] = $normalizedUrl;

            // Progress indicator
            if ($urlCount % 100 == 0) {
                echo "\rURLs processed: $urlCount    ";
            }
        }
    }

    echo "\rURLs processed: $urlCount    \n";

    return $urls;
}

/**
 * Show error summary with categorized errors
 */
function showErrorSummary($errorLog, $totalVisited) {
    $errorTypes = [];

    // Categorize errors
    foreach ($errorLog as $error) {
        $reason = $error['reason'];
        if (!isset($errorTypes[$reason])) {
            $errorTypes[$reason] = [];
        }
        $errorTypes[$reason][] = $error['url'];
    }

    echo "\n--- Error Summary ---\n";

    foreach ($errorTypes as $reason => $urls) {
        $count = count($urls);
        echo "\n$reason: $count page(s)\n";

        // Show first 3 examples
        $examples = array_slice($urls, 0, 3);
        foreach ($examples as $url) {
            echo "  - $url\n";
        }

        if ($count > 3) {
            echo "  ... and " . ($count - 3) . " more\n";
        }
    }

    echo "\n";
    $totalErrors = count($errorLog);
    $totalAttempts = $totalVisited + $totalErrors;
    $errorRate = $totalAttempts > 0 ? round(($totalErrors / $totalAttempts) * 100, 1) : 0;

    echo "Error rate: $errorRate% ($totalErrors errors out of $totalAttempts pages)\n";

    if ($totalErrors < 5) {
        echo "ℹ️  These errors are normal and can usually be ignored.\n";
    } elseif ($errorRate < 10) {
        echo "ℹ️  Small number of errors - this is normal for most websites.\n";
    } elseif ($errorRate < 25) {
        echo "⚠️  Moderate number of errors - the site might have some broken links.\n";
    } else {
        echo "⚠️  High error rate - there might be connectivity issues or many broken links.\n";
    }
}

/**
 * Fetch URL content with proper headers and error handling
 */
function fetchUrl($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: " . USER_AGENT . "\r\n" .
                       "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                       "Accept-Language: en-US,en;q=0.5\r\n",
            'timeout' => TIMEOUT,
            'ignore_errors' => true,
            'follow_location' => true,
            'max_redirects' => 5,
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);

    $html = @file_get_contents($url, false, $context);

    if ($html === false) {
        return [
            'success' => false,
            'error' => 'Connection failed',
            'content' => null
        ];
    }

    // Check HTTP response code
    if (isset($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0], $matches);
        $statusCode = isset($matches[1]) ? (int)$matches[1] : 0;

        if ($statusCode >= 400) {
            $errorMessage = 'HTTP ' . $statusCode;
            if ($statusCode == 404) {
                $errorMessage = 'Page not found (404)';
            } elseif ($statusCode == 403) {
                $errorMessage = 'Access forbidden (403)';
            } elseif ($statusCode == 500) {
                $errorMessage = 'Server error (500)';
            } elseif ($statusCode >= 400 && $statusCode < 500) {
                $errorMessage = 'Client error (' . $statusCode . ')';
            } elseif ($statusCode >= 500) {
                $errorMessage = 'Server error (' . $statusCode . ')';
            }

            return [
                'success' => false,
                'error' => $errorMessage,
                'content' => null
            ];
        }
    }

    return [
        'success' => true,
        'error' => null,
        'content' => $html
    ];
}

/**
 * Extract all links from HTML content
 */
function extractLinks($html, $baseUrl, $domain) {
    $links = [];

    // Match href attributes in various formats
    preg_match_all('/href=["\']([^"\']*)["\']|href=([^\s>]+)/i', $html, $matches);

    // Combine both match groups
    $hrefs = array_merge($matches[1], $matches[2]);

    foreach ($hrefs as $href) {
        if (empty($href)) {
            continue;
        }

        // Clean the URL first
        $href = cleanUrl($href);

        if (empty($href)) {
            continue;
        }

        // Skip special protocols early
        if (isSpecialProtocol($href)) {
            continue;
        }

        // Resolve relative URLs
        $absoluteUrl = resolveUrl($href, $baseUrl);

        if ($absoluteUrl) {
            $links[] = $absoluteUrl;
        }
    }

    return $links;
}

/**
 * Clean and validate URL string
 */
function cleanUrl($url) {
    // Trim whitespace
    $url = trim($url);

    // Remove HTML entities
    $url = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Check for invalid characters that indicate it's not a URL
    if (preg_match('/[{}()\[\]<>]/', $url)) {
        return false;
    }

    // Check if URL contains JavaScript code indicators
    if (preg_match('/\bfunction\b|\bvar\b|\blet\b|\bconst\b|\breturn\b|=>|\{|\}|\(.*\).*\{/i', $url)) {
        return false;
    }

    // Remove escaped characters that shouldn't be in URLs
    if (preg_match('/\\\\[a-z]/i', $url)) {
        return false;
    }

    // Check minimum length
    if (strlen($url) < 1) {
        return false;
    }

    return $url;
}

/**
 * Resolve relative URLs to absolute URLs
 */
function resolveUrl($relativeUrl, $baseUrl) {
    // Already absolute
    if (preg_match('#^https?://#i', $relativeUrl)) {
        return $relativeUrl;
    }

    // Protocol-relative URL
    if (strpos($relativeUrl, '//') === 0) {
        $parsed = parse_url($baseUrl);
        return $parsed['scheme'] . ':' . $relativeUrl;
    }

    $parsed = parse_url($baseUrl);
    $scheme = $parsed['scheme'] ?? 'http';
    $host = $parsed['host'] ?? '';
    $path = $parsed['path'] ?? '/';

    if (empty($host)) {
        return false;
    }

    // Remove fragment and query from base path
    $path = preg_replace('/[?#].*/', '', $path);

    // Absolute path
    if (strpos($relativeUrl, '/') === 0) {
        return $scheme . '://' . $host . $relativeUrl;
    }

    // Relative path
    $basePath = dirname($path);
    if ($basePath === '.') {
        $basePath = '/';
    }

    // Resolve ../ and ./
    $absolutePath = $basePath . '/' . $relativeUrl;
    $parts = explode('/', $absolutePath);
    $resolved = [];

    foreach ($parts as $part) {
        if ($part === '' || $part === '.') {
            continue;
        }
        if ($part === '..') {
            array_pop($resolved);
        } else {
            $resolved[] = $part;
        }
    }

    $resolvedPath = '/' . implode('/', $resolved);

    return $scheme . '://' . $host . $resolvedPath;
}

/**
 * Normalize URL (remove fragment, trailing slash)
 */
function normalizeUrl($url) {
    // Remove fragment
    $url = preg_replace('/#.*$/', '', $url);

    // Parse URL
    $parsed = parse_url($url);

    if (!$parsed || !isset($parsed['host'])) {
        return false;
    }

    // Validate scheme
    $scheme = $parsed['scheme'] ?? 'http';
    if (!in_array(strtolower($scheme), ['http', 'https'])) {
        return false;
    }

    $host = $parsed['host'];
    $path = $parsed['path'] ?? '/';
    $query = isset($parsed['query']) ? '?' . $parsed['query'] : '';

    // Validate host (basic check)
    if (empty($host) || strlen($host) < 3) {
        return false;
    }

    // Check for invalid characters in path
    if (preg_match('/[<>"{}|\\\\^`\[\]]/', $path)) {
        return false;
    }

    // Remove trailing slash (except for root)
    if ($path !== '/' && substr($path, -1) === '/') {
        $path = rtrim($path, '/');
    }

    $normalizedUrl = $scheme . '://' . $host . $path . $query;

    // Final validation: must be a valid URL
    if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
        return false;
    }

    return $normalizedUrl;
}

/**
 * Check if URL points to a file
 */
function isFileUrl($url) {
    $extensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp',
        'mp4', 'avi', 'mov', 'wmv', 'flv', 'mp3', 'wav',
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2',
        'exe', 'dmg', 'pkg', 'deb', 'rpm',
        'ico', 'webmanifest', 'xml', 'json', 'css', 'js'
    ];

    $pattern = '/\.(' . implode('|', $extensions) . ')$/i';
    return preg_match($pattern, parse_url($url, PHP_URL_PATH));
}

/**
 * Check if URL uses special protocols
 */
function isSpecialProtocol($url) {
    $protocols = [
        'tel:',
        'mailto:',
        'javascript:',
        'ftp:',
        'data:',
        'file:',
        'sms:',
        'callto:',
        'skype:',
        'whatsapp:'
    ];

    foreach ($protocols as $protocol) {
        if (stripos($url, $protocol) === 0) {
            return true;
        }
    }

    // Also check for malformed protocols
    if (preg_match('/^[a-z]+:/i', $url) && !preg_match('/^https?:/i', $url)) {
        return true;
    }

    return false;
}

/**
 * Parse command line arguments
 */
function getArguments() {
    global $argv;

    $shortopts = "";
    $longopts = [
        "url:",
        "sitemap:",
        "output:",
        "max-depth::",
        "max-urls::",
        "include-params",
        "verbose",
        "help"
    ];

    $options = getopt($shortopts, $longopts);

    // Show help
    if (isset($options['help']) || in_array('--help', $argv)) {
        showHelp();
        exit(0);
    }

    // Validate required arguments - either --url OR --sitemap
    if (!isset($options['url']) && !isset($options['sitemap'])) {
        echo "Error: Either --url or --sitemap parameter is required.\n\n";
        showHelp();
        exit(1);
    }

    // Ensure only one mode is used
    if (isset($options['url']) && isset($options['sitemap'])) {
        echo "Error: Cannot use both --url and --sitemap. Choose one mode.\n\n";
        showHelp();
        exit(1);
    }

    return $options;
}

/**
 * Show help message
 */
function showHelp() {
    echo "BackstopJS URL Crawler & Sitemap Parser\n\n";
    echo "Usage: ddev exec php crawler.php [options]\n\n";
    echo "Required (choose one):\n";
    echo "  --url=URL               Domain to crawl (e.g., https://www.example.com)\n";
    echo "  --sitemap=URL           Parse sitemap.xml instead of crawling\n";
    echo "                          (e.g., https://www.example.com/sitemap.xml)\n\n";
    echo "Optional:\n";
    echo "  --output=FILE           Output file (default: crawled_urls.txt)\n";
    echo "  --max-depth=N           Maximum crawl depth (crawler only, default: unlimited)\n";
    echo "  --max-urls=N            Maximum URLs to process (default: 10000)\n";
    echo "  --include-params        Include URLs with query parameters\n";
    echo "  --verbose               Show detailed error messages (crawler only)\n";
    echo "  --help                  Show this help message\n\n";
    echo "Examples:\n";
    echo "  # Crawl a website\n";
    echo "  ddev exec php crawler.php --url=https://www.example.com\n\n";
    echo "  # Parse a sitemap.xml (much faster!)\n";
    echo "  ddev exec php crawler.php --sitemap=https://www.example.com/sitemap.xml\n\n";
    echo "  # Parse sitemap with custom output\n";
    echo "  ddev exec php crawler.php --sitemap=https://www.example.com/sitemap.xml --output=urls.txt\n\n";
    echo "  # Limit URLs from sitemap\n";
    echo "  ddev exec php crawler.php --sitemap=https://www.example.com/sitemap.xml --max-urls=500\n\n";
    echo "Note: Sitemap parsing supports both regular sitemaps and sitemap index files.\n";
    echo "      It will automatically follow and parse all referenced sub-sitemaps.\n\n";
}

/**
 * Validate URL format
 */
function validateUrl($url) {
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo "Error: Invalid URL format: $url\n";
        exit(1);
    }

    $parsed = parse_url($url);

    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
        echo "Error: URL must use http or https protocol.\n";
        exit(1);
    }

    if (!isset($parsed['host'])) {
        echo "Error: Invalid URL - no host found.\n";
        exit(1);
    }

    return true;
}

// Parse command line arguments
$options = getArguments();

// Get optional parameters
$outputFile = $options['output'] ?? 'crawled_urls.txt';
$maxUrls = isset($options['max-urls']) ? (int)$options['max-urls'] : MAX_URLS;
$includeParams = isset($options['include-params']);

// Determine mode: crawler or sitemap
if (isset($options['url'])) {
    // ========== CRAWLER MODE ==========
    $referenceDomain = rtrim($options['url'], '/');
    validateUrl($referenceDomain);

    $maxDepth = isset($options['max-depth']) ? (int)$options['max-depth'] : null;
    $verbose = isset($options['verbose']);

    // Test if domain is reachable
    echo "Testing connection to $referenceDomain...\n";
    $testResult = fetchUrl($referenceDomain);
    if ($testResult['success'] === false) {
        echo "Error: Could not connect to $referenceDomain\n";
        echo "Reason: " . $testResult['error'] . "\n\n";
        echo "Please check:\n";
        echo "  - Is the URL correct?\n";
        echo "  - Is the website online?\n";
        echo "  - Do you have internet connection?\n";
        exit(1);
    }
    echo "Connection successful!\n\n";

    // Open file for writing BEFORE crawling
    $csvHandle = fopen($outputFile, 'w');
    if ($csvHandle === false) {
        echo "Error: Could not create file: $outputFile\n";
        exit(1);
    }

    echo "Writing URLs to: $outputFile\n\n";

    // Crawl URLs and write to file in real-time
    $result = getUrls($referenceDomain, $maxDepth, $maxUrls, $verbose, $csvHandle);

    // Close file
    fclose($csvHandle);

} else {
    // ========== SITEMAP MODE ==========
    $sitemapUrl = $options['sitemap'];
    validateUrl($sitemapUrl);

    // Extract domain from sitemap URL for filtering
    $parsed = parse_url($sitemapUrl);
    $referenceDomain = $parsed['scheme'] . '://' . $parsed['host'];

    // Test if sitemap is reachable
    echo "Testing connection to sitemap...\n";
    $testResult = fetchUrl($sitemapUrl);
    if ($testResult['success'] === false) {
        echo "Error: Could not fetch sitemap from $sitemapUrl\n";
        echo "Reason: " . $testResult['error'] . "\n\n";
        echo "Please check:\n";
        echo "  - Is the sitemap URL correct?\n";
        echo "  - Is the sitemap accessible?\n";
        echo "  - Do you have internet connection?\n";
        exit(1);
    }
    echo "Connection successful!\n\n";

    // Open file for writing BEFORE parsing
    $csvHandle = fopen($outputFile, 'w');
    if ($csvHandle === false) {
        echo "Error: Could not create file: $outputFile\n";
        exit(1);
    }

    echo "Writing URLs to: $outputFile\n\n";

    // Parse sitemap and write to file in real-time
    $result = getSitemapUrls($sitemapUrl, $referenceDomain, $maxUrls, $csvHandle);

    // Close file
    fclose($csvHandle);
}

// Check if any URLs were found
if ($result['urlCount'] === 0) {
    echo "Warning: No URLs found!\n";
    unlink($outputFile); // Delete empty file
    exit(1);
}

// Post-process: Filter out URLs with parameters if needed
if (!$includeParams) {
    echo "Post-processing: Filtering URLs with query parameters...\n";

    $tempFile = $outputFile . '.tmp';
    $readHandle = fopen($outputFile, 'r');
    $writeHandle = fopen($tempFile, 'w');

    $originalCount = 0;
    $filteredCount = 0;

    while (($line = fgets($readHandle)) !== false) {
        $originalCount++;
        $url = trim($line);

        if (strpos($url, '?') === false) {
            fwrite($writeHandle, $url . "\n");
            $filteredCount++;
        }
    }

    fclose($readHandle);
    fclose($writeHandle);

    // Replace original file with filtered file
    rename($tempFile, $outputFile);

    $removedCount = $originalCount - $filteredCount;
    if ($removedCount > 0) {
        echo "✓ Filtered out $removedCount URLs with query parameters.\n";
        echo "  Use --include-params to keep them.\n\n";
    }

    $result['urlCount'] = $filteredCount;
}

echo "✓ File successfully created: $outputFile\n";
echo "✓ Total URLs exported: " . $result['urlCount'] . "\n\n";
echo "Next steps:\n";
echo "  1. Review the file: $outputFile\n";
echo "  2. Run: ddev exec php create-backstop-scenarios.php \\\n";
echo "       --test=https://your-test-domain.com \\\n";
echo "       --reference=$referenceDomain\n";
