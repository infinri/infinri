<?php

declare(strict_types=1);

use Infinri\Core\Security\CsrfGuard;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

describe('CsrfGuard', function () {
    /** @var CsrfTokenManagerInterface&MockObject */
    beforeEach(function () {
        $this->tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->guard = new CsrfGuard($this->tokenManager);
    });

    it('generates tokens using token manager', function () {
        $token = new CsrfToken('test', 'generated-token');
        $this->tokenManager
            ->expects($this->once())
            ->method('getToken')
            ->with('test')
            ->willReturn($token);

        expect($this->guard->generateToken('test'))->toBe('generated-token');
    });

    it('validates tokens via token manager', function () {
        $this->tokenManager
            ->expects($this->once())
            ->method('isTokenValid')
            ->with($this->callback(function ($token) {
                return $token instanceof CsrfToken
                    && $token->getId() === 'test'
                    && $token->getValue() === 'value';
            }))
            ->willReturn(true);

        expect($this->guard->validateToken('test', 'value'))->toBeTrue();
    });

    it('rejects empty token values', function () {
        expect($this->guard->validateToken('test', null))->toBeFalse();
        expect($this->guard->validateToken('test', ''))->toBeFalse();
    });

    it('renders hidden field markup', function () {
        $token = new CsrfToken('form', 'token-value');
        $this->tokenManager
            ->expects($this->once())
            ->method('getToken')
            ->with('form')
            ->willReturn($token);

        $field = $this->guard->getHiddenField('form');
        expect($field)->toContain('name="_csrf_token"');
        expect($field)->toContain('value="token-value"');
    });
});
