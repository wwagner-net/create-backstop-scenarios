<?php

function getUrls($domain) {
    $visited = [];
    $toVisit = [$domain];
    $urls = [];
    $totalVisited = 0;

    while ($toVisit) {
        $currentUrl = array_pop($toVisit);

        // Skip if already visited
        if (in_array($currentUrl, $visited)) {
            continue;
        }

        $visited[] = $currentUrl;
        $html = @file_get_contents($currentUrl);
        if ($html === FALSE) {
            continue;
        }

        // Matches all href attributes
        preg_match_all('/href="([^"]*)"/i', $html, $matches);

        foreach ($matches[1] as $url) {
            // Resolve relative URLs
            if (strpos($url, 'http') !== 0) {
                $url = rtrim($domain, '/') . '/' . ltrim($url, '/');
            }

            // Filter out URLs that don't belong to the domain
            if (strpos($url, $domain) !== 0) {
                continue;
            }

            // Filter out URLs that are likely to be files (not pages)
            if (preg_match('/\.(pdf|docx?|xlsx?|pptx?|jpg|jpeg|png|gif|mp4|mp3|zip|rar|7z|tar|gz|ico|svg|webmanifest|xml)$/i', $url)) {
                continue;
            }

            // Filter out URLs with parameters
            if (strpos($url, '?') !== false) {
                continue;
            }

            // Filter out tel:, mailto:, and javascript: links
            if (strpos($url, 'tel:') !== false || strpos($url, 'mailto:') !== false || strpos($url, 'javascript:') !== false) {
                continue;
            }

            // Add to URLs list
            $urls[] = $url;

            // Add to the list of URLs to visit
            if (!in_array($url, $visited) && !in_array($url, $toVisit)) {
                $toVisit[] = $url;
            }
        }

        $totalVisited++;
        echo "Pages visited: $totalVisited, Still to visit: " . count($toVisit) . "\r";
    }

    // Remove duplicates and sort
    $urls = array_unique($urls);
    sort($urls);

    echo "\n";

    return $urls;
}

// Funktion zur Verarbeitung der Befehlszeilenargumente
function getArguments() {
    $shortopts = "";
    $longopts  = array(
        "url:",     // Erforderliches Argument
    );
    $options = getopt($shortopts, $longopts);

    if (!isset($options['url'])) {
        echo "Usage: php crawler.php --url <URL>\n";
        exit(1);
    }

    return $options['url'];
}

// Hole die Domain aus den Argumenten
$referenceDomain = getArguments();

// Crawl URLs
$urls = getUrls($referenceDomain);

// Path to the output CSV file
$outputCsvFile = 'crawled_urls.csv';

// Write CSV file
if (($handle = fopen($outputCsvFile, 'w')) !== FALSE) {
    foreach ($urls as $url) {
        fputcsv($handle, [$url]);
    }
    fclose($handle);
    echo "CSV file was successfully created: $outputCsvFile\n";
} else {
    echo "Error when writing the CSV file.\n";
}
