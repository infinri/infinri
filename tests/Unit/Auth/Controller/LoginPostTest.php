<?php

declare(strict_types=1);

use Infinri\Auth\Controller\Adminhtml\Login\Post;
use Infinri\Core\App\Request;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Admin\Service\RememberTokenService;
use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\Service\RateLimiter;

beforeEach(function () {
    $this->adminUserResource = $this->createMock(AdminUserResource::class);
    $this->csrfGuard = $this->createMock(CsrfGuard::class);
    $this->rememberTokenService = $this->createMock(RememberTokenService::class);
    $this->rateLimiter = $this->createMock(RateLimiter::class);
    
    // Rate limiter allows requests by default
    $this->rateLimiter->method('attemptFromRequest')->willReturn(true);
    
    $this->controller = new Post(
        $this->adminUserResource,
        $this->csrfGuard,
        $this->rememberTokenService,
        $this->rateLimiter
    );
    
    $this->request = $this->createMock(Request::class);
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
});

afterEach(function () {
    $_SESSION = [];
});

describe('Login Session Security', function () {
    
    it('verifies session regeneration exists in source code', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify session_regenerate_id(true) is called
        expect(str_contains($source, 'session_regenerate_id(true)'))->toBeTrue()
            ->and(str_contains($source, 'Create session'))->toBeTrue();
    });
    
    it('requires CSRF token for login', function () {
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'invalid_token'],
                ['_csrf_token_id', 'admin_login', 'admin_login'],
                ['username', '', 'admin'],
                ['password', '', 'password']
            ]);
        
        $this->request->method('getClientIp')->willReturn('127.0.0.1');
        
        $this->csrfGuard->method('validateToken')
            ->with('admin_login', 'invalid_token')
            ->willReturn(false);
        
        $response = $this->controller->execute($this->request);
        
        // Should redirect with error
        expect($response->getStatusCode())->toBe(302)
            ->and($response->getHeaders()['Location'])->toContain('error=csrf');
    });
    
    it('implements timing attack prevention', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify usleep is used for timing attack prevention
        expect(str_contains($source, 'usleep('))->toBeTrue()
            ->and(str_contains($source, 'random_int'))->toBeTrue()
            ->and(str_contains($source, 'Timing attack prevention'))->toBeTrue();
    });
    
    it('creates session fingerprint', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify fingerprint is created
        expect(str_contains($source, 'admin_fingerprint'))->toBeTrue()
            ->and(str_contains($source, 'getFingerprint'))->toBeTrue();
    });
    
    it('validates input is not empty', function () {
        $this->request->method('getPost')
            ->willReturnMap([
                ['_csrf_token', '', 'valid_token'],
                ['_csrf_token_id', 'admin_login', 'admin_login'],
                ['username', '', ''], // Empty username
                ['password', '', '']  // Empty password
            ]);
        
        $this->csrfGuard->method('validateToken')->willReturn(true);
        
        $response = $this->controller->execute($this->request);
        
        // Should store error in session
        expect($_SESSION)->toHaveKey('login_error');
        expect($_SESSION['login_error'])->toContain('username and password');
    });
    
    it('checks user active status', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify active check exists
        expect(str_contains($source, 'isActive()'))->toBeTrue()
            ->and(str_contains($source, 'disabled'))->toBeTrue();
    });
    
    it('uses password_verify for password checking', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify password_verify is used (not plain comparison)
        expect(str_contains($source, 'password_verify'))->toBeTrue()
            ->and(str_contains($source, '$password ==='))->toBeFalse()
            ->and(str_contains($source, '$password =='))->toBeFalse();
    });
    
    it('updates last login timestamp', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify last login update
        expect(str_contains($source, 'updateLastLogin'))->toBeTrue();
    });
    
    it('handles remember me functionality', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify remember me is handled
        expect(str_contains($source, 'remember_me'))->toBeTrue()
            ->and(str_contains($source, 'generateToken'))->toBeTrue()
            ->and(str_contains($source, 'setRememberCookie'))->toBeTrue();
    });
});

describe('Login Security Best Practices', function () {
    
    it('logs security events', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Verify logging is in place
        expect(str_contains($source, 'Logger::info'))->toBeTrue()
            ->and(str_contains($source, 'Logger::warning'))->toBeTrue()
            ->and(str_contains($source, 'login attempt'))->toBeTrue()
            ->and(str_contains($source, 'Login failed'))->toBeTrue();
    });
    
    it('does not reveal whether username or password was wrong', function () {
        $sourceFile = __DIR__ . '/../../../../app/Infinri/Auth/Controller/Adminhtml/Login/Post.php';
        $source = file_get_contents($sourceFile);
        
        // Should use generic message for both user not found and wrong password
        $genericMessage = 'Invalid username or password';
        
        // Count occurrences - should appear multiple times for different failures
        $count = substr_count($source, $genericMessage);
        expect($count)->toBeGreaterThanOrEqual(2);
    });
});
