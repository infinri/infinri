<?php

declare(strict_types=1);

namespace Infinri\Core\Security;

use Infinri\Core\App\Session;

/**
 * Lightweight CSRF guard backed by PHP sessions.
 * Avoids external framework dependencies while keeping API compatible
 * with existing form renderer usage.
 * 
 * Phase 2.2: Migrated to use Session service for consistency
 */
class CsrfGuard
{
    private const SESSION_KEY = '_csrf_tokens';

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function generateToken(string $tokenId): string
    {
        $this->session->start();

        $token = bin2hex(random_bytes(32));
        
        // Get existing tokens array or create new
        $tokens = $this->session->get(self::SESSION_KEY, []);
        $tokens[$tokenId] = [
            'value' => $token,
            'generated_at' => time(),
        ];
        $this->session->set(self::SESSION_KEY, $tokens);

        return $token;
    }

    public function validateToken(string $tokenId, ?string $tokenValue): bool
    {
        if ($tokenValue === null || $tokenValue === '') {
            return false;
        }

        $this->session->start();

        $tokens = $this->session->get(self::SESSION_KEY, []);
        $stored = $tokens[$tokenId] ?? null;
        
        if (!$stored) {
            return false;
        }

        // Optional expiration (default 1 hour)
        $isExpired = ($stored['generated_at'] ?? 0) < (time() - 3600);
        if ($isExpired) {
            unset($tokens[$tokenId]);
            $this->session->set(self::SESSION_KEY, $tokens);
            return false;
        }

        $isValid = hash_equals($stored['value'], $tokenValue);

        // One-time use: rotate token after successful validation
        if ($isValid) {
            unset($tokens[$tokenId]);
            $this->session->set(self::SESSION_KEY, $tokens);
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
}
