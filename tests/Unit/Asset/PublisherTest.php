<?php

declare(strict_types=1);

use Infinri\Core\Model\Asset\Publisher;
use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Api\ComponentRegistrarInterface;

describe('Asset Publisher', function () {
    
    beforeEach(function () {
        // Use a temporary directory for testing
        $this->testBasePath = sys_get_temp_dir() . '/infinri_test_' . uniqid();
        $this->staticPath = $this->testBasePath . '/pub/static';
        
        // Create test directory structure
        mkdir($this->testBasePath, 0755, true);
        mkdir($this->testBasePath . '/pub', 0755, true);
        
        // Create mock module with assets
        $this->mockModulePath = $this->testBasePath . '/app/Infinri/TestModule';
        mkdir($this->mockModulePath . '/view/frontend/web/css', 0755, true);
        mkdir($this->mockModulePath . '/view/frontend/web/js', 0755, true);
        
        // Create some test assets
        file_put_contents($this->mockModulePath . '/view/frontend/web/css/style.css', '/* test css */');
        file_put_contents($this->mockModulePath . '/view/frontend/web/js/app.js', '// test js');
        
        // Create mock ComponentRegistrar
        $this->registrar = $this->createMock(ComponentRegistrarInterface::class);
        $this->registrar->method('getPath')
            ->willReturn($this->mockModulePath);
        $this->registrar->method('getPaths')
            ->willReturn(['Infinri_TestModule' => $this->mockModulePath]);
        
        $this->publisher = new Publisher($this->testBasePath, $this->registrar, true);
    });
    
    afterEach(function () {
        // Clean up test directories
        if (is_dir($this->testBasePath)) {
            removeDirectory($this->testBasePath);
        }
    });
    
    it('can publish module assets with symlinks', function () {
        $result = $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect($result)->toBeTrue();
        expect(is_link($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
    });
    
    it('creates target directory if not exists', function () {
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(is_dir($this->staticPath))->toBeTrue();
    });
    
    it('can publish with file copying instead of symlinks', function () {
        $publisher = new Publisher($this->testBasePath, $this->registrar, false);
        
        $result = $publisher->publish('Infinri_TestModule', 'frontend');
        
        expect($result)->toBeTrue();
        expect(is_dir($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
        expect(is_link($this->staticPath . '/Infinri/TestModule'))->toBeFalse();
        expect(file_exists($this->staticPath . '/Infinri/TestModule/css/style.css'))->toBeTrue();
        expect(file_exists($this->staticPath . '/Infinri/TestModule/js/app.js'))->toBeTrue();
    });
    
    it('copies files recursively when not using symlinks', function () {
        // Create nested directory structure
        mkdir($this->mockModulePath . '/view/frontend/web/css/components', 0755, true);
        file_put_contents($this->mockModulePath . '/view/frontend/web/css/components/button.css', '/* button */');
        
        $publisher = new Publisher($this->testBasePath, $this->registrar, false);
        $publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(file_exists($this->staticPath . '/Infinri/TestModule/css/components/button.css'))->toBeTrue();
    });
    
    it('returns true when module has no assets to publish', function () {
        // Create module without web directory
        $emptyModulePath = $this->testBasePath . '/app/Infinri/EmptyModule';
        mkdir($emptyModulePath, 0755, true);
        
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn($emptyModulePath);
        
        $publisher = new Publisher($this->testBasePath, $registrar, true);
        $result = $publisher->publish('Infinri_EmptyModule', 'frontend');
        
        expect($result)->toBeTrue();
    });
    
    it('throws exception for non-existent module', function () {
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->method('getPath')->willReturn(null);
        
        $publisher = new Publisher($this->testBasePath, $registrar, true);
        
        expect(fn() => $publisher->publish('NonExistent_Module'))
            ->toThrow(RuntimeException::class, 'Module NonExistent_Module not found');
    });
    
    it('can publish all modules', function () {
        // Create a fresh publisher with proper mock
        $registrar = $this->createMock(ComponentRegistrarInterface::class);
        $registrar->method('getPaths')
            ->willReturn(['Infinri_TestModule' => $this->mockModulePath]);
        $registrar->method('getPath')
            ->willReturn($this->mockModulePath);
        
        $publisher = new Publisher($this->testBasePath, $registrar, true);
        $results = $publisher->publishAll('frontend');
        
        expect($results)->toBeArray();
        expect($results)->toHaveKey('Infinri_TestModule');
        expect($results['Infinri_TestModule'])->toBeTrue();
    });
    
    it('can clean published assets for a module', function () {
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(file_exists($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
        
        $result = $this->publisher->clean('Infinri_TestModule');
        
        expect($result)->toBeTrue();
        expect(file_exists($this->staticPath . '/Infinri/TestModule'))->toBeFalse();
    });
    
    it('can clean all published assets', function () {
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(is_dir($this->staticPath))->toBeTrue();
        expect(file_exists($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
        
        $result = $this->publisher->cleanAll();
        
        expect($result)->toBeTrue();
        // Static directory and all its contents should be removed
        expect(file_exists($this->staticPath . '/Infinri'))->toBeFalse();
    });
    
    it('clean returns true when directory does not exist', function () {
        $result = $this->publisher->clean('Infinri_NonExistent');
        
        expect($result)->toBeTrue();
    });
    
    it('cleanAll returns true when static directory does not exist', function () {
        $result = $this->publisher->cleanAll();
        
        expect($result)->toBeTrue();
    });
    
    it('replaces existing symlink when publishing again', function () {
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        $firstLink = readlink($this->staticPath . '/Infinri/TestModule');
        
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        $secondLink = readlink($this->staticPath . '/Infinri/TestModule');
        
        expect($firstLink)->toBe($secondLink);
    });
    
    it('can switch between symlink and copy modes', function () {
        $this->publisher->setUseSymlinks(false);
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(is_link($this->staticPath . '/Infinri/TestModule'))->toBeFalse();
        expect(is_dir($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
    });
    
    it('converts module name underscores to directory separators', function () {
        $this->publisher->publish('Infinri_TestModule', 'frontend');
        
        expect(file_exists($this->staticPath . '/Infinri/TestModule'))->toBeTrue();
    });
    
    it('can publish for different areas', function () {
        // Create adminhtml assets
        mkdir($this->mockModulePath . '/view/adminhtml/web/css', 0755, true);
        file_put_contents($this->mockModulePath . '/view/adminhtml/web/css/admin.css', '/* admin */');
        
        $this->publisher->publish('Infinri_TestModule', 'adminhtml');
        
        // Check symlink points to adminhtml directory
        $link = readlink($this->staticPath . '/Infinri/TestModule');
        expect(str_contains($link, '/view/adminhtml/web'))->toBeTrue();
    });
});

// Helper function to remove directory recursively
function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }
    
    $items = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path) && !is_link($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}
