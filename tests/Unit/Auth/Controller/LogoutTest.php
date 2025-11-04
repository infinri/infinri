<?php

declare(strict_types=1);

use Infinri\Auth\Controller\Adminhtml\Login\Logout;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Model\View\LayoutFactory;

beforeEach(function () {
    $this->request = $this->createMock(Request::class);
    $this->response = new Response(); // Use real Response object
    $this->layoutFactory = $this->createMock(LayoutFactory::class);
    $this->csrfGuard = $this->createMock(CsrfGuard::class);
    $this->rememberTokenService = $this->createMock(RememberTokenService::class);
    
    $this->controller = new Logout(
        $this->request,
        $this->response,
        $this->layoutFactory,
        $this->csrfGuard,
        $this->rememberTokenService
    );
    
    // Start session for tests
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
});

afterEach(function () {
    // Clean up session
    $_SESSION = [];
});

describe('Logout CSRF Protection', function () {
    
    it('rejects GET requests', function () {
        $this->request->method('isPost')->willReturn(false);
        
        $response = $this->controller->execute();
        
        // Should redirect to dashboard (not logout)
        expect($response->getStatusCode())->toBe(302)
            ->and($response->getHeaders()['Location'])->toContain('dashboard');
    });
    
    it('requires valid CSRF token', function () {
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'invalid_token'],
                ['_csrf_token_id', 'admin_logout', 'admin_logout']
            ]);
        
        $this->csrfGuard->method('validateToken')
            ->with('admin_logout', 'invalid_token')
            ->willReturn(false);
        
        $response = $this->controller->execute();
        
        // Should redirect to dashboard (not logout)
        expect($response->getStatusCode())->toBe(302)
            ->and($response->getHeaders()['Location'])->toContain('dashboard');
    });
    
    it('allows logout with valid POST and CSRF token', function () {
        $_SESSION['admin_username'] = 'testuser';
        $_SESSION['admin_user_id'] = 123;
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'valid_token'],
                ['_csrf_token_id', 'admin_logout', 'admin_logout']
            ]);
        
        $this->csrfGuard->method('validateToken')
            ->with('admin_logout', 'valid_token')
            ->willReturn(true);
        
        $this->rememberTokenService->method('getRememberCookie')->willReturn(null);
        
        $response = $this->controller->execute();
        
        // Should redirect to login page (successful logout)
        expect($response->getStatusCode())->toBe(302)
            ->and($response->getHeaders()['Location'])->toContain('login');
    });
    
    it('revokes remember-me token on logout', function () {
        $_SESSION['admin_username'] = 'testuser';
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'valid_token'],
                ['_csrf_token_id', 'admin_logout', 'admin_logout']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $this->rememberTokenService->method('getRememberCookie')
            ->willReturn('test_token_12345');
        
        $this->rememberTokenService->expects($this->once())
            ->method('revokeToken')
            ->with('test_token_12345');
        
        $this->rememberTokenService->expects($this->once())
            ->method('deleteRememberCookie');
        
        $this->controller->execute($this->request);
    });
    
    it('clears all session data on logout', function () {
        $_SESSION['admin_username'] = 'testuser';
        $_SESSION['admin_user_id'] = 123;
        $_SESSION['admin_email'] = 'test@example.com';
        $_SESSION['some_other_data'] = 'value';
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'valid_token'],
                ['_csrf_token_id', 'admin_logout', 'admin_logout']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        $this->rememberTokenService->method('getRememberCookie')->willReturn(null);
        
        $this->controller->execute($this->request);
        
        // Session should be empty after logout
        expect($_SESSION)->toBeEmpty();
    });
});

describe('Logout Session Security', function () {
    
    it('destroys session properly', function () {
        $_SESSION['admin_user_id'] = 123;
        
        $this->request->method('isPost')->willReturn(true);
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'valid_token'],
                ['_csrf_token_id', 'admin_logout', 'admin_logout']
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        $this->rememberTokenService->method('getRememberCookie')->willReturn(null);
        
        $oldSessionId = session_id();
        
        $this->controller->execute($this->request);
        
        // Verify session was destroyed
        expect($_SESSION)->toBeEmpty();
    });
});

describe('Logout Source Code Security', function () {
    
    it('enforces POST requirement in source code', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Logout.php';
        $source = file_get_contents($sourceFile);
        
        // Verify POST check exists via requirePost() method
        expect(str_contains($source, 'requirePost'))->toBeTrue()
            ->and(str_contains($source, 'Not a POST request'))->toBeTrue();
    });
    
    it('enforces CSRF validation in source code', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Logout.php';
        $source = file_get_contents($sourceFile);
        
        // Verify CSRF validation exists via validateCsrf() method
        expect(str_contains($source, 'validateCsrf'))->toBeTrue()
            ->and(str_contains($source, 'Invalid CSRF token'))->toBeTrue()
            ->and(str_contains($source, 'CsrfGuard'))->toBeTrue();
    });
});
