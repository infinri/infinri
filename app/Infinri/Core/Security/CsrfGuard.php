<?php

declare(strict_types=1);

namespace Infinri\Core\Security;

use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class CsrfGuard
{
    public function __construct(private readonly CsrfTokenManagerInterface $tokenManager)
    {
    }

    public function generateToken(string $tokenId): string
    {
        $this->ensureSessionStarted();
        return $this->tokenManager->getToken($tokenId)->getValue();
    }

    public function validateToken(string $tokenId, ?string $tokenValue): bool
    {
        if ($tokenValue === null || $tokenValue === '') {
            return false;
        }

        $this->ensureSessionStarted();
        return $this->tokenManager->isTokenValid(new CsrfToken($tokenId, $tokenValue));
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
    }
}
