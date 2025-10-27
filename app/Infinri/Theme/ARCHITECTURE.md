# Infinri Theme Module - Architecture Documentation

## 🏗️ Overview

The Theme module follows **Magento-style inheritance architecture** with three view areas:
- **`base/`** - Universal styles, layouts, and scripts shared across all views
- **`frontend/`** - Public-facing website presentation
- **`adminhtml/`** - Admin panel presentation

## 📐 Inheritance Pattern

```
base (Universal Foundation)
  ↓
  ├─→ frontend (Public Website)
  └─→ adminhtml (Admin Panel)
```

### Key Principle
**DRY (Don't Repeat Yourself)**: Common code lives in `base/`, view-specific code extends it.

---

## 📁 Directory Structure

```
Theme/view/
├── base/                          # UNIVERSAL (shared by both views)
│   ├── layout/
│   │   ├── base_default.xml       # Root HTML structure (<html>, <head>, <body>)
│   │   └── empty.xml              # Minimal layout (popups, iframes)
│   └── web/
│       ├── css/
│       │   ├── source/            # LESS modules
│       │   │   ├── _variables.less      # Colors, fonts, spacing
│       │   │   ├── _mixins.less         # Reusable LESS functions
│       │   │   ├── _reset.less          # CSS reset
│       │   │   ├── _typography.less     # Fonts, headings, text
│       │   │   ├── _layout.less         # Grid system, containers
│       │   │   ├── _grid.less           # Responsive grid
│       │   │   ├── _buttons.less        # Button styles (all variants)
│       │   │   ├── _forms.less          # Form controls
│       │   │   ├── _components.less     # Breadcrumbs, alerts, badges
│       │   │   ├── _tables.less         # Table styling
│       │   │   ├── _modals.less         # Modal dialogs
│       │   │   └── _loading.less        # Loading indicators
│       │   └── styles.less        # Entry point (imports all source files)
│       └── js/
│           ├── app.js             # Application initialization
│           ├── utils.js           # Utility functions
│           ├── forms.js           # Form validation
│           ├── modals.js          # Modal functionality
│           ├── messages.js        # Flash message display
│           └── lazy-load.js       # Image lazy loading
│
├── frontend/                      # PUBLIC WEBSITE (extends base)
│   ├── layout/
│   │   ├── default.xml            # Extends base_default, adds header/footer
│   │   ├── 1column.xml            # Single column layout
│   │   ├── 2columns-left.xml      # Two columns, sidebar left
│   │   ├── 2columns-right.xml     # Two columns, sidebar right
│   │   └── 3columns.xml           # Three columns
│   ├── templates/
│   │   ├── components/
│   │   │   ├── loading.phtml
│   │   │   ├── messages.phtml
│   │   │   ├── modal.phtml
│   │   │   └── pagination.phtml
│   │   └── html/
│   │       ├── breadcrumb.phtml   # Breadcrumb navigation
│   │       ├── footer.phtml       # Public footer
│   │       └── header.phtml       # Public header & navigation
│   └── web/
│       ├── css/
│       │   ├── source/
│       │   │   ├── _header.less        # Public header styles
│       │   │   ├── _navigation.less    # Public nav menu
│       │   │   └── _footer.less        # Public footer styles
│       │   └── styles.less        # Imports base + frontend-specific
│       └── js/
│           ├── accordion.js       # Accordion component
│           ├── navigation.js      # Menu behavior
│           └── tabs.js            # Tab component
│
└── adminhtml/                     # ADMIN PANEL (extends base)
    ├── layout/
    │   ├── default.xml            # Extends base_default, loads admin assets
    │   └── admin_1column.xml      # Admin page structure
    ├── templates/
    │   ├── form.phtml             # UI Component form renderer
    │   └── html/
    │       ├── footer.phtml       # Admin footer
    │       ├── header.phtml       # Admin header & toolbar
    │       └── menu.phtml         # Admin sidebar menu
    └── web/
        ├── css/
        │   ├── source/
        │   │   ├── _admin-header.less      # Admin header styles
        │   │   ├── _admin-navigation.less  # Admin sidebar menu
        │   │   ├── _admin-layout.less      # Admin page structure
        │   │   ├── _admin-grid.less        # Data grid component
        │   │   ├── _admin-forms.less       # Admin form overrides
        │   │   ├── _admin-tables.less      # Admin table overrides
        │   │   └── _admin-components.less  # Admin-specific components
        │   └── styles.less        # Imports base + admin-specific
        └── js/
            └── admin.js           # Admin JavaScript
```

---

## 🎨 LESS/CSS Architecture

### Entry Points

Each view area has **one entry point**: `styles.less`

#### **1. Base (`base/web/css/styles.less`)**
```less
// Variables and Mixins (foundation)
@import 'source/_variables';
@import 'source/_mixins';

// Base Styles
@import 'source/_reset';
@import 'source/_typography';
@import 'source/_layout';
@import 'source/_grid';

// Universal Components
@import 'source/_buttons';
@import 'source/_forms';
@import 'source/_components';
@import 'source/_tables';
@import 'source/_modals';
@import 'source/_loading';
```

#### **2. Frontend (`frontend/web/css/styles.less`)**
```less
// Import ALL base styles
@import '../../../base/web/css/styles';

// Add frontend-specific
@import 'source/_header';
@import 'source/_navigation';
@import 'source/_footer';
```

#### **3. Adminhtml (`adminhtml/web/css/styles.less`)**
```less
// Import ALL base styles
@import '../../../base/web/css/styles';

// Add admin-specific
@import 'source/_admin-layout';
@import 'source/_admin-header';
@import 'source/_admin-navigation';
@import 'source/_admin-forms';
@import 'source/_admin-tables';
@import 'source/_admin-grid';
@import 'source/_admin-components';
```

### Compilation

LESS files compile to:
- **Frontend**: `/pub/static/frontend/css/styles.min.css`
- **Adminhtml**: `/pub/static/adminhtml/css/styles.min.css`

### Why This is Better Than Magento

| Feature | Magento | Infinri Theme |
|---------|---------|---------------|
| Entry points | `styles-m.less` + `styles-l.less` (mobile/desktop) | `styles.less` (responsive) |
| Complexity | High (legacy mobile/desktop split) | Low (modern responsive) |
| HTTP requests | 2 CSS files | 1 CSS file |
| Maintenance | Complex fallback chain | Simple inheritance |
| Performance | Good | Better |

---

## 🔄 Layout Inheritance

### Frontend Flow
```
CMS Page (e.g., cms_index_index.xml)
  ↓ <update handle="default"/>
frontend/layout/default.xml
  ↓ <update handle="base_default"/>
base/layout/base_default.xml
  ↓ Defines <html>, <head>, <body> structure
```

### Adminhtml Flow
```
Admin Page (e.g., cms_page_edit.xml)
  ↓ <update handle="admin_1column"/>
adminhtml/layout/admin_1column.xml
  ↓ <update handle="base_default"/>
base/layout/base_default.xml
  ↓ Defines <html>, <head>, <body> structure
```

### Key Layout Files

#### **`base/layout/base_default.xml`** (Root HTML)
```xml
<layout>
    <container name="root">
        <container name="html" htmlTag="html" htmlClass="no-js">
            <container name="head" htmlTag="head">
                <!-- Meta tags, CSS, JS -->
            </container>
            <container name="body" htmlTag="body">
                <container name="page.wrapper" htmlTag="div" htmlClass="page-wrapper">
                    <container name="header.container"/>
                    <container name="breadcrumbs.wrapper"/>
                    <container name="main.content" htmlTag="main" htmlClass="main-content">
                        <container name="content"/>
                    </container>
                    <container name="footer.container"/>
                </container>
            </container>
        </container>
    </container>
</layout>
```

#### **`frontend/layout/default.xml`** (Adds Public Header/Footer)
```xml
<layout>
    <update handle="base_default"/>
    
    <!-- Load CSS/JS -->
    <referenceContainer name="head.styles">
        <block class="Infinri\Core\Block\Css" name="theme.styles">
            <arguments>
                <argument name="href" xsi:type="string">/static/frontend/css/styles.min.css</argument>
            </arguments>
        </block>
    </referenceContainer>
    
    <!-- Add header -->
    <referenceContainer name="header.container">
        <block class="Infinri\Core\Block\Template" 
               name="header" 
               template="Infinri_Theme::html/header.phtml"/>
    </referenceContainer>
    
    <!-- Add footer -->
    <referenceContainer name="footer.container">
        <block class="Infinri\Core\Block\Template" 
               name="footer" 
               template="Infinri_Theme::html/footer.phtml"/>
    </referenceContainer>
</layout>
```

#### **`adminhtml/layout/default.xml`** (Loads Admin Assets)
```xml
<layout>
    <update handle="base_default"/>
    
    <!-- Load admin CSS/JS -->
    <referenceContainer name="head.styles">
        <block class="Infinri\Core\Block\Css" name="theme.admin.styles">
            <arguments>
                <argument name="href" xsi:type="string">/static/adminhtml/css/styles.min.css</argument>
            </arguments>
        </block>
    </referenceContainer>
</layout>
```

---

## 📦 JavaScript Architecture

### Base JavaScript (Universal)
Located in `base/web/js/`:
- **`app.js`** - Application initialization, event delegation
- **`utils.js`** - Utility functions (debounce, throttle, etc.)
- **`forms.js`** - Form validation logic
- **`modals.js`** - Modal dialog functionality
- **`messages.js`** - Flash message display
- **`lazy-load.js`** - Image lazy loading

### Frontend JavaScript (View-Specific)
Located in `frontend/web/js/`:
- **`accordion.js`** - Accordion UI component
- **`navigation.js`** - Mobile menu behavior
- **`tabs.js`** - Tab component

### Adminhtml JavaScript (View-Specific)
Located in `adminhtml/web/js/`:
- **`admin.js`** - Admin-specific functionality

---

## 🎯 HTML Class Naming Convention

### Base Classes (Universal)
```css
/* Layout */
.container, .page-wrapper, .main-content

/* Buttons */
.btn, .btn-primary, .btn-secondary, .btn-success, .btn-danger

/* Forms */
.form-control, .form-group, .form-label, .form-text

/* Components */
.breadcrumbs, .breadcrumb-item, .modal, .alert, .badge

/* Tables */
.table, .table-striped, .table-hover

/* Loading */
.loading-overlay, .spinner
```

### Frontend Classes (Public Website)
```css
/* Header */
.header, .header-logo, .header-navigation, .header-search

/* Navigation */
.nav-menu, .nav-item, .nav-link, .nav-toggle

/* Footer */
.footer, .footer-content, .footer-links
```

### Adminhtml Classes (Admin Panel)
```css
/* Admin Layout */
.admin-wrapper, .admin-main, .admin-content

/* Admin Header */
.admin-header, .admin-logo, .admin-user-menu, .user-trigger

/* Admin Navigation */
.admin-navigation, .admin-menu, .admin-menu-item, .admin-submenu

/* Admin Components */
.admin-grid, .admin-form, .admin-section
```

---

## 🚀 Best Practices

### Adding New Styles

#### ✅ **DO**: Add to `base/` if universal
```less
// base/web/css/source/_components.less
.new-universal-component {
    // Used by both frontend and admin
}
```

#### ✅ **DO**: Add to view-specific if unique
```less
// frontend/web/css/source/_header.less
.public-header-banner {
    // Only used on public website
}
```

#### ❌ **DON'T**: Duplicate code
```less
// ❌ BAD: Same button in both frontend and admin LESS
// frontend/web/css/source/_buttons.less
.btn-special { }

// adminhtml/web/css/source/_admin-buttons.less  
.btn-special { }  // DUPLICATE!

// ✅ GOOD: Define once in base
// base/web/css/source/_buttons.less
.btn-special { }
```

### Adding New JavaScript

#### ✅ **DO**: Add to `base/web/js/` if universal
```javascript
// base/web/js/utils.js
export function universalFunction() {
    // Can be used anywhere
}
```

#### ✅ **DO**: Add to view-specific if unique
```javascript
// frontend/web/js/navigation.js
export function mobileMenuToggle() {
    // Only for public mobile menu
}
```

### Modifying Layouts

#### ✅ **DO**: Extend `base_default.xml`
```xml
<!-- frontend/layout/my_custom_layout.xml -->
<layout>
    <update handle="base_default"/>
    <!-- Add your customizations -->
</layout>
```

#### ❌ **DON'T**: Duplicate HTML structure
```xml
<!-- ❌ BAD: Redefining <html>, <body> -->
<layout>
    <container name="html" htmlTag="html">  <!-- Already in base_default! -->
</layout>
```

---

## 🔧 Development Workflow

### 1. Adding a New Component

**If used by both frontend and admin:**
1. Create `base/web/css/source/_newcomponent.less`
2. Add to `base/web/css/styles.less`
3. Create `base/web/js/newcomponent.js` if needed

**If view-specific:**
1. Create in `{view}/web/css/source/_newcomponent.less`
2. Add to `{view}/web/css/styles.less`
3. Create JS in `{view}/web/js/newcomponent.js` if needed

### 2. Compiling Assets

```bash
# Compile LESS to CSS (automated)
php bin/console assets:compile

# Output locations:
# - pub/static/frontend/css/styles.min.css
# - pub/static/adminhtml/css/styles.min.css
```

### 3. Testing Changes

```bash
# Run tests
php vendor/bin/phpunit

# Check for LESS errors
lessc --lint base/web/css/styles.less
```

---

## 📚 Related Documentation

- **Core Module**: `/app/Infinri/Core/README.md`
- **CMS Module**: `/app/Infinri/Cms/README.md`
- **Layout System**: `/app/Infinri/Core/Model/Layout/README.md`

---

## ✅ Architecture Validation Checklist

- [ ] All universal styles in `base/web/css/source/`
- [ ] No duplicate LESS files across views
- [ ] Each view extends `base_default.xml`
- [ ] CSS compiles without errors
- [ ] HTML classes match LESS definitions
- [ ] JavaScript modules properly organized
- [ ] No hardcoded paths (use relative imports)
- [ ] All tests passing

---

**Last Updated**: 2025-10-26  
**Architecture Version**: 2.0 (Fully Refactored)
