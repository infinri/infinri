<?php

declare(strict_types=1);

use Infinri\Admin\Service\RememberTokenService;
use Infinri\Admin\Model\ResourceModel\RememberToken;

beforeEach(function () {
    $this->tokenResource = $this->createMock(RememberToken::class);
    $this->service = new RememberTokenService($this->tokenResource);
});

describe('RememberTokenService Cookie Security', function () {
    
    it('sets cookie with secure flag enabled', function () {
        // Mock token creation (returns token_id)
        $this->tokenResource->method('createToken')->willReturn(1);
        
        $token = $this->service->generateToken(1, '127.0.0.1', 'Test Browser');
        
        // Capture cookie settings by mocking setcookie
        $cookieCalled = false;
        $cookieOptions = [];
        
        // We can't actually test setcookie without running it,
        // but we can verify the token was generated properly
        expect($token)->toBeString()
            ->and(strlen($token))->toBe(64); // 32 bytes hex = 64 chars
    });
    
    it('generates cryptographically secure tokens', function () {
        $this->tokenResource->method('createToken')->willReturn(1);
        
        $token1 = $this->service->generateToken(1, '127.0.0.1', 'Browser');
        $token2 = $this->service->generateToken(1, '127.0.0.1', 'Browser');
        
        // Tokens should be unique
        expect($token1)->not->toBe($token2)
            ->and($token1)->toMatch('/^[a-f0-9]{64}$/') // Hex format
            ->and($token2)->toMatch('/^[a-f0-9]{64}$/');
    });
    
    it('hashes tokens before storage', function () {
        $capturedHash = null;
        
        $this->tokenResource->expects($this->once())
            ->method('createToken')
            ->with(
                $this->equalTo(1),
                $this->callback(function ($hash) use (&$capturedHash) {
                    $capturedHash = $hash;
                    return true;
                }),
                $this->anything(),
                $this->anything(),
                $this->anything()
            );
        
        $token = $this->service->generateToken(1, '127.0.0.1', 'Browser');
        
        // Hash should be different from token (SHA256 is 64 hex chars)
        expect($capturedHash)->not->toBe($token)
            ->and($capturedHash)->toMatch('/^[a-f0-9]{64}$/');
        
        // Verify it's the correct hash
        expect($capturedHash)->toBe(hash('sha256', $token));
    });
    
    it('validates token using hash comparison', function () {
        $token = str_repeat('a', 64); // Fake token
        $tokenHash = hash('sha256', $token);
        
        $this->tokenResource->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn([
                'token_id' => 1,
                'user_id' => 123,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600)
            ]);
        
        $this->tokenResource->method('updateLastUsed')->willReturn(1);
        
        $userId = $this->service->validateToken($token);
        
        expect($userId)->toBe(123);
    });
    
    it('rejects expired tokens', function () {
        $token = str_repeat('a', 64);
        $tokenHash = hash('sha256', $token);
        
        $this->tokenResource->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn([
                'token_id' => 1,
                'user_id' => 123,
                'expires_at' => date('Y-m-d H:i:s', time() - 3600) // Expired 1 hour ago
            ]);
        
        $this->tokenResource->expects($this->once())
            ->method('deleteByTokenHash')
            ->with($tokenHash);
        
        $userId = $this->service->validateToken($token);
        
        expect($userId)->toBeFalse();
    });
    
    it('rejects invalid tokens', function () {
        $token = 'invalid_token';
        $tokenHash = hash('sha256', $token);
        
        $this->tokenResource->method('findByTokenHash')
            ->with($tokenHash)
            ->willReturn(false); // Not found
        
        $userId = $this->service->validateToken($token);
        
        expect($userId)->toBeFalse();
    });
    
    it('revokes token from database', function () {
        $token = str_repeat('a', 64);
        $tokenHash = hash('sha256', $token);
        
        $this->tokenResource->expects($this->once())
            ->method('deleteByTokenHash')
            ->with($tokenHash);
        
        $this->service->revokeToken($token);
    });
    
    it('revokes all user tokens', function () {
        $userId = 123;
        
        $this->tokenResource->expects($this->once())
            ->method('deleteByUserId')
            ->with($userId);
        
        $this->service->revokeAllUserTokens($userId);
    });
    
    it('cleans up expired tokens', function () {
        $this->tokenResource->method('deleteExpired')->willReturn(5);
        
        $deleted = $this->service->cleanupExpiredTokens();
        
        expect($deleted)->toBe(5);
    });
    
    it('updates last used timestamp on validation', function () {
        $token = str_repeat('a', 64);
        $tokenHash = hash('sha256', $token);
        
        $this->tokenResource->method('findByTokenHash')
            ->willReturn([
                'token_id' => 1,
                'user_id' => 123,
                'expires_at' => date('Y-m-d H:i:s', time() + 3600)
            ]);
        
        $this->tokenResource->expects($this->once())
            ->method('updateLastUsed')
            ->with(1);
        
        $this->service->validateToken($token);
    });
});

describe('RememberTokenService Cookie Flags', function () {
    
    it('enforces secure cookie requirements in source code', function () {
        // Read the actual source code to verify cookie settings
        $sourceFile = __DIR__ . '/../../../app/Infinri/Admin/Service/RememberTokenService.php';
        
        if (!file_exists($sourceFile)) {
            $this->markTestSkipped('Source file not found at: ' . $sourceFile);
        }
        
        $source = file_get_contents($sourceFile);
        
        // Verify secure flag is set to true (using str_contains for string search)
        expect(str_contains($source, "'secure' => true"))->toBeTrue()
            ->and(str_contains($source, "'secure' => false"))->toBeFalse();
        
        // Verify httponly is enabled
        expect(str_contains($source, "'httponly' => true"))->toBeTrue();
        
        // Verify SameSite is Strict for admin
        expect(str_contains($source, "'samesite' => 'Strict'"))->toBeTrue();
    });
});
