# Phase 2: Security Infrastructure

**Timeline**: Week 2 | **Priority**: 🟡 HIGH

---

## 2.1 Request Service Abstraction

### Problem
Direct `$_GET`, `$_POST`, `$_REQUEST` usage bypasses input validation.

### Current State
```php
// BAD: Direct superglobal access
$title = $_POST['title'] ?? '';
$id = (int)$_GET['id'];
```

### Target State
```php
// GOOD: Type-safe request methods
$title = $this->request->getString('title');
$id = $this->request->getInt('id');
```

### Implementation

**Enhance Request Class**:
```php
// app/Infinri/Core/App/Request.php
namespace Infinri\Core\App;

class Request
{
    // Add type-safe getters
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->getParam($key, $default);
        return is_string($value) ? trim($value) : $default;
    }
    
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_INT) ?: $default;
    }
    
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    public function getArray(string $key, array $default = []): array
    {
        $value = $this->getParam($key, $default);
        return is_array($value) ? $value : $default;
    }
    
    public function getEmail(string $key, ?string $default = null): ?string
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ?: $default;
    }
    
    public function getUrl(string $key, ?string $default = null): ?string
    {
        $value = $this->getParam($key, $default);
        return filter_var($value, FILTER_VALIDATE_URL) ?: $default;
    }
}
```

**Find & Replace**:
```bash
# Find all superglobal usage
grep -r "\$_GET\[" app/ --include="*.php"
grep -r "\$_POST\[" app/ --include="*.php"
grep -r "\$_REQUEST\[" app/ --include="*.php"
```

**Migration Example**:$7,000 per year to pursue an undergraduate degree for up to three years

```php
// BEFORE
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = $_POST['title'] ?? '';
$isActive = !empty($_POST['is_active']);

// AFTER
$pageId = $this->request->getInt('id');
$title = $this->request->getString('title');
$isActive = $this->request->getBool('is_active');
```

---

## 2.2 Session Service Abstraction

### Problem
Direct `$_SESSION` manipulation is hard to test and insecure.

### Implementation

**Create Session Service**:
```php
// app/Infinri/Core/App/Session.php
namespace Infinri\Core\App;

class Session
{
    private bool $started = false;
    
    public function start(): void
    {
        if (!$this->started) {
            session_start([
                'cookie_httponly' => true,
                'cookie_secure' => true,  // HTTPS only
                'cookie_samesite' => 'Strict',
                'use_strict_mode' => true,
            ]);
            $this->started = true;
        }
    }
    
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }
    
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }
    
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
    
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }
    
    public function clear(): void
    {
        $_SESSION = [];
    }
    
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
    
    public function flash(string $key, $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
    
    public function getFlash(string $key)
    {
        $value = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
    
    public function isExpired(int $maxLifetime = 3600): bool
    {
        $lastActivity = $this->get('_last_activity', time());
        return (time() - $lastActivity) > $maxLifetime;
    }
    
    public function updateActivity(): void
    {
        $this->set('_last_activity', time());
    }
}
```

**Migration**:
```php
// BEFORE
$_SESSION['user_id'] = $userId;
$isLoggedIn = isset($_SESSION['user_id']);
unset($_SESSION['temp_data']);

// AFTER
$this->session->set('user_id', $userId);
$isLoggedIn = $this->session->has('user_id');
$this->session->remove('temp_data');
```

**Security Enhancements**:
```php
// app/Infinri/Core/App/Middleware/SessionSecurity.php
public function process(): void
{
    if ($this->session->isExpired()) {
        $this->session->clear();
        $this->session->regenerate();
        throw new SessionExpiredException();
    }
    
    $this->session->updateActivity();
}
```

---

## 2.3 Output Escaping Audit

### Problem
Inconsistent output escaping across templates.

### Implementation

**Create Linting Script**:
```bash
#!/bin/bash
# scripts/lint-templates.sh

echo "Finding potentially unsafe output..."
grep -rn "<?=\s*\$" app/ --include="*.phtml" | \
  grep -v "escapeHtml\|escapeHtmlAttr\|escapeUrl\|escapeJs" | \
  awk -F: '{print $1":"$2}' | \
  sort -u

echo ""
echo "Review these files for proper escaping."
```

**Escaping Helpers** (add to Block base class):
```php
// app/Infinri/Core/Block/AbstractBlock.php
protected function escapeHtml(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

protected function escapeHtmlAttr(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

protected function escapeUrl(?string $url): string
{
    return filter_var($url ?? '', FILTER_SANITIZE_URL);
}

protected function escapeJs(?string $value): string
{
    return json_encode($value ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}
```

**Template Audit Checklist**:
- [ ] All `<?= $var ?>` uses escaping
- [ ] HTML attributes use `escapeHtmlAttr()`
- [ ] URLs use `escapeUrl()`
- [ ] JavaScript data uses `escapeJs()` or `json_encode()`
- [ ] No raw database output without escaping

**Fix Examples**:
```php
<!-- BAD -->
<h1><?= $page->getTitle() ?></h1>
<a href="<?= $url ?>" title="<?= $title ?>">Link</a>
<script>var data = <?= $jsonData ?>;</script>

<!-- GOOD -->
<h1><?= $block->escapeHtml($page->getTitle()) ?></h1>
<a href="<?= $block->escapeUrl($url) ?>" title="<?= $block->escapeHtmlAttr($title) ?>">Link</a>
<script>var data = <?= $block->escapeJs($jsonData) ?>;</script>
```

---

## Verification Checklist

- [ ] Request service implemented with type-safe getters
- [ ] All `$_GET/$_POST/$_REQUEST` replaced
- [ ] Request tests passing
- [ ] Session service implemented
- [ ] All `$_SESSION` replaced
- [ ] Session security middleware active
- [ ] Template linting script created
- [ ] All templates audited
- [ ] Output escaping tests pass
- [ ] Security documentation updated

---

## Files Created

- `app/Infinri/Core/App/Session.php`
- `app/Infinri/Core/App/Middleware/SessionSecurity.php`
- `tests/Unit/Core/App/RequestTest.php`
- `tests/Unit/Core/App/SessionTest.php`
- `scripts/lint-templates.sh`

## Files Modified

- `app/Infinri/Core/App/Request.php` (enhanced)
- `app/Infinri/Core/Block/AbstractBlock.php` (add escaping)
- All controllers (use Request/Session services)
- All middleware (use Session service)
- All templates (proper escaping)

---

## Success Criteria

- ✅ Zero raw superglobal access (code scan)
- ✅ Session security hardened
- ✅ All templates properly escaped (lint scan)
- ✅ All tests passing (100%)
