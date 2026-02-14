<?php

/**
 * i18n
 *
 * Internationalization and translation support.
 *
 * @package core
 * @author Stefano Azzolini <lastguest@gmail.com>
 * @copyright Coesion - 2026
 */

class i18n {
    use Module;

    protected static $locale = 'en';
    protected static $translations = [];
    protected static $fallback = 'en';

    /**
     * Get or set the current locale.
     *
     * @param string|null $locale Set locale if provided, get current if null
     * @return string The current locale
     */
    public static function locale($locale = null) {
        if ($locale !== null) {
            static::$locale = $locale;
        }
        return static::$locale;
    }

    /**
     * Get or set the fallback locale.
     *
     * @param string|null $fallback
     * @return string
     */
    public static function fallback($fallback = null) {
        if ($fallback !== null) {
            static::$fallback = $fallback;
        }
        return static::$fallback;
    }

    /**
     * Translate a key with optional parameter substitution.
     * Uses dot-notation keys: 'user.welcome' resolves to $translations['user']['welcome']
     *
     * @param string $key Dot-notation translation key
     * @param array $params Substitution parameters for {{ key }} placeholders
     * @return string The translated string, or the key itself if not found
     */
    public static function t($key, $params = []) {
        $value = static::resolve($key, static::$locale);

        if ($value === null && static::$locale !== static::$fallback) {
            $value = static::resolve($key, static::$fallback);
        }

        if ($value === null) return $key;

        if ($params) {
            $value = Text::render($value, $params);
        }

        return $value;
    }

    /**
     * Load translations for a locale from a file (JSON or PHP).
     *
     * @param string $locale The locale identifier
     * @param string $filepath Path to a JSON or PHP file returning an array
     * @return void
     */
    public static function load($locale, $filepath) {
        if (!is_file($filepath)) return;

        $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $data = json_decode(file_get_contents($filepath), true);
        } else {
            ob_start();
            $data = include $filepath;
            ob_end_clean();
        }

        if (is_array($data)) {
            if (!isset(static::$translations[$locale])) {
                static::$translations[$locale] = [];
            }
            static::$translations[$locale] = array_replace_recursive(
                static::$translations[$locale],
                $data
            );
        }
    }

    /**
     * Load translations from an associative array.
     *
     * @param string $locale The locale identifier
     * @param array $data Nested translation array
     * @return void
     */
    public static function loadArray($locale, array $data) {
        if (!isset(static::$translations[$locale])) {
            static::$translations[$locale] = [];
        }
        static::$translations[$locale] = array_replace_recursive(
            static::$translations[$locale],
            $data
        );
    }

    /**
     * Check if a translation key exists for a locale.
     *
     * @param string $key Dot-notation key
     * @param string|null $locale Optional locale, defaults to current
     * @return bool
     */
    public static function has($key, $locale = null) {
        return static::resolve($key, $locale ?? static::$locale) !== null;
    }

    /**
     * Get all translations for a locale.
     *
     * @param string|null $locale
     * @return array
     */
    public static function all($locale = null) {
        $locale = $locale ?? static::$locale;
        return static::$translations[$locale] ?? [];
    }

    /**
     * Clear all loaded translations.
     *
     * @return void
     */
    public static function flush() {
        static::$translations = [];
    }

    /**
     * Resolve a dot-notation key from the translation store.
     *
     * @param string $key
     * @param string $locale
     * @return string|null
     */
    protected static function resolve($key, $locale) {
        if (!isset(static::$translations[$locale])) return null;

        $segments = explode('.', $key);
        $current = static::$translations[$locale];

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return is_string($current) ? $current : null;
    }
}
