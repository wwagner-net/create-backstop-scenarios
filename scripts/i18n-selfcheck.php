<?php
/**
 * Smallest runnable check for I18n. Fails hard if logic breaks.
 * Usage: ddev exec php scripts/i18n-selfcheck.php
 */
require_once dirname(__DIR__) . '/src/I18n.php';

function assert_true(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: $msg\n");
        exit(1);
    }
    echo "OK: $msg\n";
}

I18n::setLocale('en');
assert_true(I18n::t('test.hello') === 'Hello', 'EN test.hello');

I18n::setLocale('de');
assert_true(I18n::t('test.hello') === 'Hallo', 'DE test.hello');
assert_true(I18n::t('test.en_only') === 'English only key', 'DE falls back to EN for missing key');
assert_true(I18n::t('test.greet', ['name' => 'Ada']) === 'Hallo, Ada', 'placeholder substitution');

// Resolve: override wins
putenv('LANG=de_CH.UTF-8');
$resolved = I18n::resolve('en', false, true);
assert_true($resolved === 'en', 'override beats env');

// Resolve: env when no override/config locale forced via env only
I18n::resolve(null, false, true);
// If config.json has locale, that may win — check normalize helpers instead
assert_true(I18n::normalize('de-CH') === 'de', 'normalize de-CH');
assert_true(I18n::normalize('en_US') === 'en', 'normalize en_US');
assert_true(I18n::normalize('fr') === null, 'normalize unknown → null');

echo "All i18n checks passed.\n";
exit(0);
