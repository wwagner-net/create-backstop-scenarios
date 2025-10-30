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
function getUrls($domain, $maxDepth = null, $maxUrls = MAX_URLS, $verbose = false) {
    $visited = [];
    $toVisit = [$domain];
    $urls = [];
    $totalVisited = 0;
    $errorLog = [];

    echo "Starting crawler...\n";
    echo "Domain: $domain\n";
    if ($maxDepth !== null) {
        echo "Max depth: $maxDepth\n";
    }
    echo "Max URLs: $maxUrls\n\n";

    while ($toVisit && $totalVisited < $maxUrls) {
        $currentUrl = array_pop($toVisit);

        // Skip if already visited
        if (in_array($currentUrl, $visited)) {
            continue;
        }

        $visited[] = $currentUrl;

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

            // Add to URLs list (with fragment removed)
            if (!in_array($normalizedUrl, $urls)) {
                $urls[] = $normalizedUrl;
            }

            // Add to crawl queue
            if (!in_array($normalizedUrl, $visited) && !in_array($normalizedUrl, $toVisit)) {
                $toVisit[] = $normalizedUrl;
            }
        }

        $totalVisited++;
        echo "\rPages visited: $totalVisited, Pending: " . count($toVisit) . ", URLs found: " . count($urls) . ", Errors: " . count($errorLog) . "    ";
    }

    echo "\n\n";
    echo "Crawling complete!\n";
    echo "Total pages visited: $totalVisited\n";
    echo "Total URLs found: " . count($urls) . "\n";
    echo "Errors encountered: " . count($errorLog) . "\n";

    // Show error summary
    if (!empty($errorLog)) {
        showErrorSummary($errorLog, $totalVisited);
    }

    echo "\n";

    // Remove duplicates and sort
    $urls = array_unique($urls);
    sort($urls);

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

    // Validate required arguments
    if (!isset($options['url'])) {
        echo "Error: --url parameter is required.\n\n";
        showHelp();
        exit(1);
    }

    return $options;
}

/**
 * Show help message
 */
function showHelp() {
    echo "BackstopJS URL Crawler\n\n";
    echo "Usage: ddev exec php crawler.php [options]\n\n";
    echo "Required:\n";
    echo "  --url=URL               Domain to crawl (e.g., https://www.example.com)\n\n";
    echo "Optional:\n";
    echo "  --output=FILE           Output CSV file (default: crawled_urls.csv)\n";
    echo "  --max-depth=N           Maximum crawl depth (default: unlimited)\n";
    echo "  --max-urls=N            Maximum URLs to crawl (default: 10000)\n";
    echo "  --include-params        Include URLs with query parameters\n";
    echo "  --verbose               Show detailed error messages during crawling\n";
    echo "  --help                  Show this help message\n\n";
    echo "Examples:\n";
    echo "  ddev exec php crawler.php --url=https://www.example.com\n";
    echo "  ddev exec php crawler.php --url=https://www.example.com --output=urls.csv\n";
    echo "  ddev exec php crawler.php --url=https://www.example.com --max-urls=500\n";
    echo "  ddev exec php crawler.php --url=https://www.example.com --verbose\n\n";
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

// Get and validate URL
$referenceDomain = rtrim($options['url'], '/');
validateUrl($referenceDomain);

// Get optional parameters
$outputFile = $options['output'] ?? 'crawled_urls.csv';
$maxDepth = isset($options['max-depth']) ? (int)$options['max-depth'] : null;
$maxUrls = isset($options['max-urls']) ? (int)$options['max-urls'] : MAX_URLS;
$includeParams = isset($options['include-params']);
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

// Crawl URLs
$urls = getUrls($referenceDomain, $maxDepth, $maxUrls, $verbose);

// Filter out URLs with parameters if needed
if (!$includeParams) {
    $originalCount = count($urls);
    $urls = array_filter($urls, function($url) {
        return strpos($url, '?') === false;
    });
    $filteredCount = $originalCount - count($urls);
    if ($filteredCount > 0) {
        echo "Filtered out $filteredCount URLs with query parameters.\n";
        echo "Use --include-params to keep them.\n\n";
    }
}

if (empty($urls)) {
    echo "Warning: No URLs found!\n";
    exit(1);
}

// Write CSV file
echo "Writing to file: $outputFile\n";
if (($handle = fopen($outputFile, 'w')) !== false) {
    foreach ($urls as $url) {
        fputcsv($handle, [$url]);
    }
    fclose($handle);
    echo "✓ CSV file successfully created: $outputFile\n";
    echo "✓ Total URLs exported: " . count($urls) . "\n\n";
    echo "Next steps:\n";
    echo "  1. Review the CSV file: $outputFile\n";
    echo "  2. Run: ddev exec php create-backstop-scenarios.php \\\n";
    echo "       --test=https://your-test-domain.com \\\n";
    echo "       --reference=$referenceDomain\n";
} else {
    echo "Error: Could not write to file: $outputFile\n";
    exit(1);
}
