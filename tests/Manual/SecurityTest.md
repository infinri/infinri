# Manual Security Testing - Phase 1 Changes

**Date**: 2025-11-02  
**Changes**: File uploads, secure cookies, session security, logout CSRF

---

## Test 1: File Upload Security ✅

### Test 1.1: Path Traversal Prevention
**File**: `Upload.php` & `Uploadmultiple.php`

```bash
# Try to upload file with path traversal in name
curl -X POST http://localhost:8080/admin/cms/media/upload \
  -F "image=@test.jpg;filename=../../evil.php"

# Expected: Filename sanitized to remove path components
# Expected: Extension validated against whitelist
```

**✅ PASS if**: File saved with sanitized name (no `../`)

### Test 1.2: Extension Whitelist
```bash
# Try to upload .php file
curl -X POST http://localhost:8080/admin/cms/media/upload \
  -F "image=@shell.php"

# Expected: "Invalid file extension: php" error
```

**✅ PASS if**: Upload rejected with extension error

### Test 1.3: Folder Traversal Prevention
```bash
# Try folder parameter with traversal
curl -X POST http://localhost:8080/admin/cms/media/uploadmultiple \
  -F "folder=../../etc" \
  -F "files=@test.jpg"

# Expected: "Invalid folder name" error
```

**✅ PASS if**: Folder validation blocks traversal

---

## Test 2: Secure Cookie Flags ✅

### Test 2.1: Remember-Me Cookie Inspection
**File**: `RememberTokenService.php`

```bash
# Login with "Remember Me" checked
# Then inspect cookies in browser DevTools

# Expected cookie attributes:
# - secure: true
# - httponly: true  
# - samesite: Strict
```

**Manual Steps**:
1. Open browser DevTools → Application → Cookies
2. Login to `/admin/auth/login` with Remember Me checked
3. Find `admin_remember` cookie
4. Verify flags:
   - ✅ Secure flag enabled
   - ✅ HttpOnly flag enabled
   - ✅ SameSite = Strict

**✅ PASS if**: All three flags present

### Test 2.2: Cookie Over HTTP Blocked
```bash
# Try to access admin over HTTP (not HTTPS)
curl -v http://localhost:8080/admin/auth/login

# Expected: Cookie NOT sent (secure flag prevents HTTP transmission)
```

**✅ PASS if**: Cookie only sent over HTTPS

---

## Test 3: Session Security ✅

### Test 3.1: Session Regeneration on Login
**File**: `Post.php` (line 105)

```bash
# Monitor session ID before and after login
# Session ID should change after successful login
```

**Manual Steps**:
1. Access `/admin/auth/login`
2. Note session ID from cookie: `PHPSESSID=abc123...`
3. Login successfully
4. Check session ID again
5. **✅ PASS if**: Session ID changed (prevents fixation)

### Test 3.2: Session Cleared on Logout
**File**: `Logout.php`

```bash
# Check session data after logout
```

**Manual Steps**:
1. Login to admin
2. Logout
3. Try to access `/admin/dashboard`
4. **✅ PASS if**: Redirected to login (session destroyed)

---

## Test 4: Logout CSRF Protection ✅

### Test 4.1: GET Request Blocked
**File**: `Logout.php` (line 31)

```bash
# Try logout via GET request (simulate CSRF attack)
curl http://localhost:8080/admin/auth/login/logout

# Expected: "Logout failed: Not a POST request" in logs
# Expected: Redirect to dashboard (not logged out)
```

**✅ PASS if**: GET request rejected, user still logged in

### Test 4.2: Missing CSRF Token
```bash
# Try logout POST without CSRF token
curl -X POST http://localhost:8080/admin/auth/login/logout

# Expected: "Invalid CSRF token" in logs  
# Expected: Redirect to dashboard (not logged out)
```

**✅ PASS if**: Missing token rejected

### Test 4.3: Valid Logout Flow
```bash
# Logout with proper POST + CSRF token
# (Must be done through browser/UI)
```

**Manual Steps**:
1. Login to admin
2. Click "Logout" button (sends POST with CSRF)
3. **✅ PASS if**: Successfully logged out

---

## Security Improvements Summary

| Feature | Before | After | Status |
|---------|--------|-------|--------|
| **File Upload Path Traversal** | Vulnerable | Protected | ✅ |
| **Upload Extension Validation** | Missing | Enforced | ✅ |
| **Folder Traversal** | Vulnerable | Protected | ✅ |
| **Cookie Secure Flag** | `false` | `true` | ✅ |
| **Cookie SameSite** | `Lax` | `Strict` | ✅ |
| **Session Regeneration** | Present | Present | ✅ |
| **Logout CSRF** | Missing | Protected | ✅ |

---

## Automated Test Commands

```bash
# Run full test suite
./vendor/bin/pest

# Run security-specific tests (when created)
./vendor/bin/pest tests/Security/

# Check for XSS vulnerabilities (future)
./vendor/bin/pest tests/Security/XssTest.php

# Check for SQL injection (future)
./vendor/bin/pest tests/Security/SqlInjectionTest.php
```

---

## Production Deployment Checklist

Before deploying to production:

- [ ] All manual tests pass
- [ ] HTTPS enabled on production server
- [ ] Cookie flags verified in production
- [ ] File upload directory has `.htaccess` blocking PHP
- [ ] Session timeout configured (30 minutes recommended)
- [ ] Rate limiting configured (future: Phase 2.5)
- [ ] Monitor logs for security events

---

## Next Phase 1 Items

- **1.1** - XSS Protection (HTMLPurifier)
- **1.2** - CSRF Audit (all POST endpoints)
- **1.3** - SQL Injection Review

---

**Testing Status**: Manual verification required  
**Automated Tests**: To be created in Phase 4  
**Risk Level**: LOW (security hardening only, no functionality changes)
