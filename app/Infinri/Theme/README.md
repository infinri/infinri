# Infinri Theme Module

**Version:** 0.1.0  
**License:** MIT  
**Requires:** Infinri_Core ^0.1.0

---

## Overview

The **Infinri Theme Module** is the foundational presentation layer for the entire Infinri platform. It provides the base layouts, templates, stylesheets, and JavaScript that all other modules inherit from—establishing a consistent, accessible, and performant user experience across the application.

Theme is a **single source of truth** for UI/UX. Other modules extend Theme's layouts via XML without duplicating CSS or JavaScript, maintaining a lean frontend architecture.

---

## Philosophy

### Design Strategy: Base Theme Inheritance

Unlike traditional frameworks where each module brings its own CSS/JS (leading to bloat), Infinri Theme implements a **centralized inheritance model**:

```
Theme Module (base styles & scripts)
    ↓ inherited by
├─ Admin Module (adds admin-specific UI)
├─ Customer Module (adds customer-specific UI)
├─ Content Module (adds CMS-specific UI)
└─ Custom Modules (extend via layout XML)
```

**Benefits:**
- ✅ **DRY Principle** - Styles defined once, inherited everywhere
- ✅ **Performance** - Single CSS/JS bundle (< 50KB CSS, < 30KB JS)
- ✅ **Consistency** - Unified design language
- ✅ **Maintainability** - Change once, apply everywhere
- ✅ **Modularity** - Per-page customization via layout XML

### Magento-Inspired Pattern

Theme follows Magento's proven approach:
- **Layout XML** defines page structure
- **PHTML templates** render HTML
- **LESS** provides CSS preprocessing
- **ViewModels** contain presentation logic
- **Multi-area support** (base, frontend, adminhtml)

**But simpler:**
- ❌ No RequireJS complexity → Plain JavaScript
- ❌ No UI Components → Simple PHTML + JS
- ❌ No complex theme inheritance chains → Single base theme
- ❌ No PHP LESS compilation → Fast Node.js compilation

---

## What Theme Provides

### 1. Layout XML Files

**Base structure:**
- `view/base/layout/default.xml` - Global page structure
- `view/base/layout/empty.xml` - Minimal layout (no chrome)

**Frontend layouts:**
- `view/frontend/layout/default.xml` - Frontend chrome (header/footer)
- `view/frontend/layout/1column.xml` - Single column layout
- `view/frontend/layout/2columns-left.xml` - Two columns (left sidebar)
- `view/frontend/layout/2columns-right.xml` - Two columns (right sidebar)
- `view/frontend/layout/3columns.xml` - Three columns (both sidebars)

### 2. PHTML Templates

**Base templates:**
- `templates/layout/base.phtml` - Master HTML structure (DOCTYPE, html, head, body)

**Frontend templates:**
- `templates/html/header.phtml` - Site header (logo, navigation, search)
- `templates/html/footer.phtml` - Site footer (links, copyright, social)
- `templates/html/breadcrumb.phtml` - Breadcrumb navigation
- `templates/html/pagination.phtml` - Pagination controls
- `templates/components/messages.phtml` - Flash messages (success/error/info)
- `templates/components/loading.phtml` - Loading spinner/skeleton

### 3. LESS Stylesheets

**Base styles** (`view/base/web/css/source/`):
- `_variables.less` - Colors, fonts, spacing, breakpoints, z-index
- `_mixins.less` - Reusable mixins (responsive, flexbox, typography)
- `_reset.less` - CSS reset/normalize
- `_typography.infinriless` - Font definitions and heading styles
- `_layout.less` - Layout containers and wrappers
- `_grid.less` - 12-column responsive grid system
- `_utilities.less` - Utility classes (margin, padding, display)
- `_components.less` - Base component styles

**Frontend styles** (`view/frontend/web/css/source/`):
- `_header.less` - Header and navigation styles
- `_footer.less` - Footer styles
- `_navigation.less` - Menu and navigation patterns
- `_forms.less` - Form element styles
- `_buttons.less` - Button variants
- `_cards.less` - Card component
- `_modals.less` - Modal dialog styles
- `_tables.less` - Table styles
- `_responsive.less` - Media query overrides

**Master files:**
- `view/base/web/css/styles.less` - Imports all base LESS
- `view/frontend/web/css/styles.less` - Imports base + frontend LESS

### 4. JavaScript

**Base scripts** (`view/base/web/js/`):
- `app.js` - Main application initialization
- `utils.js` - Utility functions (debounce, throttle, etc.)
- `events.js` - Simple pub/sub event bus
- `storage.js` - LocalStorage wrapper

**Frontend scripts** (`view/frontend/web/js/`):
- `navigation.js` - Mobile menu toggle, dropdown menus
- `forms.js` - Form validation and submission
- `modals.js` - Modal open/close/overlay handling
- `tabs.js` - Tab component switching
- `accordion.js` - Accordion expand/collapse
- `lazy-load.js` - Image lazy loading

### 5. ViewModels

**Core ViewModels** (`ViewModel/`):
- `Header.php` - Header data (logo, navigation, search URL)
- `Footer.php` - Footer data (links, copyright, social links)
- `Breadcrumb.php` - Breadcrumb trail generation
- `Pagination.php` - Pagination logic and URLs

---

## Architecture

### Directory Structure

```
app/Infinri/Theme/
├── ViewModel/                      # Presentation logic
│   ├── Header.php
│   ├── Footer.php
│   ├── Breadcrumb.php
│   └── Pagination.php
│
├── etc/
│   ├── module.xml                  # Module definition
│   ├── config.xml                  # Default theme settings
│   └── di.xml                      # DI configuration (if needed)
│
├── view/
│   ├── base/
│   │   ├── layout/
│   │   │   ├── default.xml         # Global structure
│   │   │   └── empty.xml           # Minimal layout
│   │   │
│   │   ├── templates/
│   │   │   └── layout/
│   │   │       └── base.phtml      # Master HTML structure
│   │   │
│   │   └── web/
│   │       ├── css/
│   │       │   ├── source/         # LESS source files
│   │       │   └── styles.less     # Master import
│   │       │
│   │       ├── js/                 # Base JavaScript
│   │       └── images/             # Base images
│   │
│   └── frontend/
│       ├── layout/
│       │   ├── default.xml         # Frontend chrome
│       │   ├── 1column.xml         # Layout patterns
│       │   ├── 2columns-left.xml
│       │   └── 2columns-right.xml
│       │
│       ├── templates/
│       │   ├── html/               # Page sections
│       │   │   ├── header.phtml
│       │   │   ├── footer.phtml
│       │   │   └── breadcrumb.phtml
│       │   │
│       │   └── components/         # UI components
│       │       ├── messages.phtml
│       │       └── loading.phtml
│       │
│       └── web/
│           ├── css/
│           │   ├── source/         # Frontend LESS
│           │   └── styles.less     # Frontend master
│           │
│           ├── js/                 # Frontend JavaScript
│           └── images/             # Frontend images
│
├── registration.php                # Module registration
├── requirements.txt                # Implementation checklist
├── DEPENDENCIES.txt                # External dependencies list
└── README.md                       # This file
```

---

## Installation & Usage

### Prerequisites

Theme module requires:
- **Infinri_Core** ^0.1.0 (provides Layout, Block, Template, Asset systems)
- **Node.js** 18+ (for LESS compilation)
- **npm** packages: `less`, `clean-css-cli`, `terser`

### Installation

Theme is part of the Infinri project and installed automatically.

**Enable Theme module:**
```php
// app/etc/config.php
return [
    'modules' => [
        'Infinri_Core' => 1,
        'Infinri_Theme' => 1,
    ]
];
```

**Compile assets:**
```bash
npm run build              # Development build
npm run build:prod         # Production build (minified)
npm run watch              # Watch for changes
```

---

## For Module Developers

### Extending Theme Layouts

Other modules extend Theme's layouts via XML without duplicating CSS/JS.

**Example: Adding content to a page**

`app/Infinri/YourModule/view/frontend/layout/yourmodule_page_view.xml`:
```xml
<?xml version="1.0"?>
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <!-- Inherit 1-column layout from Theme -->
    <update handle="1column"/>
    
    <!-- Add your content block -->
    <referenceContainer name="content">
        <block class="Infinri\Core\Block\Template" 
               name="yourmodule.page" 
               template="Infinri_YourModule::page.phtml">
            <arguments>
                <argument name="view_model" xsi:type="object">
                    Infinri\YourModule\ViewModel\PageData
                </argument>
            </arguments>
        </block>
    </referenceContainer>
</layout>
```

**Your module's template inherits all Theme CSS/JS automatically!**

### Modifying Theme Layouts

**Override header block:**
```xml
<referenceBlock name="header" template="YourModule::custom_header.phtml"/>
```

**Move block to different container:**
```xml
<move element="breadcrumb" destination="header.container" before="header.logo"/>
```

**Remove block:**
```xml
<referenceBlock name="footer.newsletter" remove="true"/>
```

### Using Theme Styles

Theme provides utility classes and components you can use in your templates:

**Grid system:**
```html
<div class="row">
    <div class="col-md-6">Half width on tablet+</div>
    <div class="col-md-6">Half width on tablet+</div>
</div>
```

**Buttons:**
```html
<button class="btn btn-primary">Primary Action</button>
<button class="btn btn-secondary">Secondary Action</button>
```

**Forms:**
```html
<form class="form">
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" class="form-control">
    </div>
</form>
```

**Cards:**
```html
<div class="card">
    <div class="card-header">Card Title</div>
    <div class="card-body">Card content here</div>
    <div class="card-footer">Card footer</div>
</div>
```

### Adding Module-Specific CSS

If your module needs custom styles (keep minimal!):

**Option 1: Extend Theme LESS (recommended)**

`app/Infinri/YourModule/view/frontend/web/css/source/_custom.less`:
```less
@import "../../../../../Theme/view/base/web/css/source/_variables.less";

.your-component {
    color: @primary-color;
    padding: @spacing-md;
}
```

Register in layout XML:
```xml
<head>
    <css src="Infinri_YourModule::css/custom.css"/>
</head>
```

**Option 2: Plain CSS (if LESS not needed)**

`app/Infinri/YourModule/view/frontend/web/css/custom.css`:
```css
.your-component { /* styles */ }
```

### Adding Module-Specific JavaScript

`app/Infinri/YourModule/view/frontend/web/js/custom.js`:
```javascript
(function() {
    'use strict';
    
    // Your module-specific JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Init your components
    });
})();
```

Register in layout XML:
```xml
<head>
    <link src="Infinri_YourModule::js/custom.js" defer="true"/>
</head>
```

---

## Configuration

Theme settings can be configured via `etc/config.xml`:

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
                <code_font>ui-monospace, monospace</code_font>
            </typography>
            <colors>
                <primary>#0066cc</primary>
                <secondary>#6c757d</secondary>
                <accent>#ff6b6b</accent>
            </colors>
        </theme>
    </default>
</config>
```

**Access in templates:**
```php
$logo = $block->getConfig('theme/general/logo');
$primaryColor = $block->getConfig('theme/colors/primary');
```

---

## Design Standards

### Responsive Breakpoints

```less
@mobile: 576px;      // Small phones
@tablet: 768px;      // Tablets
@desktop: 992px;     // Desktops
@widescreen: 1200px; // Large screens
```

**Mobile-first approach:**
```less
.element {
    // Mobile styles (default)
    width: 100%;
    
    // Tablet and up
    @media (min-width: @tablet) {
        width: 50%;
    }
    
    // Desktop and up
    @media (min-width: @desktop) {
        width: 33.33%;
    }
}
```

### Accessibility (WCAG AA)

Theme is built with accessibility in mind:

✅ **Semantic HTML5** - `<header>`, `<nav>`, `<main>`, `<footer>`, `<article>`  
✅ **Proper heading hierarchy** - Single `<h1>`, logical `<h2>-<h6>`  
✅ **ARIA labels** - `aria-label`, `aria-labelledby`, `aria-expanded`  
✅ **Keyboard navigation** - Tab order, focus indicators, Escape key  
✅ **Color contrast** - 4.5:1 for text, 3:1 for UI elements  
✅ **Touch targets** - Minimum 44px for interactive elements  
✅ **Alt text** - All images have descriptive alt attributes  
✅ **Form labels** - Every input has an associated label  

### Performance Targets

Theme is optimized for speed:

- ✅ **CSS:** < 50KB (minified + gzipped)
- ✅ **JavaScript:** < 30KB (minified + gzipped)
- ✅ **Images:** WebP format with fallbacks
- ✅ **Lazy loading:** Images below the fold
- ✅ **Critical CSS:** Inline above-the-fold styles
- ✅ **Deferred JS:** Non-critical scripts load after page render

---

## Customization

### Creating a Custom Theme

To create a custom theme that extends Infinri Theme:

```
app/Infinri/CustomTheme/
├── etc/
│   └── module.xml          # Declare dependency on Infinri_Theme
│
└── view/
    └── frontend/
        ├── layout/
        │   └── default.xml # Override Theme layouts
        ├── templates/
        │   └── html/       # Override Theme templates
        └── web/
            └── css/
                └── source/ # Override Theme styles
```

In layout XML, your custom theme's files take precedence over Theme's files due to module loading order.

---

## Build Pipeline

### Development Mode

```bash
npm run watch
```

**What happens:**
- Watches `**/*.less` and `**/*.js` files
- Auto-compiles LESS → CSS on change
- Generates source maps
- Copies JavaScript to `pub/static/`
- Browser auto-refresh (if LiveReload enabled)

### Production Build

```bash
npm run build:prod
```

**What happens:**
- Compiles LESS → CSS
- Minifies CSS (via clean-css)
- Minifies JavaScript (via Terser)
- Removes source maps
- Optimizes for production deployment

### Asset Deployment

```bash
php bin/console asset:deploy --area=frontend
```

**What happens:**
- Publishes assets to `pub/static/Infinri/Theme/`
- Compiles LESS to CSS
- Minifies in production mode
- Generates asset version timestamps (cache busting)

---

## Testing

### Visual Regression Testing

Theme includes visual regression tests to catch unintended UI changes:

```bash
npm run test:visual
```

Tests against:
- Desktop viewport (1920x1080)
- Tablet viewport (768x1024)
- Mobile viewport (375x667)

### Accessibility Testing

```bash
npm run test:a11y
```

Validates:
- WCAG AA compliance
- Color contrast ratios
- Keyboard navigation
- ARIA attributes
- Semantic HTML

### Browser Compatibility

Theme is tested and supports:
- ✅ Chrome/Edge (latest 2 versions)
- ✅ Firefox (latest 2 versions)
- ✅ Safari (latest 2 versions)
- ✅ Safari iOS (latest 2 versions)
- ✅ Chrome Android (latest 2 versions)

---

## Performance

### Lighthouse Scores (Target)

- **Performance:** 95+
- **Accessibility:** 100
- **Best Practices:** 100
- **SEO:** 100

### Core Web Vitals (Target)

- **LCP (Largest Contentful Paint):** < 2.5s
- **FID (First Input Delay):** < 100ms
- **CLS (Cumulative Layout Shift):** < 0.1

---

## Contributing

When contributing to Theme module:

1. ✅ Maintain mobile-first responsive design
2. ✅ Ensure WCAG AA accessibility compliance
3. ✅ Keep CSS under 50KB budget (minified + gzipped)
4. ✅ Keep JS under 30KB budget (minified + gzipped)
5. ✅ Test on all supported browsers
6. ✅ Include visual regression tests
7. ✅ Update this README if adding new patterns/components

---

## Dependencies

### Core Framework Services Used

- `Layout System` - Load and process layout XML
- `Block System` - Render PHTML templates
- `Template Engine` - PHTML rendering with ViewModels
- `Asset System` - Compile LESS, publish CSS/JS
- `Config System` - Read theme configuration
- `URL Builder` - Generate URLs in templates

**See:** [DEPENDENCIES.txt](../../../DEPENDENCIES.txt) for complete breakdown.

---

## Versioning

Theme follows Semantic Versioning aligned with Core Framework:

- **MAJOR:** Breaking layout/template changes
- **MINOR:** New components/layouts (backward compatible)
- **PATCH:** Bug fixes, style tweaks (backward compatible)

**Current Version:** 0.1.0 (pre-release)

---

## Roadmap

### Version 0.1.0 (Current - Foundation)
- ✅ Base layouts (default, empty, 1column, 2columns)
- ✅ Core templates (header, footer, breadcrumb)
- ✅ LESS architecture (_variables, _mixins, _grid, etc.)
- ✅ Base JavaScript (navigation, forms, modals)
- ✅ ViewModels (Header, Footer, Breadcrumb, Pagination)

### Version 0.2.0 (Components)
- ⏳ Advanced UI components (tabs, accordion, carousel)
- ⏳ Form builder component
- ⏳ Data table component
- ⏳ Toast notifications
- ⏳ Off-canvas navigation

### Version 0.3.0 (Enhancement)
- ⏳ Dark mode support
- ⏳ Animation library
- ⏳ Icon system (sprite/SVG)
- ⏳ Print stylesheets
- ⏳ Email templates

### Version 1.0.0 (Stable)
- ⏳ Complete component library
- ⏳ Comprehensive documentation
- ⏳ Storybook integration
- ⏳ Design tokens system
- ⏳ Theme customization UI

---

## FAQ

### Can I override Theme templates in my module?

Yes! Use layout XML to reference Theme blocks and change their templates:

```xml
<referenceBlock name="header" template="YourModule::custom_header.phtml"/>
```

### How do I add custom CSS without bloating Theme?

Keep module-specific styles in your module's `view/frontend/web/css/` directory and register via layout XML. Import Theme's `_variables.less` for consistency.

### Can I disable Theme and use my own?

Yes, but you'll need to provide all layouts, templates, and assets that Core Framework expects. Theme provides a working baseline.

### Does Theme include jQuery?

No. Theme uses vanilla JavaScript for maximum performance and minimal bundle size. If your module needs jQuery, include it yourself.

### How do I customize colors/fonts?

Override values in your module's LESS or create a custom theme that extends Theme and overrides `_variables.less`.

---

## License

Infinri Theme Module is open-source software licensed under the [MIT License](../../../LICENSE).

---

## Support

- **Documentation:** [https://docs.infinri.com/theme](https://docs.infinri.com/theme)
- **Issues:** [GitHub Issues](https://github.com/infinri/infinri/issues)
- **Discussions:** [GitHub Discussions](https://github.com/infinri/infinri/discussions)

---

**For technical implementation checklist, see [requirements.txt](requirements.txt).**  
**For dependency breakdown, see [DEPENDENCIES.txt](../../../DEPENDENCIES.txt).**
