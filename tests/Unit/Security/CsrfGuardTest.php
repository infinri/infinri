<?php

declare(strict_types=1);

use Infinri\Core\Security\CsrfGuard;
use Infinri\Core\App\Session;

describe('CsrfGuard', function () {
    beforeEach(function () {
        // Create a real session for testing
        $this->session = new Session();
        $this->guard = new CsrfGuard($this->session);
    });

    it('generates tokens using session', function () {
        $token = $this->guard->generateToken('test');
        
        expect($token)->not->toBeNull();
        expect($token)->toBeString();
        expect(strlen($token))->toBeGreaterThan(10);
    });

    it('validates tokens via session', function () {
        $token = $this->guard->generateToken('test');
        
        expect($this->guard->validateToken('test', $token))->toBeTrue();
    });

    it('rejects empty token values', function () {
        expect($this->guard->validateToken('test', null))->toBeFalse();
        expect($this->guard->validateToken('test', ''))->toBeFalse();
    });

    it('renders hidden field markup', function () {
        $field = $this->guard->getHiddenField('form');
        
        expect($field)->toContain('name="_csrf_token"');
        expect($field)->toContain('type="hidden"');
        expect($field)->toContain('value=');
    });
    
    it('rejects invalid tokens', function () {
        $this->guard->generateToken('test');
        
        expect($this->guard->validateToken('test', 'invalid-token'))->toBeFalse();
    });
    
    it('rejects tokens with different ids', function () {
        $token = $this->guard->generateToken('test1');
        
        expect($this->guard->validateToken('test2', $token))->toBeFalse();
    });
});
