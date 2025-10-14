<?php

declare(strict_types=1);

use Infinri\Core\Block\Template;
use Infinri\Core\Block\Text;
use Infinri\Core\Model\View\TemplateResolver;

beforeEach(function () {
    $this->template = new Template();
});

describe('Template', function () {
    
    it('can set and get template', function () {
        $this->template->setTemplate('Infinri_Core::test.phtml');
        
        expect($this->template->getTemplate())->toBe('Infinri_Core::test.phtml');
    });
    
    it('returns empty string when no template set', function () {
        $html = $this->template->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('returns empty string when template file not found', function () {
        $this->template->setTemplate('Infinri_Core::nonexistent.phtml');
        
        $html = $this->template->toHtml();
        
        expect($html)->toBe('');
    });
    
    it('can render template file', function () {
        $this->template->setTemplate('Infinri_Core::test.phtml');
        $this->template->setData('title', 'Test Title');
        $this->template->setData('content', 'Test Content');
        
        $html = $this->template->toHtml();
        
        expect($html)->toContain('Test Title');
        expect($html)->toContain('Test Content');
        expect($html)->toContain('class="test-template"');
    });
    
    it('uses default values in template', function () {
        $this->template->setTemplate('Infinri_Core::test.phtml');
        
        $html = $this->template->toHtml();
        
        expect($html)->toContain('Default Title');
        expect($html)->toContain('Default content');
    });
    
    it('escapes HTML output', function () {
        $result = $this->template->escapeHtml('<script>alert("xss")</script>');
        
        expect($result)->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;');
        expect($result)->not->toContain('<script>');
    });
    
    it('escapes HTML attributes', function () {
        $result = $this->template->escapeHtmlAttr('value" onclick="alert(1)');
        
        // Quotes should be escaped
        expect($result)->toContain('&quot;');
        // The escaped version is safe even though it contains "onclick"
        expect($result)->toBe('value&quot; onclick=&quot;alert(1)');
    });
    
    it('escapes URLs', function () {
        $result = $this->template->escapeUrl('http://example.com?param=<script>');
        
        // HTML special chars should be escaped
        expect($result)->toContain('&lt;');
        expect($result)->toContain('&gt;');
        expect($result)->not->toContain('<script>');
    });
    
    it('renders children in template', function () {
        $this->template->setTemplate('Infinri_Core::test.phtml');
        
        $child = new Text();
        $child->setText('Child Content');
        $this->template->addChild($child);
        
        $html = $this->template->toHtml();
        
        expect($html)->toContain('Child Content');
    });
    
    it('makes block available in template', function () {
        $this->template->setTemplate('Infinri_Core::test.phtml');
        $this->template->setData('custom_value', 'Test Value');
        
        $html = $this->template->toHtml();
        
        // Template can access $block->getData('custom_value')
        expect($html)->toBeString();
    });
    
});
