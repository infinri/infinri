<?php

declare(strict_types=1);

use Infinri\Core\App\Session;
use Infinri\Core\App\Request;

beforeEach(function () {
    // Clear any existing session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    $_SESSION = [];
    
    $this->session = new Session();
});

afterEach(function () {
    // Cleanup
    $_SESSION = [];
});

describe('Session Service (Phase 2.2)', function () {
    
    describe('Basic Operations', function () {
        
        it('starts session automatically', function () {
            $this->session->set('test', 'value');
            expect($this->session->isStarted())->toBeTrue();
        });
        
        it('sets and gets values', function () {
            $this->session->set('key', 'value');
            expect($this->session->get('key'))->toBe('value');
        });
        
        it('returns default for missing key', function () {
            expect($this->session->get('missing', 'default'))->toBe('default');
        });
        
        it('checks if key exists', function () {
            $this->session->set('exists', 'yes');
            expect($this->session->has('exists'))->toBeTrue();
            expect($this->session->has('missing'))->toBeFalse();
        });
        
        it('removes values', function () {
            $this->session->set('temp', 'data');
            expect($this->session->has('temp'))->toBeTrue();
            
            $this->session->remove('temp');
            expect($this->session->has('temp'))->toBeFalse();
        });
        
        it('clears all data', function () {
            $this->session->set('key1', 'value1');
            $this->session->set('key2', 'value2');
            
            $this->session->clear();
            
            expect($this->session->has('key1'))->toBeFalse();
            expect($this->session->has('key2'))->toBeFalse();
        });
    });
    
    describe('Flash Messages', function () {
        
        it('sets and gets flash messages', function () {
            $this->session->flash('message', 'Hello World');
            expect($this->session->getFlash('message'))->toBe('Hello World');
        });
        
        it('removes flash after reading', function () {
            $this->session->flash('temp', 'data');
            $this->session->getFlash('temp');
            
            expect($this->session->hasFlash('temp'))->toBeFalse();
        });
        
        it('adds success message', function () {
            $this->session->addSuccess('Operation successful');
            expect($this->session->getFlash('success'))->toBe('Operation successful');
        });
        
        it('adds error message', function () {
            $this->session->addError('An error occurred');
            expect($this->session->getFlash('error'))->toBe('An error occurred');
        });
        
        it('adds warning message', function () {
            $this->session->addWarning('Be careful');
            expect($this->session->getFlash('warning'))->toBe('Be careful');
        });
        
        it('adds info message', function () {
            $this->session->addInfo('FYI');
            expect($this->session->getFlash('info'))->toBe('FYI');
        });
        
        it('returns default for missing flash', function () {
            expect($this->session->getFlash('missing', 'default'))->toBe('default');
        });
    });
    
    describe('Session Security', function () {
        
        it('regenerates session ID', function () {
            $this->session->start();
            $oldId = $this->session->getId();
            
            $this->session->regenerate();
            $newId = $this->session->getId();
            
            expect($newId)->not->toBe($oldId);
        });
        
        it('tracks session activity', function () {
            $this->session->updateActivity();
            $timestamp = $this->session->get('_last_activity');
            
            expect($timestamp)->toBeInt();
            expect($timestamp)->toBeLessThanOrEqual(time());
        });
        
        it('detects expired sessions', function () {
            $this->session->set('_last_activity', time() - 7200); // 2 hours ago
            
            expect($this->session->isExpired(3600))->toBeTrue(); // 1 hour timeout
            expect($this->session->isExpired(10000))->toBeFalse(); // 3 hour timeout
        });
        
        it('detects active sessions', function () {
            $this->session->updateActivity();
            expect($this->session->isExpired())->toBeFalse();
        });
        
        it('creates session fingerprint', function () {
            $request = $this->createMock(Request::class);
            $request->method('getUserAgent')->willReturn('Test Browser');
            $request->method('getClientIp')->willReturn('127.0.0.1');
            
            $fingerprint = $this->session->getFingerprint($request);
            
            expect($fingerprint)->toBeString();
            expect(strlen($fingerprint))->toBe(64); // SHA256 hash
        });
        
        it('verifies matching fingerprint', function () {
            $request = $this->createMock(Request::class);
            $request->method('getUserAgent')->willReturn('Test Browser');
            $request->method('getClientIp')->willReturn('127.0.0.1');
            
            // First verification stores fingerprint
            expect($this->session->verifyFingerprint($request))->toBeTrue();
            
            // Second verification matches
            expect($this->session->verifyFingerprint($request))->toBeTrue();
        });
        
        it('detects fingerprint mismatch', function () {
            $request1 = $this->createMock(Request::class);
            $request1->method('getUserAgent')->willReturn('Browser 1');
            $request1->method('getClientIp')->willReturn('127.0.0.1');
            
            $request2 = $this->createMock(Request::class);
            $request2->method('getUserAgent')->willReturn('Browser 2'); // Different!
            $request2->method('getClientIp')->willReturn('127.0.0.1');
            
            // Store fingerprint with request1
            $this->session->verifyFingerprint($request1);
            
            // request2 has different fingerprint
            expect($this->session->verifyFingerprint($request2))->toBeFalse();
        });
    });
    
    describe('Session Lifecycle', function () {
        
        it('gets session ID', function () {
            $this->session->start();
            $id = $this->session->getId();
            
            expect($id)->toBeString();
            expect($id)->not->toBeEmpty();
        });
        
        it('gets session name', function () {
            $name = $this->session->getName();
            expect($name)->toBeString();
        });
        
        it('checks if started', function () {
            expect($this->session->isStarted())->toBeFalse();
            
            $this->session->start();
            expect($this->session->isStarted())->toBeTrue();
        });
        
        it('gets all session data', function () {
            $this->session->set('key1', 'value1');
            $this->session->set('key2', 'value2');
            
            $all = $this->session->all();
            
            expect($all)->toBeArray();
            expect($all)->toHaveKey('key1');
            expect($all)->toHaveKey('key2');
        });
    });
    
    describe('Type Support', function () {
        
        it('stores strings', function () {
            $this->session->set('string', 'test');
            expect($this->session->get('string'))->toBe('test');
        });
        
        it('stores integers', function () {
            $this->session->set('int', 42);
            expect($this->session->get('int'))->toBe(42);
        });
        
        it('stores booleans', function () {
            $this->session->set('bool', true);
            expect($this->session->get('bool'))->toBeTrue();
        });
        
        it('stores arrays', function () {
            $this->session->set('array', ['a', 'b', 'c']);
            expect($this->session->get('array'))->toBe(['a', 'b', 'c']);
        });
        
        it('stores objects', function () {
            $obj = new stdClass();
            $obj->prop = 'value';
            
            $this->session->set('object', $obj);
            $retrieved = $this->session->get('object');
            
            expect($retrieved)->toBeInstanceOf(stdClass::class);
            expect($retrieved->prop)->toBe('value');
        });
    });
});
