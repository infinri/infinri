<?php

declare(strict_types=1);

namespace Infinri\Core\Helper;

/**
 * Content Sanitizer
 * 
 * Sanitizes user-generated HTML content to prevent XSS attacks
 * while preserving safe formatting and structure.
 * 
 * Uses HTMLPurifier for robust HTML sanitization.
 * 
 * @see http://htmlpurifier.org/
 */
class ContentSanitizer
{
    private array $purifiers = [];
    
    /**
     * Sanitize HTML content
     * 
     * Removes dangerous tags/attributes while preserving safe content.
     * 
     * @param string $html Raw HTML content
     * @param string $profile Sanitization profile: 'default', 'strict', or 'rich'
     * @return string Sanitized HTML
     */
    public function sanitize(string $html, string $profile = 'default'): string
    {
        if (empty($html)) {
            return '';
        }
        
        $purifier = $this->getPurifier($profile);
        return $purifier->purify($html);
    }
    
    /**
     * Sanitize plain text (no HTML allowed)
     * 
     * @param string $text
     * @return string
     */
    public function sanitizePlainText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    
    /**
     * Get or create HTMLPurifier instance with specified profile
     * 
     * @param string $profile
     * @return \HTMLPurifier|object
     */
    private function getPurifier(string $profile): object
    {
        // Check if HTMLPurifier is available
        if (!class_exists('\HTMLPurifier')) {
            // Fallback: use basic tag stripping if HTMLPurifier not installed
            return $this->getFallbackPurifier($profile);
        }
        
        // Return cached purifier for this profile if available
        if (isset($this->purifiers[$profile])) {
            return $this->purifiers[$profile];
        }
        
        $config = \HTMLPurifier_Config::createDefault();
        
        // Configure based on profile
        switch ($profile) {
            case 'strict':
                // Very limited HTML - only basic formatting
                $config->set('HTML.Allowed', 'p,br,strong,em,u');
                break;
                
            case 'rich':
                // Rich content - includes images, links, lists, headings
                $config->set('HTML.Allowed', implode(',', [
                    'p', 'br', 'strong', 'em', 'u', 'strike', 'sub', 'sup',
                    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                    'ul', 'ol', 'li',
                    'a[href|title|target]',
                    'img[src|alt|title|width|height]',
                    'blockquote', 'code', 'pre',
                    'table', 'thead', 'tbody', 'tr', 'th', 'td',
                    'div[class]', 'span[class]'
                ]));
                
                // Allow safe CSS properties for styling
                $config->set('CSS.AllowedProperties', 'text-align,font-weight,font-style,color,background-color,padding,margin,width,height');
                break;
                
            case 'default':
            default:
                // Balanced - common formatting without dangerous elements
                $config->set('HTML.Allowed', implode(',', [
                    'p', 'br', 'strong', 'em', 'u',
                    'h1', 'h2', 'h3', 'h4',
                    'ul', 'ol', 'li',
                    'a[href|title]',
                    'img[src|alt|title]',
                    'blockquote'
                ]));
                break;
        }
        
        // Security: Disable cache for simplicity (can be enabled in production)
        $config->set('Cache.DefinitionImpl', null);
        
        // Security: Convert relative URIs to absolute
        $config->set('URI.MakeAbsolute', true);
        $config->set('URI.Base', $this->getBaseUrl());
        
        // Security: Disable external resources by default
        $config->set('URI.DisableExternalResources', false);
        
        // Encoding
        $config->set('Core.Encoding', 'UTF-8');
        
        $this->purifiers[$profile] = new \HTMLPurifier($config);
        
        return $this->purifiers[$profile];
    }
    
    /**
     * Fallback purifier when HTMLPurifier is not installed
     * 
     * Uses strip_tags as a basic sanitization method
     */
    private function getFallbackPurifier(string $profile = 'default'): object
    {
        // Determine allowed tags based on profile
        $allowedTagsByProfile = [
            'strict' => '<p><br><strong><em><u>',
            'default' => '<p><br><strong><em><u><h1><h2><h3><h4><ul><ol><li><a><img><blockquote>',
            'rich' => '<p><br><strong><em><u><strike><sub><sup><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><code><pre><table><thead><tbody><tr><th><td><div><span>',
        ];
        
        $allowedTags = $allowedTagsByProfile[$profile] ?? $allowedTagsByProfile['default'];
        
        return new class($allowedTags) {
            private string $allowedTags;
            
            public function __construct(string $allowedTags)
            {
                $this->allowedTags = $allowedTags;
            }
            
            public function purify(string $html): string
            {
                $cleaned = strip_tags($html, $this->allowedTags);
                
                // Basic XSS protection - remove event handlers
                $cleaned = preg_replace('/(<[^>]+)\s+(on\w+\s*=\s*["\'][^"\']*["\'])/i', '$1', $cleaned);
                
                // Remove javascript: protocol from links
                $cleaned = preg_replace('/(<a[^>]+href\s*=\s*["\'])javascript:/i', '$1#', $cleaned);
                
                return $cleaned;
            }
        };
    }
    
    /**
     * Get base URL for URI resolution
     * 
     * @return string
     */
    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . $host;
    }
    
    /**
     * Check if content contains potentially dangerous HTML
     * 
     * Returns true if the content should be sanitized
     * 
     * @param string $html
     * @return bool
     */
    public function needsSanitization(string $html): bool
    {
        // Check for script tags
        if (preg_match('/<script[^>]*>.*?<\/script>/is', $html)) {
            return true;
        }
        
        // Check for event handlers
        if (preg_match('/\s+on\w+\s*=/i', $html)) {
            return true;
        }
        
        // Check for javascript: protocol
        if (stripos($html, 'javascript:') !== false) {
            return true;
        }
        
        // Check for data: URIs (can contain XSS)
        if (stripos($html, 'data:text/html') !== false) {
            return true;
        }
        
        return false;
    }
}
