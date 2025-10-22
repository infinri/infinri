<?php
declare(strict_types=1);

namespace Infinri\Admin\Service;

use Infinri\Admin\Model\ResourceModel\RememberToken as RememberTokenResource;
use Infinri\Core\Helper\Logger;

/**
 * Remember Token Service
 * 
 * Handles remember-me token generation, validation, and cleanup
 */
class RememberTokenService
{
    private const TOKEN_LENGTH = 32; // 32 bytes = 64 hex chars
    private const COOKIE_NAME = 'admin_remember';
    private const TOKEN_LIFETIME_DAYS = 30;

    public function __construct(
        private readonly RememberTokenResource $tokenResource
    ) {}

    /**
     * Generate and store a new remember token
     * 
     * @return string The plaintext token to set in cookie
     */
    public function generateToken(int $userId, string $ipAddress, string $userAgent): string
    {
        // Generate cryptographically secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Hash the token before storing (never store plaintext)
        $tokenHash = hash('sha256', $token);
        
        // Store in database
        $this->tokenResource->createToken(
            $userId,
            $tokenHash,
            $ipAddress,
            $userAgent,
            self::TOKEN_LIFETIME_DAYS
        );
        
        Logger::info('Remember token created', ['user_id' => $userId]);
        
        return $token;
    }

    /**
     * Validate a remember token and return user ID if valid
     */
    public function validateToken(string $token): int|false
    {
        $tokenHash = hash('sha256', $token);
        
        $tokenData = $this->tokenResource->findByTokenHash($tokenHash);
        
        if (!$tokenData) {
            Logger::debug('Remember token not found', ['token_hash' => substr($tokenHash, 0, 8) . '...']);
            return false;
        }
        
        // Check if token expired
        $expiresAt = strtotime($tokenData['expires_at']);
        if ($expiresAt < time()) {
            Logger::info('Remember token expired', ['token_id' => $tokenData['token_id']]);
            $this->tokenResource->deleteByTokenHash($tokenHash);
            return false;
        }
        
        // Update last used timestamp
        $this->tokenResource->updateLastUsed($tokenData['token_id']);
        
        Logger::info('Remember token validated', [
            'user_id' => $tokenData['user_id'],
            'token_id' => $tokenData['token_id']
        ]);
        
        return (int)$tokenData['user_id'];
    }

    /**
     * Set remember cookie in browser
     */
    public function setRememberCookie(string $token): bool
    {
        $expires = time() + (self::TOKEN_LIFETIME_DAYS * 24 * 60 * 60);
        
        return setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
    }

    /**
     * Get remember token from cookie
     */
    public function getRememberCookie(): ?string
    {
        return $_COOKIE[self::COOKIE_NAME] ?? null;
    }

    /**
     * Delete remember cookie
     */
    public function deleteRememberCookie(): bool
    {
        unset($_COOKIE[self::COOKIE_NAME]);
        
        return setcookie(
            self::COOKIE_NAME,
            '',
            [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true
            ]
        );
    }

    /**
     * Revoke token (delete from database)
     */
    public function revokeToken(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $this->tokenResource->deleteByTokenHash($tokenHash);
        Logger::info('Remember token revoked');
    }

    /**
     * Revoke all tokens for a user
     */
    public function revokeAllUserTokens(int $userId): void
    {
        $this->tokenResource->deleteByUserId($userId);
        Logger::info('All remember tokens revoked for user', ['user_id' => $userId]);
    }

    /**
     * Clean up expired tokens (run periodically)
     */
    public function cleanupExpiredTokens(): int
    {
        $deleted = $this->tokenResource->deleteExpired();
        Logger::info('Expired remember tokens cleaned up', ['count' => $deleted]);
        return $deleted;
    }
}
