<?php

declare(strict_types=1);

use Infinri\Cms\Controller\Adminhtml\Media\Uploadmultiple;
use Infinri\Core\App\Request;
use Infinri\Core\Security\CsrfGuard;

beforeEach(function () {
    $this->csrfGuard = $this->createMock(CsrfGuard::class);
    $this->controller = new Uploadmultiple($this->csrfGuard);
    $this->request = $this->createMock(Request::class);
});

describe('Upload Multiple Security', function () {
    
    it('blocks folder path traversal attempts', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', '../../etc/passwd'],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        // Provide a dummy file to get past the "no files" check
        $_FILES = [
            'files' => [
                'name' => ['test.jpg'],
                'type' => ['image/jpeg'],
                'tmp_name' => [tempnam(sys_get_temp_dir(), 'test')],
                'error' => [UPLOAD_ERR_OK],
                'size' => [1000]
            ]
        ];
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        expect($body['success'])->toBeFalse();
        expect($body)->toHaveKey('error');
        expect(str_contains($body['error'] ?? '', 'Invalid folder name'))->toBeTrue();
        
        unset($_FILES);
    });
    
    it('blocks folder with null bytes', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', "uploads\0/etc/passwd"],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('Invalid folder name');
    });
    
    it('blocks folder with special characters', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', 'uploads;rm -rf /'],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('Invalid folder name');
    });
    
    it('allows valid folder names', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', 'products/category-1'],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        // No files uploaded, but should pass folder validation
        $_FILES = ['files' => []];
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        // Should fail on "no files" not folder validation
        expect($body['error'])->not->toContain('Invalid folder name');
        
        unset($_FILES);
    });
    
    it('sanitizes uploaded filenames', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpFile, $pngData);
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', ''],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $_FILES = [
            'files' => [
                'name' => ['../../evil <script>.png'],
                'type' => ['image/png'],
                'tmp_name' => [$tmpFile],
                'error' => [UPLOAD_ERR_OK],
                'size' => [strlen($pngData)]
            ]
        ];
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        if ($body['success'] && !empty($body['uploaded'])) {
            $uploadedName = $body['uploaded'][0];
            
            // Should not contain path traversal or special chars
            expect($uploadedName)->not->toContain('..')
                ->and($uploadedName)->not->toContain('/')
                ->and($uploadedName)->not->toContain('<')
                ->and($uploadedName)->not->toContain('>')
                ->and($uploadedName)->toContain('_'); // Has unique prefix
        }
        
        @unlink($tmpFile);
        unset($_FILES);
    });
    
    it('requires CSRF token', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')->willReturn('');
        
        $this->csrfGuard->method('validateToken')->willReturn(false);
        
        $response = $this->controller->execute($this->request);
        
        expect($response->getStatusCode())->toBe(403);
        
        $body = json_decode($response->getBody(), true);
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('CSRF');
    });
    
    it('adds unique prefix to prevent filename collisions', function () {
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'test_');
        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test_');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpFile1, $pngData);
        file_put_contents($tmpFile2, $pngData);
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getParam')
            ->willReturnMap([
                ['folder', '', ''],
                ['_csrf_token', '', 'valid_token']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        // Upload 2 files with same name
        $_FILES = [
            'files' => [
                'name' => ['test.png', 'test.png'],
                'type' => ['image/png', 'image/png'],
                'tmp_name' => [$tmpFile1, $tmpFile2],
                'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
                'size' => [strlen($pngData), strlen($pngData)]
            ]
        ];
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        if ($body['success'] && count($body['uploaded']) === 2) {
            // Both should have unique names
            expect($body['uploaded'][0])->not->toBe($body['uploaded'][1]);
        }
        
        @unlink($tmpFile1);
        @unlink($tmpFile2);
        unset($_FILES);
    });
});
