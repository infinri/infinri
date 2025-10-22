# SECURITY FIXES - QUICK START GUIDE

**Status:** Dependencies added, database schema ready, ready for implementation  
**Packages Added:** ✅ Symfony Security + HTMLPurifier (composer.json)  
**Database Schema:** ✅ Sessions in Core, Admin Users in Admin module  
**Data Patch:** ✅ InstallDefaultAdminUser.php in Admin module  
**Implementation Plan:** ✅ Complete (see `docs/SECURITY_FIXES_IMPLEMENTATION_PLAN.md`)

**Architecture Decision:**
- `sessions` table → **Core module** (foundational infrastructure)
- `admin_users` table → **Admin module** (admin-specific)
- `users` table → **Reserved for future Customer module** (frontend users)

---

## 🎯 WHAT WE'RE FIXING

### Critical Issues (MUST FIX)
1. ❌ **No Authentication** → ✅ Symfony Security Component
2. ❌ **No CSRF Protection** → ✅ Symfony CSRF Tokens  
3. ❌ **HTMLPurifier Missing** → ✅ Added to composer.json
4. ❌ **CSP Unsafe Directives** → ✅ Nonce-based CSP

### High Priority Issues
5. ❌ Global Namespace Bypass → ✅ Remove exception
6. ❌ IP Spoofing Risk → ✅ Validate X-Forwarded-For
7. ❌ External Resources (SSRF) → ✅ Disable external URIs
8. ❌ Module Discovery Tax → ✅ Cache module list
9. ❌ Singleton Issues → 🔄 Gradual refactor

---

## 📦 DEPENDENCIES ADDED TO COMPOSER.JSON

```json
"ezyang/htmlpurifier": "^4.17",           // XSS protection
"symfony/http-foundation": "^7.3",        // Enhanced request/response
"symfony/password-hasher": "^7.3",        // Secure password hashing
"symfony/security-core": "^7.3",          // Authentication core
"symfony/security-csrf": "^7.3",          // CSRF protection
"symfony/security-http": "^7.3",          // HTTP security layer
```

**Good news:** `symfony/security-csrf` was already there! We added the rest.

---

## 🚀 NEXT STEPS

### Step 1: Install Dependencies (5 minutes)

```bash
composer install
```

This will install:
- Symfony Security components (battle-tested authentication)
- HTMLPurifier (industry-standard XSS protection)
- Symfony HTTP Foundation (enhanced session management)

### Step 2: Create Database Migration (10 minutes)

Create file: `db/migrations/001_create_users_and_sessions.sql`

```sql
-- Run this SQL on your database
-- (Full SQL provided in SECURITY_FIXES_IMPLEMENTATION_PLAN.md)

CREATE TABLE users (
    user_id SERIAL PRIMARY KEY,
    username VARCHAR(180) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles JSON NOT NULL DEFAULT '["ROLE_USER"]',
    is_active BOOLEAN NOT NULL DEFAULT true,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL
);

CREATE TABLE sessions (
    sess_id VARCHAR(128) PRIMARY KEY,
    sess_data TEXT NOT NULL,
    sess_lifetime INTEGER NOT NULL,
    sess_time INTEGER NOT NULL
);

-- Default admin user (password: admin123 - CHANGE IMMEDIATELY!)
INSERT INTO users (username, email, password, roles) 
VALUES ('admin', 'admin@infinri.local', 
        '$2y$13$...', -- bcrypt hash
        '["ROLE_ADMIN"]');
```

### Step 3: Create Security Infrastructure (1-2 hours)

Follow the detailed implementation plan in:
`docs/SECURITY_FIXES_IMPLEMENTATION_PLAN.md`

**Files to create:**
```
app/Infinri/Core/Security/
├── Entity/User.php                          ← User entity
├── Provider/UserProvider.php                ← Loads users from DB
├── Authenticator/LoginFormAuthenticator.php ← Handles login
├── CsrfTokenManager.php                     ← CSRF token management
└── Voter/AdminVoter.php                     ← Authorization rules

app/Infinri/Core/App/Middleware/
└── CsrfProtectionMiddleware.php             ← CSRF validation

app/Infinri/Core/Controller/Adminhtml/Auth/
├── Login.php                                ← Login page
└── Logout.php                               ← Logout handler

app/Infinri/Core/Helper/
└── Csrf.php                                 ← CSRF helper for templates
```

### Step 4: Apply Critical Fixes (1 hour)

**4.1: Fix ContentSanitizer (CRITICAL)**
```php
// app/Infinri/Core/Helper/ContentSanitizer.php
public function __construct()
{
    if (!class_exists('\HTMLPurifier')) {
        throw new \RuntimeException(
            'HTMLPurifier required. Install: composer require ezyang/htmlpurifier'
        );
    }
}
```

**4.2: Fix SecurityHeadersMiddleware (CRITICAL)**
```php
// Remove unsafe-inline and unsafe-eval from CSP
"script-src 'self' 'nonce-{$nonce}'", // NO unsafe-inline!
"style-src 'self' 'nonce-{$nonce}'",
```

**4.3: Fix FrontController (HIGH)**
```php
// Remove lines 223-226 (global namespace bypass)
// DELETE: if (strpos($controllerClass, '\\') === false) { return true; }
```

**4.4: Fix Request::getClientIp() (HIGH)**
```php
// Only trust X-Forwarded-For from trusted proxies
$trustedProxies = explode(',', $_ENV['TRUSTED_PROXIES'] ?? '');
if (in_array($remoteAddr, $trustedProxies, true)) {
    // Trust proxy header
}
```

### Step 5: Update Templates with CSRF Tokens (30 minutes)

**Every form needs:**
```php
<?php
// In your .phtml templates
/** @var \Infinri\Core\Helper\Csrf $csrf */
$csrf = $block->getData('csrf_helper');
?>

<form method="POST" action="/admin/page/save">
    <?= $csrf->getFormFields('page_form') ?>
    
    <!-- Your form fields here -->
    <input type="text" name="title" />
    
    <button type="submit">Save</button>
</form>
```

### Step 6: Test Everything (1 hour)

```bash
# 1. Run tests
composer test

# 2. Test authentication
# - Visit /admin/login
# - Login with admin/admin123
# - Should redirect to dashboard

# 3. Test CSRF protection
# - Submit form without CSRF token → should get 403
# - Submit form with token → should work

# 4. Test XSS protection
# - Try injecting: <script>alert('XSS')</script>
# - Should be stripped by HTMLPurifier

# 5. Check CSP headers
# - Open browser DevTools → Network → Check headers
# - Should NOT contain unsafe-inline or unsafe-eval
```

---

## 🔒 SECURITY IMPROVEMENTS

### Before (Audit Score: 62/100)
- ❌ No authentication
- ❌ No CSRF protection
- ❌ XSS via fallback sanitizer
- ❌ CSP allows unsafe-inline
- ❌ Global namespace bypass
- ❌ Trusts X-Forwarded-For

### After (Expected Score: 85+/100)
- ✅ Symfony Security authentication
- ✅ CSRF tokens on all forms
- ✅ HTMLPurifier XSS protection
- ✅ Strict CSP with nonces
- ✅ Controller whitelist enforced
- ✅ Validated proxy headers

---

## 📊 EXPECTED TIMELINE

- **Week 1:** Authentication + CSRF (Days 1-5)
- **Week 2:** XSS fixes + CSP hardening (Days 6-10)
- **Week 3:** High priority fixes + integration (Days 11-15)
- **Week 4:** Testing + deployment (Days 16-20)

**Total: 4 weeks for production-ready security**

---

## ⚡ QUICK WINS (Can do TODAY)

### 1. Install Dependencies (5 min)
```bash
composer install
```

### 2. Fix HTMLPurifier Check (2 min)
Make ContentSanitizer throw error if HTMLPurifier missing

### 3. Remove CSP Unsafe Directives (5 min)
Update SecurityHeadersMiddleware.php lines 68, 71

### 4. Remove Global Namespace Bypass (2 min)
Delete FrontController.php lines 223-226

### 5. Add .env Configuration (5 min)
```env
# Add to .env
TRUSTED_PROXIES=127.0.0.1,10.0.0.0/8
SESSION_LIFETIME=3600
CSRF_TOKEN_TTL=3600
```

**Total: ~20 minutes for critical fixes!**

---

## 🎓 WHY SYMFONY SECURITY?

✅ **Battle-tested:** Used by thousands of enterprise applications  
✅ **Complete:** Authentication, authorization, CSRF, sessions  
✅ **Flexible:** Supports DB, LDAP, OAuth, custom providers  
✅ **Well-documented:** Excellent docs and community support  
✅ **Maintained:** Active development, security patches  
✅ **Integrated:** You already use Symfony components  
✅ **Performance:** Optimized for production use

**Alternative packages we rejected:**
- Laravel Passport ❌ (requires Laravel framework)
- Auth0 ❌ (external service, costs money)
- Custom solution ❌ (reinventing the wheel, security risks)
- jwt-auth ❌ (stateless only, not suitable for admin panel)

---

## 📚 ADDITIONAL RESOURCES

### Documentation Created
1. `audits/INFINRI_FULL_AUDIT_REPORT.md` - Complete security audit
2. `docs/SECURITY_FIXES_IMPLEMENTATION_PLAN.md` - Detailed implementation guide
3. `SECURITY_FIXES_SUMMARY.md` - This file (quick reference)

### Symfony Security Docs
- [Symfony Security](https://symfony.com/doc/current/security.html)
- [CSRF Protection](https://symfony.com/doc/current/security/csrf.html)
- [Password Hashing](https://symfony.com/doc/current/security/passwords.html)

### Testing Tools
- [OWASP ZAP](https://www.zaproxy.org/) - Security scanner
- [Burp Suite](https://portswigger.net/burp) - Penetration testing
- [SecurityHeaders.com](https://securityheaders.com/) - Check headers

---

## 🆘 SUPPORT & TROUBLESHOOTING

### Common Issues

**Issue:** Composer install fails
```bash
# Clear cache and retry
composer clear-cache
composer install --no-cache
```

**Issue:** HTMLPurifier not found
```bash
# Verify installation
composer show ezyang/htmlpurifier
# Should show version 4.17.x
```

**Issue:** CSRF tokens not validating
```bash
# Check session configuration
# Ensure sessions are enabled in php.ini
# Verify session directory is writable: var/session/
```

**Issue:** CSP blocking inline scripts
```bash
# Make sure nonce is being generated and passed to templates
# Check $_SERVER['CSP_NONCE'] is set
# Add nonce attribute to inline scripts: <script nonce="<?= $_SERVER['CSP_NONCE'] ?>">
```

---

## ✨ NEXT PHASE: PERFORMANCE OPTIMIZATION

Once security is solid, we can tackle:
1. ✅ Module list caching (Week 3)
2. Page/block output caching
3. Database query caching
4. Redis/Memcached integration
5. Asset optimization
6. CDN configuration

**But security comes first!**

---

**Ready to start? Run:** `composer install`

**Questions? Check:** `docs/SECURITY_FIXES_IMPLEMENTATION_PLAN.md`

**Need help? Review:** `audits/INFINRI_FULL_AUDIT_REPORT.md`

---

*Prepared by Cascade AI - 2025-10-21*
