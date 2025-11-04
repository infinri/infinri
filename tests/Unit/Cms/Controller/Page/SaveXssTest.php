<?php

declare(strict_types=1);

use Infinri\Cms\Controller\Adminhtml\Page\Save;
use Infinri\Core\App\Request;
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Helper\ContentSanitizer;

beforeEach(function () {
    $this->pageRepository = $this->createMock(PageRepository::class);
    $this->contentSanitizer = new ContentSanitizer();
    $this->csrfGuard = $this->createMock(CsrfGuard::class);
    
    $this->controller = new Save(
        $this->pageRepository,
        $this->contentSanitizer,
        $this->csrfGuard
    );
    
    $this->request = $this->createMock(Request::class);
    
    // Helper to setup save capture
    $this->setupSaveCapture = function (&$savedData) {
        $savedData = []; // Initialize array
        $mockPage = $this->createMock(\Infinri\Cms\Model\Page::class);
        
        // Capture data via setter methods
        $mockPage->method('setTitle')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['title'] = $value;
            return $mockPage;
        });
        $mockPage->method('setUrlKey')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['url_key'] = $value;
            return $mockPage;
        });
        $mockPage->method('setContent')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['content'] = $value;
            return $mockPage;
        });
        $mockPage->method('setMetaTitle')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['meta_title'] = $value;
            return $mockPage;
        });
        $mockPage->method('setMetaDescription')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['meta_description'] = $value;
            return $mockPage;
        });
        $mockPage->method('setMetaKeywords')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['meta_keywords'] = $value;
            return $mockPage;
        });
        $mockPage->method('setIsActive')->willReturnCallback(function ($value) use (&$savedData, $mockPage) {
            $savedData['is_active'] = $value;
            return $mockPage;
        });
        
        $mockPage->page_id = 1;
        
        $this->pageRepository->method('create')->willReturn($mockPage);
        $this->pageRepository->method('save')->willReturnCallback(function ($page) {
            return $page;
        });
        $this->pageRepository->method('getById')->willReturn(null); // Force create path
    };
    
    // Start session for tests
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
});

afterEach(function () {
    $_SESSION = [];
});

describe('CMS Page Save XSS Protection', function () {
    
    it('strips script tags from content', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', '<p>Safe content</p><script>alert("XSS")</script>'],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        // Capture the data being saved
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Verify script tag was removed
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], '<script>'))->toBeFalse()
            ->and(str_contains($savedData['content'], 'alert'))->toBeFalse()
            ->and(str_contains($savedData['content'], '<p>Safe content</p>'))->toBeTrue();
    });
    
    it('removes event handlers from HTML', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', '<div onclick="alert(1)">Click me</div>'],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Verify onclick handler was removed
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], 'onclick'))->toBeFalse()
            ->and(str_contains($savedData['content'], 'alert'))->toBeFalse();
    });
    
    it('blocks javascript protocol in links', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', '<a href="javascript:alert(1)">Click</a>'],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Verify javascript: protocol was blocked
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], 'javascript:'))->toBeFalse();
    });
    
    it('preserves safe HTML formatting', function () {
        $safeHtml = '<h1>Heading</h1><p>Paragraph with <strong>bold</strong> and <em>italic</em></p><ul><li>List item</li></ul>';
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', $safeHtml],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Verify safe HTML is preserved
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], '<h1>Heading</h1>'))->toBeTrue()
            ->and(str_contains($savedData['content'], '<strong>bold</strong>'))->toBeTrue()
            ->and(str_contains($savedData['content'], '<em>italic</em>'))->toBeTrue()
            ->and(str_contains($savedData['content'], '<ul>'))->toBeTrue()
            ->and(str_contains($savedData['content'], '<li>List item</li>'))->toBeTrue();
    });
    
    it('allows safe images and links', function () {
        $htmlWithMedia = '<p>Check this <a href="https://example.com">link</a></p><img src="/images/test.jpg" alt="Test">';
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', $htmlWithMedia],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Verify safe media elements are preserved
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], '<a'))->toBeTrue()
            ->and(str_contains($savedData['content'], 'href'))->toBeTrue()
            ->and(str_contains($savedData['content'], '<img'))->toBeTrue()
            ->and(str_contains($savedData['content'], 'src'))->toBeTrue();
    });
    
    it('sanitizes on save not display', function () {
        // This test verifies the architectural decision to sanitize on input
        $maliciousContent = '<p>Test</p><script>alert(1)</script>';
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['title', '', 'Test Page'],
                ['url_key', '', 'test-page'],
                ['content', '', $maliciousContent],
                ['meta_title', '', ''],
                ['meta_description', '', ''],
                ['meta_keywords', '', ''],
                ['is_active', false, true],
                ['_csrf_token', '', 'valid_token'],
                ['action', '', 'save']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $savedData = null;
        ($this->setupSaveCapture)($savedData);
        
        $this->controller->execute($this->request);
        
        // Content should already be sanitized in the database
        // No need to sanitize again on display
        expect($savedData)->toBeArray()
            ->and($savedData)->toHaveKey('content');
        expect(str_contains($savedData['content'], '<script>'))->toBeFalse();
    });
});

describe('ContentSanitizer Direct Tests', function () {
    
    it('detects dangerous content', function () {
        $sanitizer = new ContentSanitizer();
        
        expect($sanitizer->needsSanitization('<script>alert(1)</script>'))->toBeTrue()
            ->and($sanitizer->needsSanitization('<div onclick="alert(1)">test</div>'))->toBeTrue()
            ->and($sanitizer->needsSanitization('<a href="javascript:alert(1)">link</a>'))->toBeTrue()
            ->and($sanitizer->needsSanitization('<p>Safe content</p>'))->toBeFalse();
    });
    
    it('sanitizes with different profiles', function () {
        $sanitizer = new ContentSanitizer();
        $dangerousHtml = '<p>Test</p><script>alert(1)</script><h1>Heading</h1>';
        
        // Strict profile - very limited HTML
        $strictResult = $sanitizer->sanitize($dangerousHtml, 'strict');
        expect($strictResult)->not->toContain('<script>')
            ->and($strictResult)->not->toContain('<h1>'); // h1 not allowed in strict
        
        // Rich profile - allows more formatting
        $richResult = $sanitizer->sanitize($dangerousHtml, 'rich');
        expect($richResult)->not->toContain('<script>')
            ->and($richResult)->toContain('<h1>Heading</h1>'); // h1 allowed in rich
    });
    
    it('sanitizes plain text removes all HTML', function () {
        $sanitizer = new ContentSanitizer();
        $html = '<p>Test</p><strong>Bold</strong>';
        
        $result = $sanitizer->sanitizePlainText($html);
        
        expect($result)->not->toContain('<p>')
            ->and($result)->not->toContain('<strong>')
            ->and($result)->toContain('Test')
            ->and($result)->toContain('Bold');
    });
});
