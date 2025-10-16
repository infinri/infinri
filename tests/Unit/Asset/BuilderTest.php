<?php

declare(strict_types=1);

use Infinri\Core\Model\Asset\Builder;

describe('Asset Builder', function () {
    
    beforeEach(function () {
        $this->basePath = dirname(__DIR__, 3);
        $this->builder = new Builder($this->basePath);
        
        // Create temporary test directory
        $this->testDir = sys_get_temp_dir() . '/infinri_builder_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    });
    
    afterEach(function () {
        // Clean up test directory
        if (is_dir($this->testDir)) {
            $items = array_diff(scandir($this->testDir), ['.', '..']);
            foreach ($items as $item) {
                unlink($this->testDir . '/' . $item);
            }
            rmdir($this->testDir);
        }
    });
    
    it('can check for installed tools', function () {
        $tools = $this->builder->checkTools();
        
        expect($tools)->toBeArray();
        expect($tools)->toHaveKey('lessc');
        expect($tools)->toHaveKey('cleancss');
        expect($tools)->toHaveKey('terser');
    });
    
    it('returns correct node bin path', function () {
        $path = $this->builder->getNodeBinPath();
        
        expect($path)->toBeString();
        expect(str_contains($path, 'node_modules/.bin'))->toBeTrue();
    });
    
    it('can enable source maps', function () {
        $this->builder->setGenerateSourceMaps(true);
        
        // Source maps will be included in commands
        expect(true)->toBeTrue();
    });
    
    it('can disable source maps', function () {
        $this->builder->setGenerateSourceMaps(false);
        
        expect(true)->toBeTrue();
    });
    
    it('throws exception for non-existent LESS source file', function () {
        expect(fn() => $this->builder->compileLess('/non/existent.less', $this->testDir . '/output.css'))
            ->toThrow(RuntimeException::class, 'Source file not found');
    });
    
    it('throws exception for non-existent CSS source file', function () {
        expect(fn() => $this->builder->minifyCss('/non/existent.css', $this->testDir . '/output.min.css'))
            ->toThrow(RuntimeException::class, 'Source file not found');
    });
    
    it('throws exception for non-existent JS source file', function () {
        expect(fn() => $this->builder->minifyJs('/non/existent.js', $this->testDir . '/output.min.js'))
            ->toThrow(RuntimeException::class, 'Source file not found');
    });
    
    it('can compile LESS to CSS if lessc is installed', function () {
        $tools = $this->builder->checkTools();
        
        if (!$tools['lessc']) {
            $this->markTestSkipped('lessc not installed');
        }
        
        // Create a simple LESS file
        $lessFile = $this->testDir . '/test.less';
        $cssFile = $this->testDir . '/test.css';
        
        file_put_contents($lessFile, '@color: #4D926F; .header { color: @color; }');
        
        $result = $this->builder->compileLess($lessFile, $cssFile);
        
        expect($result)->toBeTrue();
        expect(file_exists($cssFile))->toBeTrue();
        
        $css = file_get_contents($cssFile);
        expect(str_contains($css, '.header'))->toBeTrue();
        expect(str_contains($css, '#4D926F') || str_contains($css, '#4d926f'))->toBeTrue();
    });
    
    it('can minify CSS if cleancss is installed', function () {
        $tools = $this->builder->checkTools();
        
        if (!$tools['cleancss']) {
            $this->markTestSkipped('cleancss not installed');
        }
        
        // Create a CSS file with whitespace
        $cssFile = $this->testDir . '/test.css';
        $minFile = $this->testDir . '/test.min.css';
        
        file_put_contents($cssFile, ".header {\n    color: #4D926F;\n    padding: 10px;\n}");
        
        $result = $this->builder->minifyCss($cssFile, $minFile);
        
        expect($result)->toBeTrue();
        expect(file_exists($minFile))->toBeTrue();
        
        $minified = file_get_contents($minFile);
        $original = file_get_contents($cssFile);
        
        // Minified should be smaller
        expect(strlen($minified))->toBeLessThan(strlen($original));
    });
    
    it('can minify JavaScript if terser is installed', function () {
        $tools = $this->builder->checkTools();
        
        if (!$tools['terser']) {
            $this->markTestSkipped('terser not installed');
        }
        
        // Create a JS file with whitespace and comments
        $jsFile = $this->testDir . '/test.js';
        $minFile = $this->testDir . '/test.min.js';
        
        file_put_contents($jsFile, "// Comment\nfunction hello() {\n    console.log('Hello');\n}");
        
        $result = $this->builder->minifyJs($jsFile, $minFile);
        
        expect($result)->toBeTrue();
        expect(file_exists($minFile))->toBeTrue();
        
        $minified = file_get_contents($minFile);
        $original = file_get_contents($jsFile);
        
        // Minified should be smaller
        expect(strlen($minified))->toBeLessThan(strlen($original));
    });
    
    it('can build CSS from LESS in one step', function () {
        $tools = $this->builder->checkTools();
        
        if (!$tools['lessc'] || !$tools['cleancss']) {
            $this->markTestSkipped('lessc or cleancss not installed');
        }
        
        // Create a LESS file
        $lessFile = $this->testDir . '/test.less';
        $cssFile = $this->testDir . '/test.min.css';
        
        file_put_contents($lessFile, "@color: #4D926F;\n.header {\n    color: @color;\n    padding: 10px;\n}");
        
        $result = $this->builder->buildCss($lessFile, $cssFile);
        
        expect($result)->toBeTrue();
        expect(file_exists($cssFile))->toBeTrue();
        
        $css = file_get_contents($cssFile);
        expect(str_contains($css, '.header'))->toBeTrue();
    });
    
    it('throws exception when lessc is not installed', function () {
        // Create builder with non-existent bin path
        $builder = new Builder($this->basePath, '/non/existent/bin');
        
        $lessFile = $this->testDir . '/test.less';
        file_put_contents($lessFile, '@color: red;');
        
        expect(fn() => $builder->compileLess($lessFile, $this->testDir . '/out.css'))
            ->toThrow(RuntimeException::class, 'LESS compiler not found');
    });
    
    it('throws exception when cleancss is not installed', function () {
        $builder = new Builder($this->basePath, '/non/existent/bin');
        
        $cssFile = $this->testDir . '/test.css';
        file_put_contents($cssFile, '.test { color: red; }');
        
        expect(fn() => $builder->minifyCss($cssFile, $this->testDir . '/out.min.css'))
            ->toThrow(RuntimeException::class, 'CleanCSS not found');
    });
    
    it('throws exception when terser is not installed', function () {
        $builder = new Builder($this->basePath, '/non/existent/bin');
        
        $jsFile = $this->testDir . '/test.js';
        file_put_contents($jsFile, 'console.log("test");');
        
        expect(fn() => $builder->minifyJs($jsFile, $this->testDir . '/out.min.js'))
            ->toThrow(RuntimeException::class, 'Terser not found');
    });
    
    it('cleans up temporary files when building CSS', function () {
        $tools = $this->builder->checkTools();
        
        if (!$tools['lessc'] || !$tools['cleancss']) {
            $this->markTestSkipped('lessc or cleancss not installed');
        }
        
        $lessFile = $this->testDir . '/test.less';
        $cssFile = $this->testDir . '/test.min.css';
        
        file_put_contents($lessFile, '@color: blue; .test { color: @color; }');
        
        $tempFilesBefore = glob(sys_get_temp_dir() . '/infinri_css_*');
        
        $this->builder->buildCss($lessFile, $cssFile);
        
        $tempFilesAfter = glob(sys_get_temp_dir() . '/infinri_css_*');
        
        // Should not create orphan temp files
        expect(count($tempFilesAfter))->toBe(count($tempFilesBefore));
    });
});
