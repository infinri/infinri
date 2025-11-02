<?php

declare(strict_types=1);

use Infinri\Core\App\Request;

describe('Request Type-Safe Getters (Phase 2.1)', function () {
    
    beforeEach(function () {
        $this->request = new Request(
            query: ['q' => 'search', 'page' => '2', 'active' => '1', 'tags' => ['php', 'security']],
            post: ['title' => '  Test Title  ', 'count' => 'invalid', 'email' => 'user@example.com', 'price' => '19.99']
        );
    });
    
    describe('getString()', function () {
        
        it('returns string and trims whitespace', function () {
            expect($this->request->getString('title'))->toBe('Test Title');
        });
        
        it('returns default for missing parameter', function () {
            expect($this->request->getString('missing', 'default'))->toBe('default');
        });
        
        it('converts non-string to string', function () {
            expect($this->request->getString('page'))->toBe('2');
        });
        
        it('returns empty string as default', function () {
            expect($this->request->getString('nonexistent'))->toBe('');
        });
    });
    
    describe('getInt()', function () {
        
        it('returns integer from string', function () {
            expect($this->request->getInt('page'))->toBe(2);
        });
        
        it('returns default for invalid integer', function () {
            expect($this->request->getInt('count', 10))->toBe(10);
        });
        
        it('returns default for missing parameter', function () {
            expect($this->request->getInt('missing', 99))->toBe(99);
        });
        
        it('returns zero as default', function () {
            expect($this->request->getInt('nonexistent'))->toBe(0);
        });
        
        it('handles negative integers', function () {
            $req = new Request(post: ['offset' => '-5']);
            expect($req->getInt('offset'))->toBe(-5);
        });
        
        it('rejects float as integer', function () {
            $req = new Request(post: ['value' => '3.14']);
            expect($req->getInt('value', 10))->toBe(10);
        });
    });
    
    describe('getBool()', function () {
        
        it('returns true for "1"', function () {
            expect($this->request->getBool('active'))->toBeTrue();
        });
        
        it('returns false for missing parameter', function () {
            expect($this->request->getBool('missing'))->toBeFalse();
        });
        
        it('recognizes various true values', function () {
            $req = new Request(post: [
                'flag1' => '1',
                'flag2' => 'true',
                'flag3' => 'yes',
                'flag4' => 'on',
                'flag5' => true
            ]);
            
            expect($req->getBool('flag1'))->toBeTrue();
            expect($req->getBool('flag2'))->toBeTrue();
            expect($req->getBool('flag3'))->toBeTrue();
            expect($req->getBool('flag4'))->toBeTrue();
            expect($req->getBool('flag5'))->toBeTrue();
        });
        
        it('recognizes various false values', function () {
            $req = new Request(post: [
                'flag1' => '0',
                'flag2' => 'false',
                'flag3' => 'no',
                'flag4' => 'off',
                'flag5' => false
            ]);
            
            expect($req->getBool('flag1'))->toBeFalse();
            expect($req->getBool('flag2'))->toBeFalse();
            expect($req->getBool('flag3'))->toBeFalse();
            expect($req->getBool('flag4'))->toBeFalse();
            expect($req->getBool('flag5'))->toBeFalse();
        });
    });
    
    describe('getArray()', function () {
        
        it('returns array from query parameter', function () {
            expect($this->request->getArray('tags'))->toBe(['php', 'security']);
        });
        
        it('returns default for missing parameter', function () {
            expect($this->request->getArray('missing', ['default']))->toBe(['default']);
        });
        
        it('returns default for non-array', function () {
            expect($this->request->getArray('title', ['fallback']))->toBe(['fallback']);
        });
        
        it('returns empty array as default', function () {
            expect($this->request->getArray('nonexistent'))->toBe([]);
        });
    });
    
    describe('getEmail()', function () {
        
        it('returns valid email', function () {
            expect($this->request->getEmail('email'))->toBe('user@example.com');
        });
        
        it('returns null for invalid email', function () {
            $req = new Request(post: ['email' => 'not-an-email']);
            expect($req->getEmail('email'))->toBeNull();
        });
        
        it('returns custom default for invalid email', function () {
            $req = new Request(post: ['email' => 'invalid']);
            expect($req->getEmail('email', 'default@test.com'))->toBe('default@test.com');
        });
        
        it('returns null for missing parameter', function () {
            expect($this->request->getEmail('missing'))->toBeNull();
        });
        
        it('validates complex email formats', function () {
            $req = new Request(post: ['email' => 'user+tag@subdomain.example.co.uk']);
            expect($req->getEmail('email'))->toBe('user+tag@subdomain.example.co.uk');
        });
    });
    
    describe('getUrl()', function () {
        
        it('returns valid URL', function () {
            $req = new Request(post: ['url' => 'https://example.com/path?query=1']);
            expect($req->getUrl('url'))->toBe('https://example.com/path?query=1');
        });
        
        it('returns null for invalid URL', function () {
            $req = new Request(post: ['url' => 'not a url']);
            expect($req->getUrl('url'))->toBeNull();
        });
        
        it('returns custom default for invalid URL', function () {
            $req = new Request(post: ['url' => 'invalid']);
            expect($req->getUrl('url', 'https://default.com'))->toBe('https://default.com');
        });
        
        it('validates various URL schemes', function () {
            $req = new Request(post: [
                'http' => 'http://example.com',
                'https' => 'https://example.com',
                'ftp' => 'ftp://example.com'
            ]);
            
            expect($req->getUrl('http'))->toBe('http://example.com');
            expect($req->getUrl('https'))->toBe('https://example.com');
            expect($req->getUrl('ftp'))->toBe('ftp://example.com');
        });
    });
    
    describe('getFloat()', function () {
        
        it('returns float from string', function () {
            expect($this->request->getFloat('price'))->toBe(19.99);
        });
        
        it('returns default for invalid float', function () {
            expect($this->request->getFloat('count', 5.5))->toBe(5.5);
        });
        
        it('returns zero as default', function () {
            expect($this->request->getFloat('nonexistent'))->toBe(0.0);
        });
        
        it('handles negative floats', function () {
            $req = new Request(post: ['temp' => '-3.14']);
            expect($req->getFloat('temp'))->toBe(-3.14);
        });
        
        it('handles integer as float', function () {
            $req = new Request(post: ['value' => '42']);
            expect($req->getFloat('value'))->toBe(42.0);
        });
    });
    
    describe('Type Safety Benefits', function () {
        
        it('prevents type juggling vulnerabilities', function () {
            $req = new Request(post: ['id' => '5 OR 1=1']);
            
            // Type-safe getter rejects SQL injection attempt
            expect($req->getInt('id', 0))->toBe(0); // Not 5
        });
        
        it('prevents boolean bypass attacks', function () {
            $req = new Request(post: ['is_admin' => 'true']);
            
            // Type-safe getter properly validates
            expect($req->getBool('is_admin'))->toBeTrue();
        });
        
        it('prevents email injection', function () {
            $req = new Request(post: ['email' => 'user@example.com%0ABcc:hacker@evil.com']);
            
            // Type-safe getter rejects malformed email
            expect($req->getEmail('email'))->toBeNull();
        });
    });
});
