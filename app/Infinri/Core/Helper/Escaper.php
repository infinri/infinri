<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

/**
 * XSS protection and output escaping utilities
 */
class Escaper
{
    /**
     * Escape HTML special characters
     *
     * @param string $string String to escape
     * @param int $flags ENT_* flags
     * @param string $encoding Character encoding
     * @return string Escaped string
     */
    public function escapeHtml(string $string, int $flags = ENT_QUOTES | ENT_SUBSTITUTE, string $encoding = 'UTF-8'): string
    {
        return htmlspecialchars($string, $flags, $encoding);
    }

    /**
     * Escape HTML attribute value
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeHtmlAttr(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Escape JavaScript string
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeJs(string $string): string
    {
        // Escape special characters for JavaScript
        return str_replace(
            ['\\', "'", '"', "\n", "\r", "\t", '<', '>'],
            ['\\\\', "\\'", '\\"', '\\n', '\\r', '\\t', '\\x3C', '\\x3E'],
            $string
        );
    }

    /**
     * Escape URL parameter
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeUrl(string $string): string
    {
        return rawurlencode($string);
    }

    /**
     * Escape CSS string
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escapeCss(string $string): string
    {
        // Remove potentially dangerous characters
        $result = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $string);
        if ($result === null) {
            throw new \RuntimeException('Failed to escape CSS string');
        }
        return $result;
    }

    /**
     * Strip HTML tags
     *
     * @param string $string String to clean
     * @param array<string> $allowedTags Allowed tags (e.g., ['p', 'br'])
     * @return string Cleaned string
     */
    public function stripTags(string $string, array $allowedTags = []): string
    {
        if (empty($allowedTags)) {
            return strip_tags($string);
        }

        $allowed = '<' . implode('><', $allowedTags) . '>';
        return strip_tags($string, $allowed);
    }

    /**
     * Sanitize filename
     *
     * @param string $filename Filename to sanitize
     * @return string Safe filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove path separators
        $filename = str_replace(['/', '\\'], '', $filename);

        // Remove special characters
        $result = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        if ($result === null) {
            throw new \RuntimeException('Failed to sanitize filename');
        }
        return $result;
    }

    /**
     * Sanitize email address
     *
     * @param string $email Email to sanitize
     * @return string|null Sanitized email or null if invalid
     */
    public function sanitizeEmail(string $email): ?string
    {
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if ($sanitized === false) {
            return null;
        }

        if (filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return $sanitized;
        }

        return null;
    }

    /**
     * Sanitize URL
     *
     * @param string $url URL to sanitize
     * @return string|null Sanitized URL or null if invalid
     */
    public function sanitizeUrl(string $url): ?string
    {
        $sanitized = filter_var($url, FILTER_SANITIZE_URL);
        
        if ($sanitized === false) {
            return null;
        }

        if (filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return $sanitized;
        }

        return null;
    }

    /**
     * Remove all non-alphanumeric characters
     *
     * @param string $string String to clean
     * @return string Cleaned string
     */
    public function alphanumeric(string $string): string
    {
        $result = preg_replace('/[^a-zA-Z0-9]/', '', $string);
        if ($result === null) {
            throw new \RuntimeException('Failed to clean alphanumeric string');
        }
        return $result;
    }

    /**
     * Clean string for safe output (comprehensive)
     *
     * @param string $string String to clean
     * @param bool $allowHtml Allow basic HTML tags
     * @return string Cleaned string
     */
    public function clean(string $string, bool $allowHtml = false): string
    {
        if ($allowHtml) {
            // Allow only safe HTML tags
            $safeTags = ['p', 'br', 'strong', 'em', 'u', 'a', 'ul', 'ol', 'li'];
            $string = $this->stripTags($string, $safeTags);

            // Remove dangerous attributes
            $cleaned = preg_replace('/<([a-z]+)[^>]*?(on\w+\s*=)[^>]*>/i', '<$1>', $string);
            if ($cleaned === null) {
                throw new \RuntimeException('Failed to remove dangerous attributes');
            }
            $string = $cleaned;
        } else {
            $string = strip_tags($string);
        }

        return trim($string);
    }

    /**
     * Escape for JSON output
     *
     * @param mixed $data Data to encode
     * @param int $options JSON encode options
     * @return string JSON string
     */
    public function escapeJson(mixed $data, int $options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP): string
    {
        $result = json_encode($data, $options);
        if ($result === false) {
            throw new \RuntimeException('Failed to encode JSON');
        }
        return $result;
    }

    /**
     * Validate and sanitize integer
     *
     * @param mixed $value Value to sanitize
     * @param int $default Default value
     * @return int Sanitized integer
     */
    public function sanitizeInt(mixed $value, int $default = 0): int
    {
        $filtered = filter_var($value, FILTER_VALIDATE_INT);

        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Validate and sanitize float
     *
     * @param mixed $value Value to sanitize
     * @param float $default Default value
     * @return float Sanitized float
     */
    public function sanitizeFloat(mixed $value, float $default = 0.0): float
    {
        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);

        return $filtered !== false ? $filtered : $default;
    }

    /**
     * Sanitize boolean value
     *
     * @param mixed $value Value to sanitize
     * @param bool $default Default value
     * @return bool Sanitized boolean
     */
    public function sanitizeBool(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
