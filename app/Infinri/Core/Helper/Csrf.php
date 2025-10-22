<?php
declare(strict_types=1);

namespace Infinri\Core\Helper;

use Infinri\Core\Security\CsrfTokenManager;

/**
 * CSRF Helper
 * 
 * Template helper for generating CSRF form fields
 */
class Csrf
{
    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {}

    /**
     * Generate CSRF hidden input fields for forms
     *
     * @param string $tokenId Token identifier (unique per form type)
     * @return string HTML hidden inputs
     */
    public function getFormFields(string $tokenId = 'default'): string
    {
        $token = $this->csrfManager->generateToken($tokenId);
        $escaper = new Escaper();
        
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">' . "\n" .
            '<input type="hidden" name="_csrf_token_id" value="%s">',
            $escaper->escapeHtmlAttr($token),
            $escaper->escapeHtmlAttr($tokenId)
        );
    }

    /**
     * Get CSRF token value
     *
     * @param string $tokenId
     * @return string
     */
    public function getToken(string $tokenId = 'default'): string
    {
        return $this->csrfManager->generateToken($tokenId);
    }

    /**
     * Validate CSRF token from request
     *
     * @param string $tokenId
     * @param string $token
     * @return bool
     */
    public function validateToken(string $tokenId, string $token): bool
    {
        return $this->csrfManager->validateToken($tokenId, $token);
    }
}
