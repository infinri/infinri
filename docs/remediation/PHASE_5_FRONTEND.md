# Phase 5: Front-End Improvements

**Timeline**: Week 6 | **Priority**: 🟣 LOW

---

## 5.1 Extract Inline Scripts

### Problem
Inline `<script>` tags violate Content Security Policy and make code hard to maintain.

### Audit

**Find all inline scripts**:
```bash
grep -rn "<script>" app/ --include="*.phtml"
```

**Known Issues**:
1. `Menu/view/adminhtml/templates/form/field/checkboxset-with-sortorder.phtml` - Row highlighting JS
2. Any other inline event handlers (`onclick`, `onchange`, etc.)

### Implementation

**Step 1: Extract Menu Form Script**

**BEFORE** (`checkboxset-with-sortorder.phtml`):
```php
<script>
document.querySelectorAll('.menu-item-row input[type="checkbox"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        if (this.checked) {
            this.closest('.menu-item-row').classList.add('selected');
        } else {
            this.closest('.menu-item-row').classList.remove('selected');
        }
    });
});
</script>
```

**AFTER** (template):
```php
<!-- Remove inline script, add data attribute -->
<div class="menu-item-row" data-menu-item>
    <!-- ... -->
</div>
```

**Create JS file**:
```javascript
// app/Infinri/Menu/view/adminhtml/web/js/menu-form.js

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('[data-menu-item]');
        
        rows.forEach(function(row) {
            const checkbox = row.querySelector('input[type="checkbox"]');
            
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    row.classList.toggle('selected', this.checked);
                });
            }
        });
    });
})();
```

**Load via layout XML**:
```xml
<!-- app/Infinri/Menu/view/adminhtml/layout/menu_adminhtml_menu_edit.xml -->
<page>
    <head>
        <script src="Infinri_Menu::js/menu-form.js" defer="true"/>
    </head>
</page>
```

**Step 2: Remove Inline Event Handlers**

**BEFORE**:
```html
<button onclick="deleteItem(123)">Delete</button>
```

**AFTER**:
```html
<button class="delete-btn" data-item-id="123">Delete</button>

<script src="js/delete-handler.js" defer></script>
```

```javascript
// js/delete-handler.js
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const itemId = this.dataset.itemId;
        deleteItem(itemId);
    });
});
```

**Step 3: Add Content Security Policy**

```php
// app/Infinri/Core/App/Middleware/SecurityHeaders.php
namespace Infinri\Core\App\Middleware;

class SecurityHeaders
{
    public function process(Response $response): Response
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'", // Allow inline CSS for now
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
        ];
        
        $response->setHeader('Content-Security-Policy', implode('; ', $csp));
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('X-XSS-Protection', '1; mode=block');
        
        return $response;
    }
}
```

---

## 5.2 Fix Asset Management

### Problem
- Login form has hardcoded `<link>` tag (workaround for layout issue)
- Inconsistent asset loading
- No cache busting

### Implementation

**Step 1: Fix Login Form Layout Issue**

**Investigate why layout blocks weren't rendering**:
```bash
# Check layout XML
cat app/Infinri/Admin/view/adminhtml/layout/admin_auth_login.xml

# Check if it extends proper base
# Should have: <update handle="admin_default"/>
```

**Root Cause**: Login layout doesn't load base layout.

**Fix**:
```xml
<!-- app/Infinri/Admin/view/adminhtml/layout/admin_auth_login.xml -->
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <update handle="admin_empty"/>  <!-- Use empty admin layout, not base_default -->
    
    <head>
        <css src="Infinri_Admin::css/login.css"/>
    </head>
    
    <body>
        <referenceContainer name="content">
            <block class="Infinri\Admin\Block\Auth\Login" 
                   name="admin.auth.login" 
                   template="Infinri_Admin::auth/login.phtml"/>
        </referenceContainer>
    </body>
</page>
```

**Remove hardcoded link from template**:
```php
<!-- BEFORE -->
<link rel="stylesheet" href="/static/adminhtml/css/compiled/login.css">

<!-- AFTER -->
<!-- Removed - loaded via layout XML -->
```

**Step 2: Create AssetManager Service**

```php
// app/Infinri/Core/View/AssetManager.php
namespace Infinri\Core\View;

use Infinri\Core\Model\Config\ScopeConfig;

class AssetManager
{
    private array $versionCache = [];
    
    public function __construct(
        private ScopeConfig $config
    ) {}
    
    public function getAssetUrl(string $path, string $area = 'frontend'): string
    {
        $baseUrl = $this->config->getValue('web/unsecure/base_url');
        $version = $this->getVersion($path);
        
        return rtrim($baseUrl, '/') . "/static/{$area}/{$path}?v={$version}";
    }
    
    public function getCssUrl(string $path, string $area = 'frontend'): string
    {
        return $this->getAssetUrl("css/{$path}", $area);
    }
    
    public function getJsUrl(string $path, string $area = 'frontend'): string
    {
        return $this->getAssetUrl("js/{$path}", $area);
    }
    
    private function getVersion(string $path): string
    {
        if (isset($this->versionCache[$path])) {
            return $this->versionCache[$path];
        }
        
        // In production: use deployment version
        // In dev: use file modification time
        $isDev = $this->config->getBool('dev/debug/enabled');
        
        if ($isDev) {
            $filePath = $this->getFilePath($path);
            $version = file_exists($filePath) ? filemtime($filePath) : time();
        } else {
            // Use deployment ID from config
            $version = $this->config->getValue('dev/static/version') ?? '1.0.0';
        }
        
        $this->versionCache[$path] = (string)$version;
        return $this->versionCache[$path];
    }
    
    private function getFilePath(string $path): string
    {
        return __DIR__ . "/../../../../pub/static/{$path}";
    }
}
```

**Step 3: Update Layout XML to Use AssetManager**

```xml
<!-- app/Infinri/Theme/view/frontend/layout/default.xml -->
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <head>
        <!-- Use module syntax, AssetManager resolves to full URL -->
        <css src="Infinri_Theme::css/styles.min.css" media="all"/>
        <script src="Infinri_Theme::js/scripts.min.js" defer="true"/>
    </head>
</page>
```

**Step 4: Update Builder to Use AssetManager**

```php
// app/Infinri/Core/Model/Layout/Builder.php

private function processCss(SimpleXMLElement $node): void
{
    $src = (string)$node['src'];
    $media = (string)($node['media'] ?? 'all');
    
    // Convert module syntax: Infinri_Theme::css/styles.css
    $url = $this->assetManager->resolveAssetUrl($src, $this->area);
    
    $this->headAssets[] = [
        'type' => 'css',
        'src' => $url,
        'media' => $media
    ];
}
```

---

## 5.3 Asset Versioning & Optimization

### Problem
- No cache busting
- Assets not minified
- No CDN support

### Implementation

**Step 1: Build Process**

```javascript
// build.js
const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const CleanCSS = require('clean-css');

async function buildAssets() {
    // Minify JavaScript
    const jsFiles = [
        'app/Infinri/Theme/view/frontend/web/js/main.js',
        // Add more...
    ];
    
    let jsBundle = '';
    for (const file of jsFiles) {
        jsBundle += fs.readFileSync(file, 'utf8') + '\n';
    }
    
    const minifiedJs = await minify(jsBundle);
    fs.writeFileSync('pub/static/frontend/js/scripts.min.js', minifiedJs.code);
    
    // Minify CSS
    const cssFiles = [
        'app/Infinri/Theme/view/frontend/web/css/main.css',
        // Add more...
    ];
    
    let cssBundle = '';
    for (const file of cssFiles) {
        cssBundle += fs.readFileSync(file, 'utf8') + '\n';
    }
    
    const minifiedCss = new CleanCSS().minify(cssBundle);
    fs.writeFileSync('pub/static/frontend/css/styles.min.css', minifiedCss.styles);
    
    console.log('✅ Assets built successfully');
}

buildAssets().catch(console.error);
```

**Step 2: Deployment Version**

```php
// bin/console command: static:deploy
namespace Infinri\Core\Console\Command;

class StaticDeploy
{
    public function execute(): void
    {
        // Generate version hash
        $version = substr(md5(time()), 0, 8);
        
        // Save to config
        $this->config->saveValue('dev/static/version', $version);
        
        // Run build
        exec('node build.js');
        
        echo "✅ Static assets deployed (version: {$version})\n";
    }
}
```

**Step 3: CDN Support**

```php
// app/Infinri/Core/View/AssetManager.php

private function getBaseUrl(): string
{
    $cdnUrl = $this->config->getValue('web/cdn/base_url');
    
    if ($cdnUrl && !$this->config->getBool('dev/debug/enabled')) {
        return rtrim($cdnUrl, '/');
    }
    
    return rtrim($this->config->getValue('web/unsecure/base_url'), '/');
}
```

**Configuration**:
```php
// Add to core_config_data
$config->saveValue('web/cdn/base_url', 'https://cdn.example.com');
$config->saveValue('web/cdn/enabled', '1');
```

---

## 5.4 Template Cleanup & Standardization

### Problem
Inconsistent template structure and practices.

### Implementation

**Step 1: Create Template Style Guide**

```markdown
# Template Style Guide

## File Organization
- Place templates in `view/{area}/templates/`
- Match template path to block class hierarchy
- Use lowercase with hyphens: `page-view.phtml`

## Block Data Access
```php
<!-- GOOD: Via block methods -->
<?= $block->getPageTitle() ?>

<!-- BAD: Direct data access -->
<?= $block->getData('title') ?>
```

## Escaping Requirements
- Text: `$block->escapeHtml($value)`
- Attributes: `$block->escapeHtmlAttr($value)`
- URLs: `$block->escapeUrl($url)`
- JavaScript: `$block->escapeJs($value)`

## Logic in Templates
```php
<!-- GOOD: Simple presentation logic -->
<?php if ($block->hasItems()): ?>
    <!-- Display items -->
<?php endif; ?>

<!-- BAD: Business logic -->
<?php
$items = $repository->getAll();
$filtered = array_filter($items, function($item) {
    return $item->isActive() && $item->getStock() > 0;
});
?>
```

## Comments
```php
<?php
/**
 * Page Header Template
 * 
 * @var \Infinri\Theme\Block\Header $block
 */
?>
```
```

**Step 2: Template Linting**

```php
// scripts/lint-templates.php
<?php

class TemplateLinter
{
    private array $errors = [];
    
    public function lintFile(string $file): array
    {
        $content = file_get_contents($file);
        $this->errors = [];
        
        // Check for unescaped output
        $this->checkUnescapedOutput($content, $file);
        
        // Check for business logic
        $this->checkBusinessLogic($content, $file);
        
        // Check for inline styles
        $this->checkInlineStyles($content, $file);
        
        return $this->errors;
    }
    
    private function checkUnescapedOutput(string $content, string $file): void
    {
        // Find <?= without escape functions
        preg_match_all('/<?=\s*\$(?!block->escape)([^?]+)\?>/', $content, $matches);
        
        foreach ($matches[0] as $match) {
            $this->errors[] = [
                'file' => $file,
                'type' => 'unescaped_output',
                'message' => "Potentially unescaped output: {$match}"
            ];
        }
    }
    
    private function checkBusinessLogic(string $content, string $file): void
    {
        $badPatterns = [
            '->getConnection(',
            '->save(',
            '->delete(',
            'new \PDO',
        ];
        
        foreach ($badPatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $this->errors[] = [
                    'file' => $file,
                    'type' => 'business_logic',
                    'message' => "Business logic found: {$pattern}"
                ];
            }
        }
    }
    
    private function checkInlineStyles(string $content, string $file): void
    {
        if (preg_match('/<[^>]+style=/', $content)) {
            $this->errors[] = [
                'file' => $file,
                'type' => 'inline_style',
                'message' => 'Inline style attribute found'
            ];
        }
    }
}

// Run linter
$linter = new TemplateLinter();
$templateDir = __DIR__ . '/../app';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($templateDir)
);

$totalErrors = 0;
foreach ($iterator as $file) {
    if ($file->getExtension() === 'phtml') {
        $errors = $linter->lintFile($file->getPathname());
        $totalErrors += count($errors);
        
        foreach ($errors as $error) {
            echo "{$error['type']}: {$error['file']}\n  {$error['message']}\n\n";
        }
    }
}

echo "Total errors: {$totalErrors}\n";
exit($totalErrors > 0 ? 1 : 0);
```

**Step 3: Refactor Non-Compliant Templates**

**BEFORE**:
```php
<!-- page-view.phtml -->
<?php
$page = $block->getPage();
$repository = new PageRepository($connection);
$relatedPages = $repository->getRelated($page->getId());
?>

<div style="padding: 20px;">
    <h1><?= $page->getTitle() ?></h1>
    <div><?= $page->getContent() ?></div>
</div>
```

**AFTER**:
```php
<!-- page-view.phtml -->
<?php
/**
 * CMS Page View Template
 * 
 * @var \Infinri\Cms\Block\Page\View $block
 */
?>

<div class="cms-page">
    <h1><?= $block->escapeHtml($block->getPageTitle()) ?></h1>
    <div class="cms-content">
        <?= $block->getPageContent() ?>
    </div>
    
    <?php if ($block->hasRelatedPages()): ?>
        <div class="related-pages">
            <h2><?= __('Related Pages') ?></h2>
            <?php foreach ($block->getRelatedPages() as $relatedPage): ?>
                <a href="<?= $block->escapeUrl($relatedPage->getUrl()) ?>">
                    <?= $block->escapeHtml($relatedPage->getTitle()) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

```php
// app/Infinri/Cms/Block/Page/View.php
namespace Infinri\Cms\Block\Page;

class View extends \Infinri\Core\Block\Template
{
    public function getPageTitle(): string
    {
        return $this->getPage()->getTitle();
    }
    
    public function getPageContent(): string
    {
        // Content is already sanitized by Sanitizer helper
        return $this->sanitizer->sanitizeHtml($this->getPage()->getContent());
    }
    
    public function hasRelatedPages(): bool
    {
        return count($this->getRelatedPages()) > 0;
    }
    
    public function getRelatedPages(): array
    {
        // Business logic in block, not template
        return $this->pageRepository->getRelated($this->getPage()->getId());
    }
    
    private function getPage()
    {
        return $this->getData('page');
    }
}
```

---

## Verification Checklist

- [ ] All inline scripts extracted to `.js` files
- [ ] Content Security Policy headers added
- [ ] Login form layout issue fixed
- [ ] AssetManager service created
- [ ] Asset versioning implemented
- [ ] Build process for minification
- [ ] CDN support added
- [ ] Template style guide created
- [ ] Template linter implemented
- [ ] Non-compliant templates refactored
- [ ] All tests passing
- [ ] No CSP violations in browser console

---

## Files Created

**JavaScript**:
- `app/Infinri/Menu/view/adminhtml/web/js/menu-form.js`
- Additional extracted scripts

**Services**:
- `app/Infinri/Core/View/AssetManager.php`
- `app/Infinri/Core/App/Middleware/SecurityHeaders.php`

**Tools**:
- `scripts/lint-templates.php`
- `build.js` (enhanced)

**Documentation**:
- `docs/TEMPLATE_STYLE_GUIDE.md`

## Files Modified

- Templates with inline scripts (removed scripts)
- `app/Infinri/Admin/view/adminhtml/templates/auth/login.phtml` (remove hardcoded link)
- `app/Infinri/Admin/view/adminhtml/layout/admin_auth_login.xml` (fix layout)
- `app/Infinri/Core/Model/Layout/Builder.php` (use AssetManager)
- All non-compliant templates

---

## Success Criteria

- ✅ Zero inline scripts (CSP compliant)
- ✅ All assets load via layout XML
- ✅ Cache busting working
- ✅ Template linter passes
- ✅ All templates follow style guide
- ✅ No layout workarounds
- ✅ All tests passing (100%)
