# Infinri Audit Remediation Plan

**Timeline**: 9-10 weeks | **Status**: Not Started  
**Sources**: audit.md + aduit2.md (comprehensive architecture review)

---

## Phase 1: Critical Security (Week 1.5) 🔴

### 1.1 XSS - CMS Content Sanitization
- [ ] Install HTMLPurifier: `composer require ezyang/htmlpurifier`
- [ ] Create `Core/Helper/Sanitizer.php`
- [ ] **Sanitize content on SAVE (not display)** ⭐
- [ ] Update `Cms/view/frontend/templates/page/view.phtml` to sanitize output
- [ ] Add tests for XSS injection attempts

### 1.2 CSRF Protection Audit
- [ ] Audit all POST/PUT/DELETE endpoints
- [ ] Verify CSRF tokens on all state-changing forms
- [ ] Add missing CSRF tokens
- [ ] Create CSRF middleware for auto-validation
- [ ] **Ensure logout requires POST + CSRF** ⭐

### 1.3 SQL Injection Review
- [ ] Audit ResourceModel SQL queries
- [ ] Verify parameterized queries everywhere
- [ ] Add SQL injection tests

### 1.4 File Upload Security **[CRITICAL - NEW]** ⭐
- [ ] Use `basename()` on all uploaded filenames
- [ ] Whitelist filename characters: `[A-Za-z0-9._-]`
- [ ] Add unique prefix to prevent collisions
- [ ] Set `.htaccess` in media folder to block PHP execution
- [ ] Validate MIME types with `finfo`
- [ ] Test path traversal attacks blocked

### 1.5 Secure Cookie Flags **[CRITICAL - NEW]** ⭐
- [ ] Add `secure: true` to remember-me cookies
- [ ] Add `samesite: 'Strict'` or `'Lax'`
- [ ] Verify HttpOnly flag is set
- [ ] Test cookies only sent over HTTPS

### 1.6 Session Security **[NEW]** ⭐
- [ ] Add `session_regenerate_id(true)` on login
- [ ] Implement session timeout
- [ ] Clear session data on logout
- [ ] Test session fixation prevention

---

## Phase 2: Security Infrastructure (Week 2) 🟡

### 2.1 Request Service
- [ ] Enhance `Core/App/Request.php` with type-safe getters
- [ ] Replace all `$_GET/$_POST/$_REQUEST` with Request methods
- [ ] Add input validation framework

### 2.2 Session Service
- [ ] Create `Core/App/Session.php` abstraction
- [ ] Replace all `$_SESSION` usage
- [ ] Add session security (fixation prevention, timeout)

### 2.3 Output Escaping
- [ ] Audit all templates for unescaped output
- [ ] Add template linting for XSS detection
- [ ] Fix all unsafe output

### 2.4 Template Path Validation **[NEW]** ⭐
- [ ] Prevent directory traversal in template names
- [ ] Whitelist allowed characters in template paths
- [ ] Validate template files exist before including
- [ ] Test `../../` attacks are blocked

### 2.5 Rate Limiting **[NEW]** ⭐
- [ ] Create `RateLimiter` service
- [ ] Add login attempt tracking (max 5 per 15 min)
- [ ] Implement lockout on exceeded attempts
- [ ] Add rate limiting to password reset
- [ ] Test brute force attacks are blocked

---

## Phase 3: SOLID Refactoring (Week 3-4) 🔵

### 3.1 FrontController (SRP)
- [ ] Extract `Core/App/Router.php` (route matching)
- [ ] Extract `Core/App/Dispatcher.php` (controller execution)
- [ ] Slim FrontController to orchestration only (~100 LOC)

### 3.2 UiComponentRenderer (SRP)
- [ ] Extract `Core/View/Element/GridRenderer.php`
- [ ] Extract `Core/View/Element/FormRenderer.php`
- [ ] Extract `Core/View/Element/ComponentResolver.php`
- [ ] Slim UiComponentRenderer to delegation only

### 3.3 Remove HTML from Controllers
- [ ] Find controllers with `<<<HTML` blocks
- [ ] Move HTML to templates
- [ ] Controllers only pass data to layout

### 3.4 Media Picker (SRP)
- [ ] Create `Core/Model/Media/MediaLibrary.php` service
- [ ] Create template for media picker
- [ ] Slim controller to data + layout

---

## Phase 4: DRY/KISS (Week 5) 🟢

### 4.1 Base Controllers
- [ ] Create `AbstractController` with common methods
- [ ] Create `AbstractAdminController` for admin
- [ ] Refactor all controllers to extend base

### 4.2 Helper Classes
- [ ] Create `Core/Helper/Data.php` (utilities)
- [ ] Create `Core/Helper/Url.php` (URL operations)
- [ ] Create `Core/Helper/String.php` (string utilities)
- [ ] Replace duplicated code with helpers

### 4.3 HTML Builders
- [ ] Create `Core/View/TableBuilder.php`
- [ ] Create `Core/View/FormBuilder.php`
- [ ] Replace inline HTML with builders

### 4.4 Constants
- [ ] Create constant classes (HttpStatus, CmsStatus, etc.)
- [ ] Replace magic strings/numbers

### 4.5 Replace Static Logger Calls **[NEW - HIGH EFFORT]** ⭐
- [ ] Create PSR-3 logger adapter
- [ ] Replace all `Logger::debug()` static calls (~50+ files)
- [ ] Replace all `Logger::error()` static calls
- [ ] Inject `LoggerInterface` via DI
- [ ] Update all affected classes
- [ ] Test logging still works

### 4.6 AbstractModel Field Validation **[NEW]** ⭐
- [ ] Add `getAllowedFields()` abstract method
- [ ] Validate fields in `setData()` method
- [ ] Throw exception on invalid fields
- [ ] Update all model classes
- [ ] Test invalid field rejection

### 4.7 File Naming Cleanup **[NEW]**
- [ ] Rename `NonComposerCompotentRegistration.php` → `NonComposerComponentRegistration.php`
- [ ] Update any references
- [ ] Run full test suite

### 4.8 ObjectManager Usage Guidelines **[DOCUMENTATION]**
- [ ] Document when ObjectManager is acceptable
- [ ] Document when to use constructor injection instead
- [ ] Create code review checklist
- [ ] Add to architecture guide

---

## Phase 5: Front-End (Week 6) 🟣

### 5.1 Extract Inline Scripts
- [ ] Find all `<script>` tags in templates
- [ ] Move to separate `.js` files
- [ ] Add CSP header

### 5.2 Fix Asset Management
- [ ] Fix login form layout issue (remove workaround)
- [ ] Load all assets via layout XML
- [ ] Remove inline styles

### 5.3 Asset Versioning
- [ ] Create `Core/View/AssetManager.php`
- [ ] Add version hashing for cache busting
- [ ] Update templates to use AssetManager

---

## Phase 6: Performance (Week 7-8) ⚡

### 6.1 Layout Processor
- [ ] Profile with Xdebug
- [ ] Optimize nested loops to O(N)
- [ ] Add layout caching

### 6.2 Grid Rendering
- [ ] Implement proper pagination
- [ ] Add grid HTML caching
- [ ] Virtual scrolling for large datasets

### 6.3 Database
- [ ] Profile queries, find N+1 patterns
- [ ] Add missing indexes
- [ ] Implement query result caching
- [ ] **Optimize menu tree building (O(n²) → O(n))** ⭐

### 6.4 Media Library
- [ ] Add pagination (50-100 files per page)
- [ ] Thumbnail caching
- [ ] Directory indexing in database

### 6.5 Caching System
- [ ] Create cache abstraction (File/Redis/Memcached)
- [ ] Cache configuration, layouts, blocks
- [ ] Full-page cache for frontend

---

## Success Metrics

### Security (Phase 1-2)
- [ ] Zero XSS vulnerabilities (security scan)
- [ ] 100% CSRF coverage (all forms protected)
- [ ] **File uploads sanitized (no path traversal)** ⭐
- [ ] **Secure flag on all cookies** ⭐
- [ ] **Session regeneration on login** ⭐
- [ ] **Rate limiting active (5 attempts/15min)** ⭐
- [ ] **Template paths validated** ⭐
- [ ] No raw superglobal usage (code audit)

### Code Quality (Phase 3-4)
- [ ] Average class complexity < 10 (cyclomatic)
- [ ] **Static Logger calls eliminated** ⭐
- [ ] **AbstractModel validates fields** ⭐
- [ ] **File naming corrected** ⭐
- [ ] FrontController < 150 LOC
- [ ] Test coverage > 80%

### Performance (Phase 6)
- [ ] Page load < 200ms (after caching)
- [ ] **Menu tree building O(n)** ⭐
- [ ] Module registration cached
- [ ] Layout XML cached

### Documentation
- [ ] **ObjectManager usage guidelines** ⭐
- [ ] Security best practices documented
- [ ] Architecture guide updated

---

## Testing Strategy

**Per Phase**:
1. Run full test suite before changes
2. Add tests for new code
3. Run test suite after changes
4. Manual QA of affected features
5. Security testing (XSS, CSRF, SQLi)

**Final QA**:
- Security penetration testing
- Performance benchmarking
- Load testing (1000+ concurrent users)
- Browser compatibility testing

---

## Rollback Plan

Each phase is independent:
- Keep feature branches per phase
- Merge only after full testing
- Can rollback individual phases if issues
- Tag releases after each phase

---

## Documentation Updates

Update after each phase:
- [ ] `ARCHITECTURE_GUIDE.md`
- [ ] `SECURITY.md` (new file)
- [ ] `PERFORMANCE.md` (new file)
- [ ] `CONTRIBUTING.md` (coding standards)
- [ ] Inline code documentation

---

## Quick Reference: File Changes

### Phase 1 Files
- Create: `Core/Helper/Sanitizer.php`
- Modify: `Cms/view/frontend/templates/page/view.phtml`
- Review: All controllers with POST handlers

### Phase 2 Files
- Create: `Core/App/Session.php`
- Enhance: `Core/App/Request.php`
- Modify: All files using `$_SESSION`, `$_GET`, `$_POST`

### Phase 3 Files
- Create: `Core/App/Router.php`, `Dispatcher.php`, `Route.php`
- Create: `Core/View/Element/GridRenderer.php`, `FormRenderer.php`
- Create: `Core/Model/Media/MediaLibrary.php`
- Modify: `Core/App/FrontController.php` (slim down)

### Phase 4 Files
- Create: `Core/Controller/AbstractController.php`
- Create: `Core/Helper/Data.php`, `Url.php`, `String.php`
- Create: `Core/View/TableBuilder.php`, `FormBuilder.php`

### Phase 5 Files
- Create: JS files for extracted scripts
- Create: `Core/View/AssetManager.php`
- Modify: Templates with inline scripts/styles

### Phase 6 Files
- Modify: `Core/Model/Layout/Processor.php`
- Create: `Core/Cache/CacheInterface.php`
- Modify: `db_schema.xml` (add indexes)

---

**Start Date**: TBD  
**Owner**: Development Team  
**Review Frequency**: Weekly progress meetings
