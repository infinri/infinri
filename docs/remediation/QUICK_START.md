# Remediation Plan - Quick Start Guide

## Overview

This multi-phase plan addresses all findings from `audit.md`. Estimated timeline: **6-8 weeks**.

## Phase Priority

1. **Phase 1** (Week 1): 🔴 Critical Security - XSS, CSRF, SQL injection
2. **Phase 2** (Week 2): 🟡 Security Infrastructure - Request/Session abstraction, escaping
3. **Phase 3** (Week 3-4): 🔵 SOLID Refactoring - Break down large classes
4. **Phase 4** (Week 5): 🟢 DRY/KISS - Eliminate duplication
5. **Phase 5** (Week 6): 🟣 Front-End - Extract inline scripts/CSS
6. **Phase 6** (Week 7-8): ⚡ Performance - Optimize hot paths

---

## Quick Commands

### Start Phase 1
```bash
# Install security dependencies
composer require ezyang/htmlpurifier

# Run security audit
./scripts/security-audit.sh

# Run tests
./vendor/bin/pest
```

### Lint Templates (Phase 2)
```bash
# Find unescaped output
./scripts/lint-templates.sh
```

### Measure Complexity (Phase 3)
```bash
# Install phpmetrics
composer require --dev phpmetrics/phpmetrics

# Run analysis
./vendor/bin/phpmetrics --report-html=reports/metrics app/
```

### Run All Tests
```bash
./vendor/bin/pest --coverage
```

---

## Document Structure

```
docs/remediation/
├── QUICK_START.md          ← You are here
├── PHASE_1_SECURITY.md     ← Week 1: XSS, CSRF, SQLi
├── PHASE_2_INFRASTRUCTURE.md ← Week 2: Request/Session
├── PHASE_3_SOLID.md        ← Week 3-4: Architecture
├── PHASE_4_DRY.md          ← Week 5: Code quality (TODO)
├── PHASE_5_FRONTEND.md     ← Week 6: Assets (TODO)
└── PHASE_6_PERFORMANCE.md  ← Week 7-8: Optimization (TODO)
```

---

## Phase 1 Checklist (Week 1)

Critical security fixes:

- [ ] Install HTMLPurifier
- [ ] Create `Core/Helper/Sanitizer.php`
- [ ] Fix CMS content XSS vulnerability
- [ ] Audit all CSRF tokens
- [ ] Add CSRF middleware
- [ ] Review all SQL queries
- [ ] Run security tests
- [ ] Manual penetration testing

**Exit Criteria**: Zero critical security vulnerabilities

---

## Phase 2 Checklist (Week 2)

Security infrastructure:

- [ ] Enhance Request class (type-safe getters)
- [ ] Replace all `$_GET/$_POST/$_REQUEST`
- [ ] Create Session service
- [ ] Replace all `$_SESSION`
- [ ] Run template linter
- [ ] Fix all unescaped output
- [ ] Add escaping tests

**Exit Criteria**: No raw superglobal usage, all output escaped

---

## Phase 3 Checklist (Week 3-4)

SOLID refactoring:

- [ ] Split FrontController → Router + Dispatcher
- [ ] Split UiComponentRenderer → Grid/Form/Toolbar renderers
- [ ] Remove all HTML from controllers
- [ ] Refactor Media Picker (service + template)
- [ ] Measure complexity reduction

**Exit Criteria**: All classes < 200 LOC, cyclomatic complexity < 10

---

## Daily Workflow

1. **Start of day**: Pull latest, run tests
2. **During work**: Make small commits, run tests frequently
3. **End of day**: Code review, push changes
4. **Weekly**: Phase review meeting

---

## Testing Strategy

**Per Phase**:
```bash
# Before changes
./vendor/bin/pest > before.txt

# After changes
./vendor/bin/pest > after.txt

# Compare
diff before.txt after.txt
```

**Manual Testing**:
- [ ] Admin panel works
- [ ] CMS pages render
- [ ] Forms submit correctly
- [ ] No console errors

---

## Rollback Strategy

Each phase has its own branch:
```bash
git checkout -b phase-1-security
# ... make changes ...
git commit -m "Phase 1: Fix XSS vulnerability"

# If issues found:
git checkout main
git branch -D phase-1-security
```

---

## Success Metrics

### Security
- ✅ Zero XSS vulnerabilities (OWASP ZAP scan)
- ✅ 100% CSRF coverage
- ✅ Zero raw superglobal usage

### Code Quality
- ✅ Average class LOC < 150
- ✅ Cyclomatic complexity < 10
- ✅ Test coverage > 80%

### Performance
- ✅ Page load < 200ms (with cache)
- ✅ Grid render < 100ms (1000 rows)
- ✅ Layout compile < 50ms

---

## Need Help?

1. **Read the phase docs**: Each phase has detailed implementation guide
2. **Check audit.md**: Original findings and recommendations
3. **Run tests**: Tests should guide you
4. **Ask team**: Weekly review meetings

---

## Progress Tracking

Update this section as you complete phases:

- [ ] **Phase 1**: Security fixes (Week 1)
- [ ] **Phase 2**: Infrastructure (Week 2)
- [ ] **Phase 3**: SOLID refactoring (Week 3-4)
- [ ] **Phase 4**: DRY/KISS (Week 5)
- [ ] **Phase 5**: Front-end (Week 6)
- [ ] **Phase 6**: Performance (Week 7-8)

---

**Last Updated**: 2025-11-02  
**Status**: Not Started  
**Next Action**: Begin Phase 1 - Security Fixes
