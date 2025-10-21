<?php

declare(strict_types=1);

use Infinri\Core\Helper\ContentSanitizer;

describe('ContentSanitizer', function () {
    beforeEach(function () {
        $this->sanitizer = new ContentSanitizer();
    });

    describe('sanitizePlainText', function () {
        it('escapes HTML entities', function () {
            $input = '<script>alert("XSS")</script>';
            $output = $this->sanitizer->sanitizePlainText($input);
            
            expect($output)->toBe('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;');
        });

        it('escapes quotes', function () {
            $input = 'Test "quoted" text';
            $output = $this->sanitizer->sanitizePlainText($input);
            
            expect($output)->toContain('&quot;');
        });

        it('preserves safe text', function () {
            $input = 'Hello World 123';
            $output = $this->sanitizer->sanitizePlainText($input);
            
            expect($output)->toBe('Hello World 123');
        });
    });

    describe('needsSanitization', function () {
        it('detects script tags', function () {
            $html = '<p>Safe content</p><script>alert("XSS")</script>';
            
            expect($this->sanitizer->needsSanitization($html))->toBeTrue();
        });

        it('detects event handlers', function () {
            $html = '<img src="test.jpg" onerror="alert(1)">';
            
            expect($this->sanitizer->needsSanitization($html))->toBeTrue();
        });

        it('detects javascript: protocol', function () {
            $html = '<a href="javascript:alert(1)">Click</a>';
            
            expect($this->sanitizer->needsSanitization($html))->toBeTrue();
        });

        it('detects data: text/html URIs', function () {
            $html = '<iframe src="data:text/html,<script>alert(1)</script>"></iframe>';
            
            expect($this->sanitizer->needsSanitization($html))->toBeTrue();
        });

        it('returns false for safe HTML', function () {
            $html = '<p><strong>Safe</strong> content with <a href="/page">link</a></p>';
            
            expect($this->sanitizer->needsSanitization($html))->toBeFalse();
        });
    });

    describe('sanitize with fallback', function () {
        it('removes script tags', function () {
            $input = '<p>Safe</p><script>alert("XSS")</script>';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->not->toContain('<script>');
            expect($output)->toContain('<p>Safe</p>');
        });

        it('removes event handlers', function () {
            $input = '<img src="test.jpg" onerror="alert(1)">';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->not->toContain('onerror');
        });

        it('removes javascript: protocol', function () {
            $input = '<a href="javascript:alert(1)">Click</a>';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->not->toContain('javascript:');
        });

        it('preserves safe HTML', function () {
            $input = '<p><strong>Bold</strong> and <em>italic</em></p>';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->toContain('<strong>Bold</strong>');
            expect($output)->toContain('<em>italic</em>');
        });

        it('allows links in default profile', function () {
            $input = '<a href="/safe-page">Link</a>';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->toContain('<a href=');
        });

        it('allows images in default profile', function () {
            $input = '<img src="/image.jpg" alt="Test">';
            $output = $this->sanitizer->sanitize($input, 'default');
            
            expect($output)->toContain('<img');
        });
    });

    describe('sanitize profiles', function () {
        it('strict profile allows minimal HTML', function () {
            $input = '<p>Text</p><h1>Heading</h1><a href="#">Link</a>';
            $output = $this->sanitizer->sanitize($input, 'strict');
            
            expect($output)->toContain('<p>Text</p>');
            expect($output)->not->toContain('<h1>');
            expect($output)->not->toContain('<a');
        });

        it('handles empty strings', function () {
            expect($this->sanitizer->sanitize(''))->toBe('');
            expect($this->sanitizer->sanitizePlainText(''))->toBe('');
        });
    });
});
