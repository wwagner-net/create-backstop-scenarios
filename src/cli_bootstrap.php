<?php
/**
 * Shared CLI bootstrap: load I18n and honour --lang=en|de.
 */
require_once __DIR__ . '/I18n.php';

if (!defined('BASE_DIR')) {
    define('BASE_DIR', dirname(__DIR__));
}

function cliParseLang(array $argv): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, '--lang=')) {
            return substr($arg, 7);
        }
        if ($arg === '--lang' || $arg === '-l') {
            // value may be next argv entry — handled by getopt-style callers
            continue;
        }
    }
    return null;
}

function cliBootstrap(array $argv): string
{
    return I18n::resolve(cliParseLang($argv), false, true);
}

function cliT(string $key, array $vars = []): string
{
    return I18n::t($key, $vars);
}
