<?php

declare(strict_types=1);

namespace Infinri\Core\Security;

/**
 * Lightweight CSRF guard backed by PHP sessions.
 * Avoids external framework dependencies while keeping API compatible
 * with existing form renderer usage.
 */
class CsrfGuard
{
    private const SESSION_KEY = '_csrf_tokens';

    public function generateToken(string $tokenId): string
    {
        $this->ensureSessionStarted();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY][$tokenId] = [
            'value' => $token,
            'generated_at' => time(),
        ];

        return $token;
    }

    public function validateToken(string $tokenId, ?string $tokenValue): bool
    {
        if ($tokenValue === null || $tokenValue === '') {
            return false;
        }

        $this->ensureSessionStarted();

        $stored = $_SESSION[self::SESSION_KEY][$tokenId] ?? null;
        if (!$stored) {
            return false;
        }

        // Optional expiration (default 1 hour)
        $isExpired = ($stored['generated_at'] ?? 0) < (time() - 3600);
        if ($isExpired) {
            unset($_SESSION[self::SESSION_KEY][$tokenId]);
            return false;
        }

        $isValid = hash_equals($stored['value'], $tokenValue);

        // One-time use: rotate token after successful validation
        if ($isValid) {
            unset($_SESSION[self::SESSION_KEY][$tokenId]);
        }

        return $isValid;
    }

    public function getHiddenField(string $tokenId, string $fieldName = '_csrf_token'): string
    {
        $token = $this->generateToken($tokenId);

        return sprintf(
            '<input type="hidden" name="%s" value="%s" />',
            htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    public function appendFieldToHtml(string $html, string $tokenId, string $fieldName = '_csrf_token'): string
    {
        return $html . $this->getHiddenField($tokenId, $fieldName);
    }

    private function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }
}
