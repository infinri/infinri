# THEME MODULE - IMPLEMENTATION PLAN

**Module:** Infinri_Theme  
**Dependencies:** Infinri_Core (✅ Complete)  
**Created:** October 16, 2025, 10:07  
**Estimated Duration:** 6-8 hours  
**Estimated Tests:** 80-100

---

## Executive Summary

With Core Framework complete (512 tests passing), we can now build the Theme module. Theme will consume Core services to provide:
- Layout XML files (page structure)
- PHTML templates (HTML rendering)
- ViewModels (presentation logic)
- LESS stylesheets (compiled to CSS)
- JavaScript (UI interactions)

---

## Prerequisites ✅

All Theme dependencies are met:

- ✅ **ComponentRegistrar** - Module registration
- ✅ **Layout System** - XML loading, merging, processing, rendering
- ✅ **Block System** - AbstractBlock, Template, Container, Text
- ✅ **Template Engine** - PHTML rendering with fallback chain
- ✅ **Asset System** - LESS compilation, JS minification, publishing
- ✅ **Configuration** - ScopeConfig for theme settings
- ✅ **URL Builder** - Route-based URL generation
- ✅ **Event System** - Observer pattern

**Core Framework:** 512 tests passing, 100% functional

---

## Implementation Phases

### Phase 1: Module Foundation (30 min, 5 tests)

**Priority:** CRITICAL  
**Files to Create:**

1. **`registration.php`**
```php
<?php
use Infinri\Core\Model\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Infinri_Theme',
    __DIR__
);
```

2. **`etc/module.xml`**
```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <module name="Infinri_Theme" setup_version="1.0.0">
        <sequence>
            <module name="Infinri_Core"/>
        </sequence>
    </module>
</config>
```

3. **`etc/config.xml`** - Theme default settings
```xml
<?xml version="1.0"?>
<config>
    <default>
        <theme>
            <general>
                <logo>Infinri_Theme::images/logo.svg</logo>
                <favicon>Infinri_Theme::images/favicon.ico</favicon>
                <copyright>© 2025 Infinri. All rights reserved.</copyright>
            </general>
            <layout>
                <default_layout>1column</default_layout>
                <container_width>1200px</container_width>
            </layout>
            <typography>
                <body_font>system-ui, -apple-system, sans-serif</body_font>
                <heading_font>inherit</heading_font>
            </typography>
            <colors>
                <primary>#0066cc</primary>
                <secondary>#6c757d</secondary>
            </colors>
        </theme>
    </default>
</config>
```

4. **`etc/di.xml`** (if needed for ViewModels)

**Tests:**
- Module registration
- Module sequence (depends on Core)
- Configuration loading
- Config values accessible
- Module appears in module list

---

### Phase 2: Base Layout XML (45 min, 8 tests)

**Priority:** CRITICAL  
**Files to Create:**

1. **`view/base/layout/default.xml`**
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <container name="root">
        <container name="html">
            <container name="head">
                <block class="Infinri\Core\Block\Text" name="head.title"/>
                <block class="Infinri\Core\Block\Text" name="head.meta"/>
                <block class="Infinri\Core\Block\Text" name="head.styles"/>
                <block class="Infinri\Core\Block\Text" name="head.scripts"/>
            </container>
            <container name="body">
                <container name="before.body.end"/>
            </container>
        </container>
    </container>
</layout>
```

2. **`view/base/layout/empty.xml`**
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <container name="root">
        <container name="content">
            <!-- Minimal layout, no chrome -->
        </container>
    </container>
</layout>
```

**Tests:**
- Base layout loads
- Default handle merges correctly
- Container hierarchy is correct
- Empty layout loads
- Blocks are created
- Layout cache works
- Multiple layouts can merge
- Layout inheritance works

---

### Phase 3: Frontend Layout XML (1 hour, 12 tests)

**Priority:** HIGH  
**Files to Create:**

1. **`view/frontend/layout/default.xml`**
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <update handle="base_default"/>
    
    <referenceContainer name="body">
        <block class="Infinri\Core\Block\Template" 
               name="header" 
               template="Infinri_Theme::html/header.phtml"
               before="-">
            <arguments>
                <argument name="view_model" xsi:type="object">
                    Infinri\Theme\ViewModel\Header
                </argument>
            </arguments>
        </block>
        
        <container name="main">
            <container name="content"/>
        </container>
        
        <block class="Infinri\Core\Block\Template" 
               name="footer" 
               template="Infinri_Theme::html/footer.phtml"
               after="-">
            <arguments>
                <argument name="view_model" xsi:type="object">
                    Infinri\Theme\ViewModel\Footer
                </argument>
            </arguments>
        </block>
    </referenceContainer>
    
    <referenceContainer name="head">
        <css src="Infinri_Theme::css/styles.css"/>
        <link src="Infinri_Theme::js/app.js" defer="true"/>
    </referenceContainer>
</layout>
```

2. **`view/frontend/layout/1column.xml`**
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <update handle="default"/>
    
    <referenceContainer name="main">
        <container name="content" htmlTag="main" htmlClass="container"/>
    </referenceContainer>
</layout>
```

3. **`view/frontend/layout/2columns-left.xml`**
4. **`view/frontend/layout/2columns-right.xml`**

**Tests:**
- Frontend default layout loads
- Extends base layout correctly
- Header block created
- Footer block created
- 1column layout works
- 2column layouts work
- Content container exists
- Assets registered
- ViewModels injected
- Layout handles resolve
- Multiple layout updates work
- Template paths resolve

---

### Phase 4: Core ViewModels (1 hour, 15 tests)

**Priority:** HIGH  
**Files to Create:**

1. **`ViewModel/Header.php`**
```php
<?php
namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\Url\Builder as UrlBuilder;

class Header
{
    public function __construct(
        private ScopeConfig $config,
        private UrlBuilder $urlBuilder
    ) {}
    
    public function getLogo(): string
    {
        return $this->config->getValue('theme/general/logo');
    }
    
    public function getLogoUrl(): string
    {
        return $this->urlBuilder->build('home/index/index');
    }
    
    public function getNavigation(): array
    {
        return [
            ['label' => 'Home', 'url' => $this->urlBuilder->build('home')],
            ['label' => 'About', 'url' => $this->urlBuilder->build('about')],
            ['label' => 'Contact', 'url' => $this->urlBuilder->build('contact')],
        ];
    }
    
    public function getSearchUrl(): string
    {
        return $this->urlBuilder->build('search/index/index');
    }
}
```

2. **`ViewModel/Footer.php`**
```php
<?php
namespace Infinri\Theme\ViewModel;

use Infinri\Core\Model\Config\ScopeConfig;

class Footer
{
    public function __construct(
        private ScopeConfig $config
    ) {}
    
    public function getCopyright(): string
    {
        return $this->config->getValue('theme/general/copyright');
    }
    
    public function getLinks(): array
    {
        return [
            ['label' => 'Privacy Policy', 'url' => '/privacy'],
            ['label' => 'Terms of Service', 'url' => '/terms'],
            ['label' => 'Contact Us', 'url' => '/contact'],
        ];
    }
    
    public function getSocialLinks(): array
    {
        return [
            ['platform' => 'Twitter', 'url' => 'https://twitter.com/infinri'],
            ['platform' => 'GitHub', 'url' => 'https://github.com/infinri'],
        ];
    }
}
```

3. **`ViewModel/Breadcrumb.php`**
```php
<?php
namespace Infinri\Theme\ViewModel;

class Breadcrumb
{
    private array $breadcrumbs = [];
    
    public function addCrumb(string $label, ?string $url = null): void
    {
        $this->breadcrumbs[] = [
            'label' => $label,
            'url' => $url,
        ];
    }
    
    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }
}
```

4. **`ViewModel/Pagination.php`**

**Tests for each ViewModel:**
- ViewModel instantiation via DI
- Get methods return expected types
- Config integration works
- URL builder integration works
- Data methods return correct values
- ViewModels work in templates

---

### Phase 5: Base Templates (1.5 hours, 10 tests)

**Priority:** HIGH  
**Files to Create:**

1. **`view/base/templates/layout/base.phtml`**
```php
<?php
/** @var \Infinri\Core\Block\Template $block */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $block->escapeHtml($block->getChildHtml('head.title')) ?></title>
    <?= $block->getChildHtml('head.meta') ?>
    <?= $block->getChildHtml('head.styles') ?>
</head>
<body>
    <?= $block->getChildHtml() ?>
    <?= $block->getChildHtml('before.body.end') ?>
</body>
</html>
```

2. **`view/frontend/templates/html/header.phtml`**
```php
<?php
/** @var \Infinri\Core\Block\Template $block */
/** @var \Infinri\Theme\ViewModel\Header $viewModel */
$viewModel = $block->getViewModel();
?>
<header class="header">
    <div class="container">
        <a href="<?= $block->escapeUrl($viewModel->getLogoUrl()) ?>" class="logo">
            <img src="<?= $block->escapeUrl($viewModel->getLogo()) ?>" alt="Logo">
        </a>
        
        <nav class="navigation">
            <ul>
                <?php foreach ($viewModel->getNavigation() as $item): ?>
                    <li>
                        <a href="<?= $block->escapeUrl($item['url']) ?>">
                            <?= $block->escapeHtml($item['label']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        
        <div class="search">
            <form action="<?= $block->escapeUrl($viewModel->getSearchUrl()) ?>" method="get">
                <input type="search" name="q" placeholder="Search...">
                <button type="submit">Search</button>
            </form>
        </div>
    </div>
</header>
```

3. **`view/frontend/templates/html/footer.phtml`**
4. **`view/frontend/templates/html/breadcrumb.phtml`**
5. **`view/frontend/templates/components/messages.phtml`**

**Tests:**
- Base template renders
- Header template renders with ViewModel
- Footer template renders with ViewModel
- XSS escaping works (escapeHtml, escapeUrl)
- Child HTML rendering works
- ViewModel data accessible in templates
- Template fallback chain works
- Multiple templates can render
- Template caching works
- Template resolver finds correct template

---

### Phase 6: Base LESS (1.5 hours, 12 tests)

**Priority:** HIGH  
**Files to Create:**

1. **`view/base/web/css/source/_variables.less`**
```less
// Colors
@primary-color: #0066cc;
@secondary-color: #6c757d;
@success-color: #28a745;
@danger-color: #dc3545;
@warning-color: #ffc107;
@info-color: #17a2b8;

// Typography
@font-family-base: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
@font-family-heading: inherit;
@font-family-monospace: ui-monospace, 'Courier New', monospace;

@font-size-base: 16px;
@line-height-base: 1.5;

// Spacing
@spacing-xs: 4px;
@spacing-sm: 8px;
@spacing-md: 16px;
@spacing-lg: 24px;
@spacing-xl: 32px;

// Breakpoints
@breakpoint-mobile: 576px;
@breakpoint-tablet: 768px;
@breakpoint-desktop: 992px;
@breakpoint-widescreen: 1200px;

// Container
@container-width: 1200px;

// Z-index scale
@z-index-dropdown: 1000;
@z-index-modal: 1050;
@z-index-tooltip: 1100;
```

2. **`view/base/web/css/source/_mixins.less`**
```less
// Responsive mixins
.responsive(@breakpoint, @rules) {
    @media (min-width: @breakpoint) {
        @rules();
    }
}

// Flexbox helpers
.flex-center() {
    display: flex;
    align-items: center;
    justify-content: center;
}

// Typography
.heading(@size) {
    font-family: @font-family-heading;
    font-size: @size;
    line-height: 1.2;
    font-weight: 600;
}

// Utilities
.truncate() {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
```

3. **`view/base/web/css/source/_reset.less`** - CSS normalize
4. **`view/base/web/css/source/_typography.less`** - Font styles
5. **`view/base/web/css/source/_layout.less`** - Layout containers
6. **`view/base/web/css/source/_grid.less`** - 12-column grid
7. **`view/base/web/css/source/_utilities.less`** - Utility classes

8. **`view/base/web/css/styles.less`** - Master import file
```less
@import 'source/_variables';
@import 'source/_mixins';
@import 'source/_reset';
@import 'source/_typography';
@import 'source/_layout';
@import 'source/_grid';
@import 'source/_utilities';
```

**Tests:**
- LESS files compile to CSS
- Variables are accessible
- Mixins work
- Master styles.less imports all
- Compiled CSS is minified (production)
- Source maps generated (dev)
- CSS < 50KB (budget)
- Asset publishing works
- Cache busting URLs work
- Assets load in templates
- Multiple LESS files merge
- LESS compilation errors are caught

---

### Phase 7: Frontend LESS (1 hour, 10 tests)

**Priority:** MEDIUM  
**Files to Create:**

1. **`view/frontend/web/css/source/_header.less`**
2. **`view/frontend/web/css/source/_footer.less`**
3. **`view/frontend/web/css/source/_navigation.less`**
4. **`view/frontend/web/css/source/_forms.less`**
5. **`view/frontend/web/css/source/_buttons.less`**
6. **`view/frontend/web/css/source/_cards.less`**
7. **`view/frontend/web/css/source/_responsive.less`**

8. **`view/frontend/web/css/styles.less`**
```less
@import '../../../base/web/css/styles';
@import 'source/_header';
@import 'source/_footer';
@import 'source/_navigation';
@import 'source/_forms';
@import 'source/_buttons';
@import 'source/_cards';
@import 'source/_responsive';
```

**Tests:**
- Frontend LESS extends base
- Component styles compile
- Responsive styles work
- Frontend CSS < total budget
- Area-specific styles load
- Import chain works
- No CSS conflicts
- Mobile-first approach verified
- Desktop overrides work
- Browser compatibility

---

### Phase 8: Base JavaScript (1 hour, 8 tests)

**Priority:** MEDIUM  
**Files to Create:**

1. **`view/base/web/js/app.js`**
```javascript
(function() {
    'use strict';
    
    const App = {
        init() {
            this.initModules();
            this.ready();
        },
        
        initModules() {
            // Initialize core modules
            if (window.InfinriUtils) InfinriUtils.init();
            if (window.InfinriEvents) InfinriEvents.init();
        },
        
        ready() {
            document.addEventListener('DOMContentLoaded', () => {
                console.log('Infinri App Ready');
            });
        }
    };
    
    window.InfinriApp = App;
    App.init();
})();
```

2. **`view/base/web/js/utils.js`** - Debounce, throttle, etc.
3. **`view/base/web/js/events.js`** - Pub/sub event bus
4. **`view/base/web/js/storage.js`** - LocalStorage wrapper

5. **`view/frontend/web/js/navigation.js`** - Mobile menu, dropdowns

**Tests:**
- JS files minify correctly
- App initialization works
- Utils functions work (debounce, throttle)
- Event bus pub/sub works
- Storage wrapper works
- Navigation JS works
- JS < 30KB budget
- No console errors

---

### Phase 9: Component Styles & Scripts (1.5 hours, 12 tests)

**Priority:** MEDIUM  
**Files to Create:**

1. **`view/frontend/web/css/source/_modals.less`**
2. **`view/frontend/web/css/source/_tables.less`**
3. **`view/frontend/web/js/modals.js`** - Modal open/close
4. **`view/frontend/web/js/forms.js`** - Form validation
5. **`view/frontend/web/js/tabs.js`** - Tab switching
6. **`view/frontend/web/js/accordion.js`** - Accordion
7. **`view/frontend/templates/components/modal.phtml`**
8. **`view/frontend/templates/components/pagination.phtml`**

**Tests:**
- Modal styles render
- Modal JS opens/closes
- Form validation works
- Tabs switch correctlyinfinri
- Accordion expands/collapses
- Components are accessible
- Keyboard navigation works
- Touch-friendly (44px targets)
- ARIA labels present
- WCAG AA contrast verified
- Components work without JS
- Progressive enhancement verified

---

## Test Coverage Plan

### Total Estimated Tests: 92

**By Category:**
- Module Foundation: 5 tests
- Layout XML: 20 tests (base + frontend)
- ViewModels: 15 tests
- Templates: 10 tests
- LESS Compilation: 22 tests
- JavaScript: 8 tests
- Components: 12 tests

**Test Types:**
- Unit tests: ViewModels, utility functions
- Integration tests: Layout rendering, asset compilation
- Functional tests: Full page rendering

---

## Success Criteria

### Must Have (Critical)
- ✅ Module registers and loads
- ✅ Layout XML loads and merges
- ✅ Templates render with ViewModels
- ✅ LESS compiles to CSS
- ✅ Assets publish to pub/static
- ✅ Total CSS < 50KB (minified + gzipped)
- ✅ Total JS < 30KB (minified + gzipped)

### Should Have (High Priority)
- ✅ Mobile-first responsive design
- ✅ WCAG AA accessibility
- ✅ Works without JavaScript
- ✅ 1column, 2column layouts
- ✅ Header, Footer, Breadcrumb
- ✅ Basic components (buttons, forms)

### Nice to Have (Medium Priority)
- ✅ Advanced components (modals, tabs, accordion)
- ✅ 3-column layout
- ✅ Animation/transitions
- ✅ Dark mode support

---

## Timeline Estimate

**Phase 1:** 30 min (Foundation)  
**Phase 2:** 45 min (Base Layouts)  
**Phase 3:** 1 hour (Frontend Layouts)  
**Phase 4:** 1 hour (ViewModels)  
**Phase 5:** 1.5 hours (Templates)  
**Phase 6:** 1.5 hours (Base LESS)  
**Phase 7:** 1 hour (Frontend LESS)  
**Phase 8:** 1 hour (JavaScript)  
**Phase 9:** 1.5 hours (Components)  

**Total:** ~10 hours implementation + testing

**With testing and iteration:** 12-14 hours

---

## Dependencies

### External (Already Available)
- ✅ Core Framework (512 tests passing)
- ✅ LESS compiler (npm)
- ✅ clean-css-cli (npm)
- ✅ terser (npm)

### Internal (Will Create)
- ViewModels (Phase 4)
- Templates need ViewModels
- Layouts need Templates
- Assets need compilation

---

## Risk Mitigation

### Known Risks
1. **CSS size budget** - May exceed 50KB
   - Mitigation: Component-based approach, purge unused CSS
   
2. **Browser compatibility** - Older browsers
   - Mitigation: Progressive enhancement, fallbacks
   
3. **Accessibility** - WCAG AA compliance
   - Mitigation: Test with screen readers, keyboard navigation

4. **Template complexity** - Hard to maintain
   - Mitigation: Keep templates simple, use ViewModels for logic

---

## Next Steps

1. **Start with Phase 1** - Module foundation
2. **Build incrementally** - One phase at a time
3. **Test as you go** - Don't skip tests
4. **Iterate based on feedback** - Refine as needed

---

## Notes

- Core Framework provides all infrastructure
- Theme focuses on presentation only
- No business logic in Theme
- Other modules will extend Theme
- Keep it simple and maintainable

---

*Ready to begin implementation!*
