# Phase 1: Critical Security Fixes

**Timeline**: Week 1 | **Priority**: 🔴 CRITICAL

---

## 1.1 XSS Vulnerability - CMS Content Sanitization

### Problem
`Cms/view/frontend/templates/page/view.phtml` outputs raw HTML without sanitization:
```php
<?= $page->getContent() ?>  // VULNERABLE
```

### Solution

**Step 1**: Install HTMLPurifier
```bash
composer require ezyang/htmlpurifier
```

**Step 2**: Create Sanitizer Helper
```php
// app/Infinri/Core/Helper/Sanitizer.php
namespace Infinri\Core\Helper;

use HTMLPurifier;
use HTMLPurifier_Config;

class Sanitizer
{
    private HTMLPurifier $purifier;
    
    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href],img[src|alt],h1,h2,h3,ul,ol,li');
        $config->set('AutoFormat.AutoParagraph', true);
        $this->purifier = new HTMLPurifier($config);
    }
    
    public function sanitizeHtml(string $html): string
    {
        return $this->purifier->purify($html);
    }
}
```

**Step 3**: Update Template
```php
// app/Infinri/Cms/view/frontend/templates/page/view.phtml
<?= $block->getSanitizer()->sanitizeHtml($page->getContent()) ?>
```

**Step 4**: Add Tests
```php
// tests/Unit/Core/Helper/SanitizerTest.php
public function testRemovesXssScript()
{
    $sanitizer = new Sanitizer();
    $input = '<p>Hello</p><script>alert("XSS")</script>';
    $output = $sanitizer->sanitizeHtml($input);
    
    $this->assertStringContainsString('<p>Hello</p>', $output);
    $this->assertStringNotContainsString('<script>', $output);
}
```

---

## 1.2 CSRF Protection Audit

### Problem
Not all state-changing endpoints have CSRF validation.

### Solution

**Step 1**: Create CSRF Audit Checklist
```bash
# Find all POST handlers
grep -r "public function.*execute" app/Infinri/*/Controller/ --include="*.php" | \
  xargs grep -l "POST\|Save\|Delete\|Update"
```

**Step 2**: Verify Each Endpoint

Create checklist:
- [ ] `Cms/Controller/Adminhtml/Page/Save.php`
- [ ] `Cms/Controller/Adminhtml/Page/Delete.php`
- [ ] `Cms/Controller/Adminhtml/Block/Save.php`
- [ ] `Cms/Controller/Adminhtml/Block/Delete.php`
- [ ] `Cms/Controller/Adminhtml/Media/Upload.php`
- [ ] `Cms/Controller/Adminhtml/Media/Delete.php`
- [ ] `Menu/Controller/Adminhtml/Menu/Save.php`
- [ ] `Core/Controller/Adminhtml/Config/Save.php`

**Step 3**: Add CSRF Middleware
```php
// app/Infinri/Core/App/Middleware/CsrfValidation.php
namespace Infinri\Core\App\Middleware;

class CsrfValidation
{
    public function __construct(
        private CsrfGuard $csrfGuard,
        private Request $request
    ) {}
    
    public function process(): void
    {
        if ($this->isStateChanging()) {
            if (!$this->csrfGuard->validateToken($this->request)) {
                throw new CsrfException('Invalid CSRF token');
            }
        }
    }
    
    private function isStateChanging(): bool
    {
        return in_array($this->request->getMethod(), ['POST', 'PUT', 'DELETE', 'PATCH']);
    }
}
```

**Step 4**: Test CSRF Protection
```php
// tests/Integration/Core/Security/CsrfProtectionTest.php
public function testRejectsRequestWithoutCsrfToken()
{
    $response = $this->post('/admin/cms/page/save', ['title' => 'Test']);
    $this->assertEquals(403, $response->getStatusCode());
}
```

---

## 1.3 SQL Injection Review

### Problem
Need to verify all SQL uses parameterized queries.

### Solution

**Step 1**: Audit SQL Queries
```bash
# Find all SQL
grep -r "\$this->connection->prepare\|->query\|->exec" app/ --include="*.php" -A 3
```

**Step 2**: Verify Patterns

✅ **SAFE**:
```php
$stmt = $this->connection->prepare("SELECT * FROM cms_page WHERE id = ?");
$stmt->execute([$pageId]);
```

❌ **UNSAFE**:
```php
$sql = "SELECT * FROM cms_page WHERE id = {$pageId}";  // NEVER DO THIS
```

**Step 3**: Check Dynamic Table Names
```php
// If table names are dynamic, whitelist them
private const ALLOWED_TABLES = ['cms_page', 'cms_block', 'menu_item'];

private function getTable(string $tableName): string
{
    if (!in_array($tableName, self::ALLOWED_TABLES)) {
        throw new \InvalidArgumentException("Invalid table: {$tableName}");
    }
    return $tableName;
}
```

**Step 4**: Add SQL Injection Tests
```php
// tests/Security/SqlInjectionTest.php
public function testRejectsSqlInjectionInParam()
{
    $malicious = "1' OR '1'='1";
    
    $repository = new PageRepository($this->connection);
    $result = $repository->getById($malicious);
    
    // Should return null, not all records
    $this->assertNull($result);
}
```

---

## Verification Checklist

Before completing Phase 1:

- [ ] HTMLPurifier installed and working
- [ ] All CMS content sanitized
- [ ] XSS injection tests pass
- [ ] All POST endpoints have CSRF tokens
- [ ] CSRF middleware active
- [ ] CSRF tests pass
- [ ] All SQL queries use prepared statements
- [ ] No string concatenation in SQL
- [ ] SQL injection tests pass
- [ ] Security documentation updated
- [ ] Code review completed
- [ ] Manual penetration testing done

---

## Rollback Plan

If issues found:
1. Revert composer.json (remove HTMLPurifier)
2. Revert template changes
3. Disable CSRF middleware
4. Keep SQL audit findings for next attempt

---

## Success Criteria

- ✅ Zero XSS vulnerabilities (OWASP ZAP scan)
- ✅ 100% CSRF coverage (manual audit)
- ✅ All SQL parameterized (code review)
- ✅ All tests passing
