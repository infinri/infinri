<?php

declare(strict_types=1);

use Infinri\Core\Service\RateLimiter;
use Infinri\Core\App\Request;

beforeEach(function () {
    $this->limiter = new RateLimiter();
    $this->limiter->clearAll(); // Clean state for each test
});

afterEach(function () {
    $this->limiter->clearAll();
});

describe('Rate Limiter Service (Phase 2.5)', function () {
    
    describe('Basic Rate Limiting', function () {
        
        it('allows requests under the limit', function () {
            expect($this->limiter->attempt('login', '127.0.0.1', 5, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '127.0.0.1', 5, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '127.0.0.1', 5, 60))->toBeTrue();
        });
        
        it('blocks requests over the limit', function () {
            // Allow 3 requests per minute
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            
            // 4th request should be blocked
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeFalse();
        });
        
        it('uses default limits when not specified', function () {
            // Login default: 5 requests per 5 minutes
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeTrue();
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeTrue();
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeTrue();
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeTrue();
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeTrue();
            
            // 6th should be blocked
            expect($this->limiter->attempt('login', '192.168.1.1'))->toBeFalse();
        });
    });
    
    describe('Per-Identifier Isolation', function () {
        
        it('isolates rate limits by identifier', function () {
            // IP 1 exhausts limit
            expect($this->limiter->attempt('test', '10.0.0.1', 2, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '10.0.0.1', 2, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '10.0.0.1', 2, 60))->toBeFalse();
            
            // IP 2 still has attempts available
            expect($this->limiter->attempt('test', '10.0.0.2', 2, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '10.0.0.2', 2, 60))->toBeTrue();
        });
        
        it('isolates rate limits by action', function () {
            // Exhaust 'login' limit
            expect($this->limiter->attempt('login', '127.0.0.1', 2, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '127.0.0.1', 2, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '127.0.0.1', 2, 60))->toBeFalse();
            
            // 'api' action still has attempts
            expect($this->limiter->attempt('api', '127.0.0.1', 2, 60))->toBeTrue();
        });
    });
    
    describe('Check Without Recording', function () {
        
        it('checks limit without incrementing counter', function () {
            // Check doesn't record
            expect($this->limiter->check('test', '127.0.0.1', 3, 60))->toBeTrue();
            expect($this->limiter->check('test', '127.0.0.1', 3, 60))->toBeTrue();
            
            // Actual attempts
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '127.0.0.1', 3, 60))->toBeTrue();
            
            // Should be at limit now
            expect($this->limiter->check('test', '127.0.0.1', 3, 60))->toBeFalse();
        });
    });
    
    describe('Remaining Attempts', function () {
        
        it('returns correct remaining count', function () {
            expect($this->limiter->remaining('test', '127.0.0.1', 5, 60))->toBe(5);
            
            $this->limiter->attempt('test', '127.0.0.1', 5, 60);
            expect($this->limiter->remaining('test', '127.0.0.1', 5, 60))->toBe(4);
            
            $this->limiter->attempt('test', '127.0.0.1', 5, 60);
            expect($this->limiter->remaining('test', '127.0.0.1', 5, 60))->toBe(3);
        });
        
        it('returns zero when limit exceeded', function () {
            $this->limiter->attempt('test', '127.0.0.1', 2, 60);
            $this->limiter->attempt('test', '127.0.0.1', 2, 60);
            
            expect($this->limiter->remaining('test', '127.0.0.1', 2, 60))->toBe(0);
        });
    });
    
    describe('Retry After', function () {
        
        it('returns seconds until reset', function () {
            $this->limiter->attempt('test', '127.0.0.1', 1, 60);
            
            $retryAfter = $this->limiter->retryAfter('test', '127.0.0.1', 60);
            
            expect($retryAfter)->toBeGreaterThan(0);
            expect($retryAfter)->toBeLessThanOrEqual(60);
        });
        
        it('returns zero when no limit reached', function () {
            $retryAfter = $this->limiter->retryAfter('test', '127.0.0.1', 60);
            expect($retryAfter)->toBe(0);
        });
    });
    
    describe('Clear Rate Limit', function () {
        
        it('clears specific identifier', function () {
            $this->limiter->attempt('test', '127.0.0.1', 1, 60);
            expect($this->limiter->attempt('test', '127.0.0.1', 1, 60))->toBeFalse();
            
            $this->limiter->clear('test', '127.0.0.1');
            
            expect($this->limiter->attempt('test', '127.0.0.1', 1, 60))->toBeTrue();
        });
        
        it('clears all limits', function () {
            $this->limiter->attempt('test', '127.0.0.1', 1, 60);
            $this->limiter->attempt('test', '10.0.0.1', 1, 60);
            
            $this->limiter->clearAll();
            
            expect($this->limiter->attempt('test', '127.0.0.1', 1, 60))->toBeTrue();
            expect($this->limiter->attempt('test', '10.0.0.1', 1, 60))->toBeTrue();
        });
    });
    
    describe('Request Integration', function () {
        
        it('limits based on request IP', function () {
            $request = $this->createMock(Request::class);
            $request->method('getClientIp')->willReturn('192.168.1.100');
            
            expect($this->limiter->attemptFromRequest($request, 'test', 2, 60))->toBeTrue();
            expect($this->limiter->attemptFromRequest($request, 'test', 2, 60))->toBeTrue();
            expect($this->limiter->attemptFromRequest($request, 'test', 2, 60))->toBeFalse();
        });
        
        it('handles missing IP gracefully', function () {
            $request = $this->createMock(Request::class);
            $request->method('getClientIp')->willReturn(null);
            
            // Should use 'unknown' as identifier
            expect($this->limiter->attemptFromRequest($request, 'test', 1, 60))->toBeTrue();
            expect($this->limiter->attemptFromRequest($request, 'test', 1, 60))->toBeFalse();
        });
    });
    
    describe('Brute Force Prevention', function () {
        
        it('prevents login brute force', function () {
            // Simulate 5 failed login attempts
            for ($i = 0; $i < 5; $i++) {
                expect($this->limiter->attempt('login', '1.2.3.4'))->toBeTrue();
            }
            
            // 6th attempt should be blocked
            expect($this->limiter->attempt('login', '1.2.3.4'))->toBeFalse();
        });
        
        it('allows distributed attacks separately', function () {
            // Different IPs each get their own limit
            expect($this->limiter->attempt('login', '1.1.1.1', 1, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '2.2.2.2', 1, 60))->toBeTrue();
            expect($this->limiter->attempt('login', '3.3.3.3', 1, 60))->toBeTrue();
            
            // But each IP is individually limited
            expect($this->limiter->attempt('login', '1.1.1.1', 1, 60))->toBeFalse();
            expect($this->limiter->attempt('login', '2.2.2.2', 1, 60))->toBeFalse();
        });
    });
    
    describe('API Rate Limiting', function () {
        
        it('limits API requests per minute', function () {
            // Default API limit: 60 requests per minute
            for ($i = 0; $i < 60; $i++) {
                expect($this->limiter->attempt('api', 'user123'))->toBeTrue();
            }
            
            // 61st request blocked
            expect($this->limiter->attempt('api', 'user123'))->toBeFalse();
        });
    });
});
