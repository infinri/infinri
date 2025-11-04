<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

/**
 * Provides internationalization (i18n) support
 * Simple implementation - can be extended with gettext or other libraries.
 */
class Translation
{
    /**
     * Current locale.
     */
    private string $locale = 'en_US';

    /**
     * Translation dictionary.
     *
     * @var array<string, array<string, string>>
     */
    private array $translations = [];

    /**
     * Loaded translation files.
     *
     * @var array<string>
     */
    private array $loadedFiles = [];

    /**
     * Set current locale.
     *
     * @param string $locale Locale code (e.g., 'en_US', 'fr_FR')
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * Get current locale.
     *
     * @return string Locale code
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * Translate text.
     *
     * @param string $text    Text to translate
     * @param mixed  ...$args Arguments for sprintf formatting
     *
     * @return string Translated text
     */
    public function translate(string $text, ...$args): string
    {
        $translated = $this->translations[$this->locale][$text] ?? $text;

        if (! empty($args)) {
            $translated = \sprintf($translated, ...$args);
        }

        return $translated;
    }

    /**
     * Translate text (alias).
     *
     * @param string $text    Text to translate
     * @param mixed  ...$args Arguments for sprintf formatting
     *
     * @return string Translated text
     */
    public function __(string $text, ...$args): string
    {
        return $this->translate($text, ...$args);
    }

    /**
     * Load translation file.
     *
     * @param string $locale   Locale code
     * @param string $filePath Path to translation file (CSV or PHP array)
     *
     * @return bool Success
     */
    public function loadTranslationFile(string $locale, string $filePath): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        if (\in_array($filePath, $this->loadedFiles, true)) {
            return true;
        }

        $extension = pathinfo($filePath, \PATHINFO_EXTENSION);

        if ('php' === $extension) {
            $translations = include $filePath;
            if (\is_array($translations)) {
                $this->addTranslations($locale, $translations);
                $this->loadedFiles[] = $filePath;

                return true;
            }
        } elseif ('csv' === $extension) {
            return $this->loadCsvFile($locale, $filePath);
        }

        return false;
    }

    /**
     * Load CSV translation file.
     *
     * @param string $locale   Locale code
     * @param string $filePath CSV file path
     *
     * @return bool Success
     */
    private function loadCsvFile(string $locale, string $filePath): bool
    {
        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            return false;
        }

        $translations = [];

        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if (\count($data) >= 2 && isset($data[0], $data[1])) {
                $translations[$data[0]] = $data[1];
            }
        }

        fclose($handle);

        if (! empty($translations)) {
            $this->addTranslations($locale, $translations);
        }
        $this->loadedFiles[] = $filePath;

        return true;
    }

    /**
     * Add translations for a locale.
     *
     * @param string                $locale       Locale code
     * @param array<string, string> $translations Translation key-value pairs
     */
    public function addTranslations(string $locale, array $translations): void
    {
        if (! isset($this->translations[$locale])) {
            $this->translations[$locale] = [];
        }

        $this->translations[$locale] = array_merge(
            $this->translations[$locale],
            $translations
        );
    }

    /**
     * Get all translations for a locale.
     *
     * @param string|null $locale Locale code (null = current)
     *
     * @return array Translations
     */
    public function getTranslations(?string $locale = null): array
    {
        $locale = $locale ?? $this->locale;

        return $this->translations[$locale] ?? [];
    }

    /**
     * Check if translation exists.
     *
     * @param string      $text   Text to check
     * @param string|null $locale Locale code (null = current)
     *
     * @return bool True if translation exists
     */
    public function hasTranslation(string $text, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->locale;

        return isset($this->translations[$locale][$text]);
    }

    /**
     * Pluralize text based on count.
     *
     * @param int         $count    Count
     * @param string      $singular Singular form
     * @param string|null $plural   Plural form (null = add 's')
     *
     * @return string Pluralized text
     */
    public function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        if (1 === $count) {
            return $this->translate($singular);
        }

        if (null === $plural) {
            $plural = $singular . 's';
        }

        return $this->translate($plural);
    }

    /**
     * Translate with count.
     *
     * @param int         $count    Count
     * @param string      $singular Singular form
     * @param string|null $plural   Plural form
     *
     * @return string Formatted translation with count
     */
    public function translateWithCount(int $count, string $singular, ?string $plural = null): string
    {
        $text = $this->pluralize($count, $singular, $plural);

        return \sprintf('%d %s', $count, $text);
    }

    /**
     * Clear all translations.
     */
    public function clear(): void
    {
        $this->translations = [];
        $this->loadedFiles = [];
    }

    /**
     * Get loaded translation files.
     *
     * @return array File paths
     */
    public function getLoadedFiles(): array
    {
        return $this->loadedFiles;
    }
}
