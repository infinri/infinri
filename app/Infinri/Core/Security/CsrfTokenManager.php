<?php

declare(strict_types=1);

namespace Infinri\Core\Security;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManager as SymfonyCsrfTokenManager;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\NativeSessionTokenStorage;

/**
 * Wrapper around Symfony Security CSRF for token generation and validation.
 */
class CsrfTokenManager
{
    private SymfonyCsrfTokenManager $manager;

    public function __construct()
    {
        // Start session if not already started
        if (\PHP_SESSION_NONE === session_status()) {
            session_start();
        }

        // Initialize Symfony CSRF manager with native session storage
        $this->manager = new SymfonyCsrfTokenManager(
            new UriSafeTokenGenerator(),
            new NativeSessionTokenStorage()
        );
    }

    /**
     * Generate CSRF token for given ID.
     *
     * @param string $tokenId Token identifier (e.g., 'page_form', 'delete_action')
     */
    public function generateToken(string $tokenId = 'default'): string
    {
        return $this->manager->getToken($tokenId)->getValue();
    }

    /**
     * Validate CSRF token.
     *
     * @param string $tokenId Token identifier
     * @param string $token   Token value to validate
     */
    public function validateToken(string $tokenId, string $token): bool
    {
        return $this->manager->isTokenValid(new CsrfToken($tokenId, $token));
    }

    /**
     * Remove token from storage.
     */
    public function removeToken(string $tokenId): void
    {
        $this->manager->removeToken($tokenId);
    }

    /**
     * Refresh token (remove old, generate new).
     *
     * @return string New token
     */
    public function refreshToken(string $tokenId): string
    {
        $this->removeToken($tokenId);

        return $this->generateToken($tokenId);
    }
}
