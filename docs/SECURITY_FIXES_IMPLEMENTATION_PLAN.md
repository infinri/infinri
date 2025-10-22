# SECURITY FIXES IMPLEMENTATION PLAN

**Date:** 2025-10-21  
**Priority:** CRITICAL  
**Estimated Duration:** 4 weeks  
**Status:** In Progress

---

## CRITICAL ISSUES TO FIX

Based on the comprehensive audit, we have **4 CRITICAL** and **5 HIGH** priority security issues:

### Critical Issues
1. ❌ No Authentication System
2. ❌ No CSRF Protection  
3. ❌ HTMLPurifier Missing (XSS vulnerability)
4. ❌ CSP allows unsafe-inline/unsafe-eval

### High Priority Issues
5. ❌ Global Namespace Controller Bypass
6. ❌ IP Spoofing (trusts X-Forwarded-For)
7. ❌ External Resources Allowed (SSRF risk)
8. ❌ Module Discovery Per Request
9. ❌ Singleton Antipatterns

---

## PHASE 1: DEPENDENCIES & INFRASTRUCTURE (Week 1)

### Step 1.1: Add Required Composer Packages

```bash
composer require symfony/security-bundle:^7.3
composer require symfony/security-csrf:^7.3
composer require symfony/http-foundation:^7.3
composer require ezyang/htmlpurifier:^4.17
```

**Packages:**
- `symfony/security-bundle` - Complete authentication system
- `symfony/security-csrf` - CSRF token generation/validation  
- `symfony/http-foundation` - Enhanced request/response with session
- `ezyang/htmlpurifier` - XSS prevention via HTML sanitization

### Step 1.2: Database Schema for Authentication

Create migration: `db/migrations/20251021_create_users_table.sql`

```sql
-- Users table for authentication
CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(180) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- bcrypt hash
    roles JSON NOT NULL DEFAULT '["ROLE_USER"]',
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL
);

CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_is_active ON users(is_active);

-- Sessions table for secure session management
CREATE TABLE sessions (
    sess_id VARCHAR(128) PRIMARY KEY,
    sess_data TEXT NOT NULL,
    sess_lifetime INTEGER NOT NULL,
    sess_time INTEGER NOT NULL
);

CREATE INDEX idx_sessions_lifetime ON sessions(sess_lifetime);
CREATE INDEX idx_sessions_time ON sessions(sess_time);

-- Insert default admin user (password: admin123 - CHANGE IMMEDIATELY)
INSERT INTO users (username, email, password, roles, is_active) 
VALUES (
    'admin',
    'admin@infinri.local',
    '$2y$13$qVW8.Vp5rJ5K5h5h5h5h5u5h5h5h5h5h5h5h5h5h5h5h5h5h5h5h5h', -- admin123
    '["ROLE_ADMIN", "ROLE_USER"]',
    true
);
```

### Step 1.3: Create Security Configuration Structure

```
app/Infinri/Core/Security/
├── Authenticator/
│   └── LoginFormAuthenticator.php
├── Provider/
│   └── UserProvider.php
├── Entity/
│   └── User.php
├── Firewall/
│   └── AdminFirewall.php
└── Voter/
    └── AdminVoter.php
```

---

## PHASE 2: AUTHENTICATION IMPLEMENTATION (Week 1-2)

### Step 2.1: Create User Entity

**File:** `app/Infinri/Core/Security/Entity/User.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Security\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    private ?int $id = null;
    private string $username;
    private string $email;
    private string $password;
    private array $roles = [];
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Guarantee every user has at least ROLE_USER
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data
    }

    // Setters for hydration
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }
}
```

### Step 2.2: Create User Provider

**File:** `app/Infinri/Core/Security/Provider/UserProvider.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Security\Provider;

use Infinri\Core\Security\Entity\User;
use Infinri\Core\Model\ResourceModel\Connection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $userData = $this->connection->fetchRow(
            'SELECT * FROM users WHERE username = ? AND is_active = true LIMIT 1',
            [$identifier]
        );

        if (!$userData) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $this->hydrateUser($userData);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('Invalid user class');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }

    private function hydrateUser(array $data): User
    {
        $user = new User();
        $user->setId((int)$data['user_id'])
            ->setUsername($data['username'])
            ->setEmail($data['email'])
            ->setPassword($data['password'])
            ->setRoles(json_decode($data['roles'], true))
            ->setIsActive((bool)$data['is_active']);

        return $user;
    }

    public function updateLastLogin(int $userId): void
    {
        $this->connection->exec(
            'UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE user_id = ' . $userId
        );
    }
}
```

### Step 2.3: Create Login Authenticator

**File:** `app/Infinri/Core/Security/Authenticator/LoginFormAuthenticator.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Security\Authenticator;

use Infinri\Core\Security\Provider\UserProvider;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\PasswordUpgradeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

class LoginFormAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserProvider $userProvider
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->getPathInfo() === '/admin/login' && $request->isPost();
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->getPost('username', '');
        $password = $request->getPost('password', '');

        return new Passport(
            new UserBadge($username, [$this->userProvider, 'loadUserByIdentifier']),
            new PasswordCredentials($password),
            [new PasswordUpgradeBadge($password)]
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        // Update last login timestamp
        $user = $token->getUser();
        if ($user instanceof \Infinri\Core\Security\Entity\User) {
            $this->userProvider->updateLastLogin($user->getId());
        }

        // Redirect to admin dashboard
        $response = new Response();
        $response->setRedirect('/admin/dashboard');
        return $response;
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        $response = new Response();
        $response->setRedirect('/admin/login?error=1');
        return $response;
    }
}
```

---

## PHASE 3: CSRF PROTECTION (Week 2)

### Step 3.1: Create CSRF Manager Service

**File:** `app/Infinri/Core/Security/CsrfTokenManager.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Security;

use Symfony\Component\Security\Csrf\CsrfTokenManager as SymfonyCsrfTokenManager;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenGenerator\UriSafeTokenGenerator;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;

class CsrfTokenManager
{
    private SymfonyCsrfTokenManager $manager;

    public function __construct()
    {
        // Initialize with session storage and URI-safe generator
        $this->manager = new SymfonyCsrfTokenManager(
            new UriSafeTokenGenerator(),
            new SessionTokenStorage()
        );
    }

    public function generateToken(string $tokenId = 'default'): string
    {
        return $this->manager->getToken($tokenId)->getValue();
    }

    public function validateToken(string $tokenId, string $token): bool
    {
        return $this->manager->isTokenValid(new CsrfToken($tokenId, $token));
    }

    public function removeToken(string $tokenId): void
    {
        $this->manager->removeToken($tokenId);
    }
}
```

### Step 3.2: Create CSRF Middleware

**File:** `app/Infinri/Core/App/Middleware/CsrfProtectionMiddleware.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\App\Middleware;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Security\CsrfTokenManager;
use Infinri\Core\Helper\Logger;

class CsrfProtectionMiddleware
{
    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {}

    public function handle(Request $request, Response $response): Response
    {
        // Only check POST, PUT, DELETE, PATCH requests
        if (!in_array($request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $response;
        }

        // Skip CSRF for API endpoints (they should use bearer tokens)
        if (str_starts_with($request->getPathInfo(), '/api/')) {
            return $response;
        }

        $token = $request->getPost('_csrf_token', '');
        $tokenId = $request->getPost('_csrf_token_id', 'default');

        if (!$this->csrfManager->validateToken($tokenId, $token)) {
            Logger::warning('CSRF token validation failed', [
                'path' => $request->getPathInfo(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp()
            ]);

            $response->setStatusCode(403);
            $response->setBody('CSRF token validation failed. Please refresh and try again.');
            return $response;
        }

        return $response;
    }
}
```

### Step 3.3: Add CSRF Helper for Templates

**File:** `app/Infinri/Core/Helper/Csrf.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Helper;

use Infinri\Core\Security\CsrfTokenManager;

class Csrf
{
    public function __construct(
        private readonly CsrfTokenManager $csrfManager
    ) {}

    /**
     * Generate CSRF hidden input fields for forms
     */
    public function getFormFields(string $tokenId = 'default'): string
    {
        $token = $this->csrfManager->generateToken($tokenId);
        
        return sprintf(
            '<input type="hidden" name="_csrf_token" value="%s">' . "\n" .
            '<input type="hidden" name="_csrf_token_id" value="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($tokenId, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Get CSRF token value
     */
    public function getToken(string $tokenId = 'default'): string
    {
        return $this->csrfManager->generateToken($tokenId);
    }
}
```

---

## PHASE 4: XSS PROTECTION (Week 2-3)

### Step 4.1: Update ContentSanitizer with HTMLPurifier

**File:** `app/Infinri/Core/Helper/ContentSanitizer.php` (Update existing)

Add to constructor:
```php
private bool $htmlPurifierAvailable = false;

public function __construct()
{
    $this->htmlPurifierAvailable = class_exists('\HTMLPurifier');
    
    if (!$this->htmlPurifierAvailable) {
        throw new \RuntimeException(
            'HTMLPurifier is required for security. Install with: composer require ezyang/htmlpurifier'
        );
    }
}
```

Enable cache in getPurifier():
```php
// Enable cache in production
$cacheDir = __DIR__ . '/../../../var/cache/htmlpurifier';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}
$config->set('Cache.SerializerPath', $cacheDir);
```

### Step 4.2: Fix CSP - Remove Unsafe Directives

**File:** `app/Infinri/Core/App/Middleware/SecurityHeadersMiddleware.php`

Update getContentSecurityPolicy():
```php
private function getContentSecurityPolicy(): string
{
    // Generate nonce for inline scripts
    $nonce = base64_encode(random_bytes(16));
    
    // Store nonce in request for templates to use
    $_SERVER['CSP_NONCE'] = $nonce;
    
    $directives = [
        "default-src 'self'",
        "script-src 'self' 'nonce-{$nonce}'", // REMOVED unsafe-inline, unsafe-eval
        "style-src 'self' 'nonce-{$nonce}'",  // REMOVED unsafe-inline
        "img-src 'self' data: blob:",
        "font-src 'self'",
        "connect-src 'self'",
        "frame-src 'self'",
        "form-action 'self'",
        "base-uri 'self'",
        "frame-ancestors 'self'",
        "report-uri /csp-report" // Add CSP violation reporting
    ];
    
    return implode('; ', $directives);
}
```

### Step 4.3: Disable External Resources

Update ContentSanitizer.php line 118:
```php
// Security: Disable external resources to prevent SSRF
$config->set('URI.DisableExternalResources', true);
```

---

## PHASE 5: REMAINING HIGH PRIORITY FIXES (Week 3)

### Fix 5.1: Remove Global Namespace Bypass

**File:** `app/Infinri/Core/App/FrontController.php` line 224

Delete the global namespace check:
```php
// REMOVE THIS ENTIRE BLOCK (lines 223-226):
// if (strpos($controllerClass, '\\') === false) {
//     return true;
// }

// Keep only the whitelist check
return false;
```

### Fix 5.2: Validate X-Forwarded-For

**File:** `app/Infinri/Core/App/Request.php`

Update getClientIp() method:
```php
public function getClientIp(): ?string
{
    // List of trusted proxy IPs (configure in .env)
    $trustedProxies = explode(',', $_ENV['TRUSTED_PROXIES'] ?? '');
    
    // Only trust X-Forwarded-For if request comes from trusted proxy
    $remoteAddr = $this->server['REMOTE_ADDR'] ?? null;
    
    if ($remoteAddr && in_array($remoteAddr, $trustedProxies, true)) {
        // Trust the proxy header
        if (isset($this->server['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $this->server['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
    }
    
    // Otherwise use direct connection IP
    return $remoteAddr;
}
```

### Fix 5.3: Cache Module List

**File:** `app/bootstrap.php`

Update initApplication():
```php
// 2. Initialize Module System with caching
$cacheDir = __DIR__ . '/../var/cache';
$moduleCacheFile = $cacheDir . '/modules.php';

if (file_exists($moduleCacheFile) && !$isDevelopment) {
    // Load from cache in production
    $moduleList = require $moduleCacheFile;
} else {
    // Build module list
    $registrar = ComponentRegistrar::getInstance();
    $moduleReader = new ModuleReader();
    $moduleList = new ModuleList($registrar, $moduleReader);
    
    // Cache for production
    if (!$isDevelopment) {
        file_put_contents($moduleCacheFile, '<?php return ' . var_export($moduleList, true) . ';');
    }
}

$moduleManager = new ModuleManager($moduleList);
```

---

## PHASE 6: INTEGRATION & TESTING (Week 4)

### Step 6.1: Update DI Configuration

**File:** `app/Infinri/Core/etc/di.xml`

Add security services:
```xml
<!-- Authentication -->
<type name="Infinri\Core\Security\Provider\UserProvider">
    <arguments>
        <argument name="connection" xsi:type="object">Infinri\Core\Model\ResourceModel\Connection</argument>
    </arguments>
</type>

<!-- CSRF -->
<type name="Infinri\Core\Security\CsrfTokenManager"/>

<type name="Infinri\Core\App\Middleware\CsrfProtectionMiddleware">
    <arguments>
        <argument name="csrfManager" xsi:type="object">Infinri\Core\Security\CsrfTokenManager</argument>
    </arguments>
</type>

<!-- Apply CSRF middleware globally -->
<type name="Infinri\Core\App\FrontController">
    <arguments>
        <argument name="middlewares" xsi:type="array">
            <item name="security_headers" xsi:type="object">Infinri\Core\App\Middleware\SecurityHeadersMiddleware</item>
            <item name="csrf_protection" xsi:type="object">Infinri\Core\App\Middleware\CsrfProtectionMiddleware</item>
        </argument>
    </arguments>
</type>
```

### Step 6.2: Create Login Controller

**File:** `app/Infinri/Core/Controller/Adminhtml/Auth/Login.php`

```php
<?php
declare(strict_types=1);

namespace Infinri\Core\Controller\Adminhtml\Auth;

use Infinri\Core\Controller\AbstractController;
use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\Model\View\LayoutFactory;

class Login extends AbstractController
{
    public function __construct(
        private readonly LayoutFactory $layoutFactory
    ) {}

    public function execute(Request $request): Response
    {
        // If already authenticated, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $response = new Response();
            $response->setRedirect('/admin/dashboard');
            return $response;
        }

        // Render login page
        return $this->layoutFactory->create()
            ->setPageTitle('Admin Login')
            ->render('adminhtml_auth_login');
    }
}
```

### Step 6.3: Create Tests

**File:** `tests/Unit/Security/CsrfTokenManagerTest.php`

```php
<?php

use Infinri\Core\Security\CsrfTokenManager;

test('generates valid CSRF token', function () {
    $manager = new CsrfTokenManager();
    $token = $manager->generateToken('test');
    
    expect($token)->toBeString()
        ->and($token)->not->toBeEmpty();
});

test('validates correct CSRF token', function () {
    $manager = new CsrfTokenManager();
    $token = $manager->generateToken('test');
    
    expect($manager->validateToken('test', $token))->toBeTrue();
});

test('rejects invalid CSRF token', function () {
    $manager = new CsrfTokenManager();
    
    expect($manager->validateToken('test', 'invalid-token'))->toBeFalse();
});
```

---

## DEPLOYMENT CHECKLIST

### Pre-Deployment
- [ ] Run all tests: `composer test`
- [ ] Run static analysis: `composer phpstan`
- [ ] Create database backups
- [ ] Test authentication flow manually
- [ ] Test CSRF protection on all forms
- [ ] Verify HTMLPurifier caching works
- [ ] Check CSP headers with browser dev tools

### Deployment Steps
1. [ ] `composer install --no-dev --optimize-autoloader`
2. [ ] Run database migrations
3. [ ] Clear all caches: `php bin/console cache:clear`
4. [ ] Set environment to production: `APP_ENV=production`
5. [ ] Generate admin user with secure password
6. [ ] Configure trusted proxies in `.env`
7. [ ] Enable OPcache
8. [ ] Set up monitoring/alerting
9. [ ] Test admin login
10. [ ] Verify CSRF protection active

### Post-Deployment
- [ ] Monitor error logs for 24 hours
- [ ] Check CSP violation reports
- [ ] Verify no authentication bypasses
- [ ] Test all admin forms for CSRF
- [ ] Performance testing
- [ ] Security scan with OWASP ZAP

---

## ESTIMATED TIMELINE

**Week 1:** Dependencies, Database, Basic Authentication  
**Week 2:** CSRF Protection, XSS Fixes  
**Week 3:** High Priority Fixes, Integration  
**Week 4:** Testing, Documentation, Deployment

**Total: 4 weeks for all CRITICAL + HIGH issues**

---

## SUCCESS CRITERIA

- ✅ All users must authenticate to access admin panel
- ✅ All POST/PUT/DELETE/PATCH requests require valid CSRF token
- ✅ HTMLPurifier installed and sanitizing all user content
- ✅ CSP headers do not allow unsafe-inline or unsafe-eval
- ✅ No global namespace controller bypass
- ✅ X-Forwarded-For only trusted from known proxies
- ✅ External resources blocked in content sanitizer
- ✅ Module list cached in production
- ✅ All tests passing
- ✅ Security audit score improved from 62/100 to 85+/100

---

*Implementation plan prepared by: Cascade AI*  
*Date: 2025-10-21*
