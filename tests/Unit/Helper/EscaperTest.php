<?php

declare(strict_types=1);

use Infinri\Core\Helper\Escaper;

describe('Escaper Helper', function () {
    
    beforeEach(function () {
        $this->helper = new Escaper();
    });
    
    it('can escape HTML', function () {
        $html = '<script>alert("XSS")</script>';
        
        $escaped = $this->helper->escapeHtml($html);
        
        expect($escaped)->not->toContain('<script>');
        expect($escaped)->toContain('&lt;script&gt;');
    });
    
    it('can escape HTML attributes', function () {
        $value = 'test" onclick="alert(1)"';
        
        $escaped = $this->helper->escapeHtmlAttr($value);
        
        expect($escaped)->toContain('&quot;');
        expect($escaped)->not->toContain('<');
    });
    
    it('can escape JavaScript', function () {
        $js = "alert('test')";
        
        $escaped = $this->helper->escapeJs($js);
        
        expect($escaped)->toContain("\\'");
    });
    
    it('can escape URL parameters', function () {
        $url = 'hello world';
        
        $escaped = $this->helper->escapeUrl($url);
        
        expect($escaped)->toBe('hello%20world');
    });
    
    it('can escape CSS', function () {
        $css = 'test; background: url(javascript:alert(1))';
        
        $escaped = $this->helper->escapeCss($css);
        
        expect($escaped)->toMatch('/^[a-zA-Z0-9\s\-_]+$/');
        expect($escaped)->not->toContain(';');
    });
    
    it('can strip HTML tags', function () {
        $html = '<p>Hello <strong>World</strong></p>';
        
        $stripped = $this->helper->stripTags($html);
        
        expect($stripped)->toBe('Hello World');
    });
    
    it('can strip HTML tags with allowed tags', function () {
        $html = '<p>Hello <strong>World</strong> <script>alert(1)</script></p>';
        
        $stripped = $this->helper->stripTags($html, ['p', 'strong']);
        
        expect($stripped)->toContain('<p>');
        expect($stripped)->toContain('<strong>');
        expect($stripped)->not->toContain('<script>');
    });
    
    it('can sanitize filename', function () {
        $filename = '../../../etc/passwd';
        
        $safe = $this->helper->sanitizeFilename($filename);
        
        expect($safe)->not->toContain('/');
        expect($safe)->not->toContain('\\');
    });
    
    it('can sanitize email', function () {
        expect($this->helper->sanitizeEmail('test@example.com'))->toBe('test@example.com');
        expect($this->helper->sanitizeEmail('invalid email'))->toBeNull();
    });
    
    it('can sanitize URL', function () {
        expect($this->helper->sanitizeUrl('https://example.com'))->toBe('https://example.com');
        expect($this->helper->sanitizeUrl('javascript:alert(1)'))->toBeNull();
    });
    
    it('can extract alphanumeric characters', function () {
        expect($this->helper->alphanumeric('test123!@#$'))->toBe('test123');
    });
    
    it('can clean string without HTML', function () {
        $dirty = '<p>Hello <script>alert(1)</script> World</p>';
        
        $clean = $this->helper->clean($dirty, false);
        
        expect($clean)->not->toContain('<script>');
        expect($clean)->not->toContain('<p>');
    });
    
    it('can clean string with safe HTML', function () {
        $dirty = '<p>Hello <strong>World</strong> <script>alert(1)</script></p>';
        
        $clean = $this->helper->clean($dirty, true);
        
        expect($clean)->toContain('<p>');
        expect($clean)->toContain('<strong>');
        expect($clean)->not->toContain('<script>');
    });
    
    it('can escape for JSON', function () {
        $data = ['<script>', '"quotes"', "line\nbreak"];
        
        $json = $this->helper->escapeJson($data);
        
        expect($json)->toBeString();
        expect($json)->toContain('\\u003C');
    });
    
    it('can sanitize integer', function () {
        expect($this->helper->sanitizeInt('123'))->toBe(123);
        expect($this->helper->sanitizeInt('abc', 10))->toBe(10);
        expect($this->helper->sanitizeInt('12.5', 0))->toBe(0);
    });
    
    it('can sanitize float', function () {
        expect($this->helper->sanitizeFloat('12.5'))->toBe(12.5);
        expect($this->helper->sanitizeFloat('abc', 1.5))->toBe(1.5);
    });
    
    it('can sanitize boolean', function () {
        expect($this->helper->sanitizeBool('true'))->toBeTrue();
        expect($this->helper->sanitizeBool('false'))->toBeFalse();
        expect($this->helper->sanitizeBool('1'))->toBeTrue();
        expect($this->helper->sanitizeBool('0'))->toBeFalse();
        expect($this->helper->sanitizeBool('invalid', true))->toBeTrue();
    });
});
