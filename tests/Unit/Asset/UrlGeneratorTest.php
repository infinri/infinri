<?php

declare(strict_types=1);

use Infinri\Core\Model\Asset\UrlGenerator;

describe('Asset URL Generator', function () {
    
    beforeEach(function () {
        $this->generator = new UrlGenerator();
    });
    
    it('can generate URL for CSS asset', function () {
        $url = $this->generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->toBe('/static/Infinri/Core/css/style.css');
    });
    
    it('can generate URL for JavaScript asset', function () {
        $url = $this->generator->getUrl('Infinri_Theme::js/app.js');
        
        expect($url)->toBe('/static/Infinri/Theme/js/app.js');
    });
    
    it('converts module name underscores to slashes', function () {
        $url = $this->generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->toContain('/Infinri/Core/');
    });
    
    it('preserves file path structure', function () {
        $url = $this->generator->getUrl('Infinri_Core::css/components/button.css');
        
        expect($url)->toBe('/static/Infinri/Core/css/components/button.css');
    });
    
    it('adds version parameter when versioning enabled', function () {
        $generator = new UrlGenerator(null, true, '1.2.3');
        
        $url = $generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->toBe('/static/Infinri/Core/css/style.css?v=1.2.3');
    });
    
    it('does not add version parameter when versioning disabled', function () {
        $generator = new UrlGenerator(null, false);
        
        $url = $generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->not->toContain('?v=');
    });
    
    it('can set custom version', function () {
        $this->generator->setVersioningEnabled(true);
        $this->generator->setVersion('2.0.0-beta');
        
        $url = $this->generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->toContain('?v=2.0.0-beta');
    });
    
    it('can enable versioning after construction', function () {
        $this->generator->setVersioningEnabled(true);
        $this->generator->setVersion('1.5.0');
        
        $url = $this->generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->toBe('/static/Infinri/Core/css/style.css?v=1.5.0');
    });
    
    it('can disable versioning after construction', function () {
        $generator = new UrlGenerator(null, true, '1.0.0');
        $generator->setVersioningEnabled(false);
        
        $url = $generator->getUrl('Infinri_Core::css/style.css');
        
        expect($url)->not->toContain('?v=');
    });
    
    it('can generate URLs for multiple assets', function () {
        $assets = [
            ['path' => 'Infinri_Core::css/style.css'],
            ['path' => 'Infinri_Theme::css/theme.css'],
            ['path' => 'Infinri_Core::js/app.js'],
        ];
        
        $urls = $this->generator->getUrls($assets);
        
        expect($urls)->toHaveCount(3);
        expect($urls['Infinri_Core::css/style.css'])->toBe('/static/Infinri/Core/css/style.css');
        expect($urls['Infinri_Theme::css/theme.css'])->toBe('/static/Infinri/Theme/css/theme.css');
        expect($urls['Infinri_Core::js/app.js'])->toBe('/static/Infinri/Core/js/app.js');
    });
    
    it('throws exception for invalid asset path without separator', function () {
        expect(fn() => $this->generator->getUrl('invalid-path'))
            ->toThrow(InvalidArgumentException::class, 'Invalid asset path format');
    });
    
    it('throws exception for asset path with empty module name', function () {
        expect(fn() => $this->generator->getUrl('::css/style.css'))
            ->toThrow(InvalidArgumentException::class);
    });
    
    it('throws exception for asset path with empty file path', function () {
        expect(fn() => $this->generator->getUrl('Infinri_Core::'))
            ->toThrow(InvalidArgumentException::class);
    });
    
    it('handles deep file paths', function () {
        $url = $this->generator->getUrl('Infinri_Core::css/vendor/bootstrap/dist/bootstrap.min.css');
        
        expect($url)->toBe('/static/Infinri/Core/css/vendor/bootstrap/dist/bootstrap.min.css');
    });
    
    it('handles different file extensions', function () {
        $cssUrl = $this->generator->getUrl('Infinri_Core::css/style.less');
        $jsUrl = $this->generator->getUrl('Infinri_Core::js/app.ts');
        $fontUrl = $this->generator->getUrl('Infinri_Core::fonts/icons.woff2');
        
        expect($cssUrl)->toContain('.less');
        expect($jsUrl)->toContain('.ts');
        expect($fontUrl)->toContain('.woff2');
    });
});
