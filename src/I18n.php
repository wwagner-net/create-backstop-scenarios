<?php
/**
 * Minimal EN/DE i18n helper. No dependencies.
 *
 * Locale order: explicit override → config.json locale → Accept-Language / $LANG → en
 */
class I18n
{
    private static string $locale = 'en';
    private static array $catalogs = [];
    private static ?string $root = null;

    public static function root(): string
    {
        if (self::$root === null) {
            self::$root = defined('BASE_DIR') ? BASE_DIR : dirname(__DIR__);
        }
        return self::$root;
    }

    public static function setLocale(string $locale): void
    {
        $locale = self::normalize($locale) ?? 'en';
        self::$locale = $locale;
    }

    public static function getLocale(): string
    {
        return self::$locale;
    }

    /** @return 'en'|'de'|null */
    public static function normalize(?string $locale): ?string
    {
        if ($locale === null || $locale === '') {
            return null;
        }
        $locale = strtolower(str_replace('_', '-', trim($locale)));
        if (str_starts_with($locale, 'de')) {
            return 'de';
        }
        if (str_starts_with($locale, 'en')) {
            return 'en';
        }
        return null;
    }

    /**
     * Resolve and set active locale.
     *
     * @param string|null $override Explicit --lang / ?lang / GUI choice
     * @param bool $useHttpHeader Also read Accept-Language (web)
     * @param bool $useEnv Also read $LANG / $LC_ALL (CLI)
     */
    public static function resolve(?string $override = null, bool $useHttpHeader = true, bool $useEnv = true): string
    {
        $locale = self::normalize($override);

        if ($locale === null) {
            $locale = self::normalize(self::configLocale());
        }

        if ($locale === null && $useHttpHeader) {
            $locale = self::fromAcceptLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        }

        if ($locale === null && $useEnv) {
            $locale = self::normalize(getenv('LC_ALL') ?: null)
                ?? self::normalize(getenv('LANG') ?: null);
        }

        self::$locale = $locale ?? 'en';
        return self::$locale;
    }

    public static function t(string $key, array $vars = []): string
    {
        $text = self::lookup(self::$locale, $key);
        if ($text === null && self::$locale !== 'en') {
            $text = self::lookup('en', $key);
        }
        if ($text === null) {
            $text = $key;
        }
        foreach ($vars as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }
        return $text;
    }

    public static function catalog(string $locale): array
    {
        $locale = self::normalize($locale) ?? 'en';
        if (!isset(self::$catalogs[$locale])) {
            $path = self::root() . '/lang/' . $locale . '.json';
            if (!is_file($path)) {
                self::$catalogs[$locale] = [];
            } else {
                $data = json_decode((string)file_get_contents($path), true);
                self::$catalogs[$locale] = is_array($data) ? $data : [];
            }
        }
        return self::$catalogs[$locale];
    }

    /** Both catalogs for GUI bootstrap. */
    public static function allCatalogs(): array
    {
        return [
            'en' => self::catalog('en'),
            'de' => self::catalog('de'),
        ];
    }

    private static function lookup(string $locale, string $key): ?string
    {
        $cat = self::catalog($locale);
        return isset($cat[$key]) && is_string($cat[$key]) ? $cat[$key] : null;
    }

    private static function configLocale(): ?string
    {
        $path = self::root() . '/config.json';
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string)file_get_contents($path), true);
        if (!is_array($data)) {
            return null;
        }
        return isset($data['locale']) ? (string)$data['locale'] : null;
    }

    private static function fromAcceptLanguage(string $header): ?string
    {
        if ($header === '') {
            return null;
        }
        foreach (explode(',', $header) as $part) {
            $code = trim(explode(';', $part)[0]);
            $norm = self::normalize($code);
            if ($norm !== null) {
                return $norm;
            }
        }
        return null;
    }
}
