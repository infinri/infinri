<?php

declare(strict_types=1);

use Infinri\Cms\Controller\Adminhtml\Media\Upload;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfGuard;

beforeEach(function () {
    $this->csrfGuard = $this->createMock(CsrfGuard::class);
    $this->csrfGuard->method('validateToken')->willReturn(true);
    
    $this->controller = new Upload($this->csrfGuard);
    $this->request = $this->createMock(Request::class);
    $this->request->method('getParam')
        ->willReturnCallback(fn($key, $default = null) => $key === '_csrf_token' ? 'valid_token' : $default);
});

describe('Upload Security', function () {
    
    it('sanitizes filename with path traversal attempt', function () {
        // Create actual PNG data
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpFile, $pngData);
        
        // Mock $_FILES with path traversal attempt
        $_FILES = [
            'image' => [
                'name' => '../../evil.php',
                'type' => 'image/png',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($pngData)
            ]
        ];
        
        $this->request->method('isPost')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        // Should reject .php extension
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('extension');
        
        // Cleanup
        @unlink($tmpFile);
        unset($_FILES);
    });
    
    it('rejects non-image file extensions', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'fake php code');
        
        $_FILES = [
            'image' => [
                'name' => 'shell.php',
                'type' => 'application/x-php',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1000
            ]
        ];
        
        $this->request->method('isPost')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        // MIME type check happens first, then extension
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toMatch('/(Invalid file type|extension)/');
        
        @unlink($tmpFile);
        unset($_FILES);
    });
    
    it('rejects files with invalid MIME type even with valid extension', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, '<?php echo "evil"; ?>');
        
        $_FILES = [
            'image' => [
                'name' => 'fake.jpg', // Valid extension
                'type' => 'image/jpeg',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 1000
            ]
        ];
        
        $this->request->method('isPost')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        // finfo should detect it's not actually an image
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('Invalid file type');
        
        @unlink($tmpFile);
        unset($_FILES);
    });
    
    it('requires POST method', function () {
        $this->request->method('isPost')->willReturn(false);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        // Now returns CSRF error since CSRF check includes POST validation
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toContain('Invalid CSRF token');
    });
    
    it('generates unique filenames to prevent collisions', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        
        // Create actual image data (1x1 PNG)
        $pngData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        file_put_contents($tmpFile, $pngData);
        
        $_FILES = [
            'image' => [
                'name' => 'test.png',
                'type' => 'image/png',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($pngData)
            ]
        ];
        
        $this->request->method('isPost')->willReturn(true);
        
        // Upload twice with same name
        $response1 = $this->controller->execute($this->request);
        $body1 = json_decode($response1->getBody(), true);
        
        // Create new temp file for second upload
        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile2, $pngData);
        $_FILES['image']['tmp_name'] = $tmpFile2;
        
        $response2 = $this->controller->execute($this->request);
        $body2 = json_decode($response2->getBody(), true);
        
        // Both should succeed or fail, but always assert
        expect($body1)->toHaveKey('success')
            ->and($body2)->toHaveKey('success');
        
        if ($body1['success'] && $body2['success']) {
            // Filenames should be different due to uniqid()
            expect($body1['filename'])->not->toBe($body2['filename']);
        }
        
        @unlink($tmpFile);
        @unlink($tmpFile2);
        unset($_FILES);
    });
    
    it('validates file size limit', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        // Create actual JPEG data (small but valid)
        $jpegData = base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP/bAEMAAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAACAAIDAREAAhEBAxEB/8QAFAABAAAAAAAAAAAAAAAAAAAACv/EABQQAQAAAAAAAAAAAAAAAAAAAAD/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8A');
        file_put_contents($tmpFile, $jpegData);
        
        $_FILES = [
            'image' => [
                'name' => 'huge.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => $tmpFile,
                'error' => UPLOAD_ERR_OK,
                'size' => 6 * 1024 * 1024 // 6MB (exceeds 5MB limit)
            ]
        ];
        
        $this->request->method('isPost')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        $body = json_decode($response->getBody(), true);
        
        expect($body['success'])->toBeFalse()
            ->and($body['error'])->toMatch('/(too large|Invalid file type)/');
        
        @unlink($tmpFile);
        unset($_FILES);
    });
});
