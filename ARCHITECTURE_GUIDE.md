# Infinri Architecture Guide

**Version:** 1.0  
**Date:** October 22, 2025  
**Status:** Production Ready

---

## Table of Contents

1. [Overview](#overview)
2. [Core Principles](#core-principles)
3. [Module Layers](#module-layers)
4. [Theme & Core Relationship](#theme--core-relationship)
5. [Layout System](#layout-system)
6. [Asset Workflow](#asset-workflow)
7. [Module Development](#module-development)
8. [Best Practices](#best-practices)

---

## Overview

Infinri follows a **modular, layered architecture** inspired by Magento 2 with improvements for simplicity and performance.

### Three-Layer Architecture

```
┌──────────────────────────────────────┐
│      APPLICATION MODULES             │  Business Logic Layer
│  (Admin, Auth, CMS, Customer, etc.)  │  • Controllers
│                                       │  • Models
├──────────────────────────────────────┤  • Services
│         THEME MODULE                  │  Presentation Layer
│    (Base, Frontend, Adminhtml)       │  • Layouts
│                                       │  • Templates
├──────────────────────────────────────┤  • Styles (LESS)
│         CORE MODULE                   │  • Scripts (JS)
│      (Framework Infrastructure)       │  Framework Layer
│                                       │  • Layout System
│                                       │  • Block System
└──────────────────────────────────────┘  • DI Container
```

---

## Core Principles

### 1. **Single Source of Truth**
Every piece of functionality has ONE canonical location:
- **UI Structure:** Theme layouts
- **Styles:** Theme LESS files
- **Variables:** `Theme/view/base/web/css/source/_variables.less`
- **Asset Build:** `build.js`
- **Layout Processing:** Core Layout System

### 2. **DRY (Don't Repeat Yourself)**
- Modules NEVER duplicate Theme styles
- Modules extend layouts, never redefine base structure
- Shared logic in Core, business logic in modules

### 3. **Separation of Concerns**
- **Core** = Framework (no business logic, minimal UI)
- **Theme** = Presentation (no business logic)
- **Modules** = Business logic + module-specific UI extensions

### 4. **Convention Over Configuration**
- Standard directory structure
- Predictable file paths
- Automatic discovery (layouts, assets, modules)

---

## Module Layers

### Core Module (Framework)

**Purpose:** Provide infrastructure services

**Contains:**
- Layout system (Loader, Builder, Processor, Merger, Renderer)
- Block system (AbstractBlock, Template, Container, Css, Js)
- Dependency Injection (DI container, plugin system)
- Routing (Router, Controller dispatcher)
- Configuration (Config loader, merger, cache)
- Event system (Event dispatcher, observers)
- Cache abstraction (PSR-6/PSR-16 implementations)
- Minimal framework admin UI (fallback only)

**Does NOT Contain:**
- Business logic
- Application layouts/templates (except minimal admin)
- Asset compilation (handled by build.js)

**Example Path:**
```
app/Infinri/Core/
├── Model/
│   ├── Layout/           # Layout processing
│   └── View/             # Template resolution
├── Block/                # Block rendering
├── Controller/           # Base controllers
└── View/                 # Minimal admin UI (fallback)
```

---

### Theme Module (Presentation)

**Purpose:** Define UI/UX for the entire application

**Contains:**
- Base layouts (HTML structure)
- Frontend layouts (frontend theme)
- Adminhtml layouts (admin theme)
- All LESS styles (variables, mixins, components)
- All JavaScript (utilities, components)
- Shared templates
- ViewModels (presentation logic)

**Does NOT Contain:**
- Business logic
- Database operations
- API calls

**Example Path:**
```
app/Infinri/Theme/
├── view/
│   ├── base/             # Shared across all areas
│   │   ├── layout/
│   │   │   └── base_default.xml
│   │   └── web/
│   │       ├── css/source/
│   │       │   ├── _variables.less  ← SINGLE SOURCE
│   │       │   ├── _mixins.less
│   │       │   ├── _reset.less
│   │       │   ├── _typography.less
│   │       │   ├── _layout.less
│   │       │   └── _grid.less
│   │       └── js/
│   │           ├── app.js
│   │           └── utils.js
│   │
│   ├── frontend/         # Frontend theme
│   │   ├── layout/
│   │   │   └── frontend_default.xml
│   │   └── web/
│   │       ├── css/source/
│   │       └── js/
│   │
│   └── adminhtml/        # Admin theme
│       ├── layout/
│       │   └── adminhtml_default.xml
│       └── web/
│           ├── css/source/
│           └── js/
│
└── ViewModel/            # Presentation logic
```

---

### Application Modules (Business Logic)

**Purpose:** Implement specific business features

**Contains:**
- Controllers (handle HTTP requests)
- Models (business entities)
- Services (business logic)
- Repositories (data access)
- Module-specific layouts (extend Theme)
- Module-specific templates
- Module-specific assets (rare - prefer Theme)

**Does NOT Contain:**
- Framework infrastructure
- Shared UI components (in Theme)
- Base styles/layouts (in Theme)

**Example Path:**
```
app/Infinri/YourModule/
├── Controller/           # HTTP handlers
├── Model/                # Business entities
├── Service/              # Business logic
├── Repository/           # Data access
├── view/
│   ├── adminhtml/
│   │   ├── layout/       # Extends Theme admin
│   │   └── templates/    # Module templates
│   └── frontend/
│       ├── layout/       # Extends Theme frontend
│       └── templates/    # Module templates
└── etc/
    ├── module.xml
    ├── di.xml
    └── routes.xml
```

---

## Theme & Core Relationship

### What Theme Does
```
Theme defines WHAT the UI looks like:
├── Structure (containers, blocks)
├── Styling (colors, typography, layout)
├── Interactivity (JavaScript behaviors)
└── Assets (CSS/JS bundles)
```

### What Core Does
```
Core defines HOW the UI is built:
├── Layout XML → Block Tree
├── Block Tree → HTML
├── Template Path Resolution
└── Block Data Injection
```

### How They Work Together

```
1. Controller requests layout handle: "admin_dashboard_index"
              ↓
2. Core Layout Loader finds XML files from all modules
              ↓
3. Core Layout Merger combines XML (respecting priorities)
              ↓
4. Core Layout Processor applies directives (references, removes)
              ↓
5. Core Layout Builder creates Block objects
              ↓
6. Core Layout Renderer calls toHtml() on root block
              ↓
7. Blocks render using Theme templates
              ↓
8. HTML output includes Theme CSS/JS (defined in layouts)
```

---

## Layout System

### Layout Handle Naming

**Convention:** `{area}_{section}_{action}`

**Examples:**
- `base_default` - Base HTML structure (all areas)
- `frontend_default` - Frontend area default
- `adminhtml_default` - Admin area default
- `admin_default` - Admin module default
- `admin_dashboard_index` - Admin dashboard index page
- `cms_page_view` - CMS page view
- `auth_adminhtml_login_index` - Admin login page

### Layout File Discovery

Core searches for layout files in this order:
```
view/adminhtml/layout/{handle}.xml  (admin area)
view/frontend/layout/{handle}.xml   (frontend area)
view/base/layout/{handle}.xml       (shared area)
```

### Layout Inheritance

```xml
<!-- Page-specific layout -->
<layout>
    <update handle="admin_default"/>  <!-- Extend admin default -->
    
    <!-- Add page-specific content -->
    <referenceContainer name="content">
        <block name="dashboard" template="..."/>
    </referenceContainer>
</layout>
```

---

## Asset Workflow

### Build Process

```bash
npm run build
```

**What Happens:**
```
1. build.js discovers all modules in app/Infinri/
              ↓
2. For each area (base, frontend, adminhtml):
   a. Find all {module}/view/{area}/web/css/styles.less
   b. Compile LESS → CSS using lessc
   c. Store in pub/static/Infinri/{module}/{area}/css/
              ↓
3. Merge all module CSS into area bundle
   → pub/static/{area}/css/styles.css
              ↓
4. Minify CSS → styles.min.css
              ↓
5. Repeat for JavaScript
   → pub/static/{area}/js/scripts.min.js
```

### How Layouts Reference Assets

```xml
<!-- Theme/view/adminhtml/layout/adminhtml_default.xml -->
<layout>
    <referenceContainer name="head.styles">
        <block class="Infinri\Core\Block\Css" name="theme.admin.styles">
            <arguments>
                <argument name="href">/static/adminhtml/css/styles.min.css</argument>
            </arguments>
        </block>
    </referenceContainer>
</layout>
```

---

## Module Development

### Creating a New Module

#### 1. Module Structure

```
app/Infinri/YourModule/
├── Controller/
│   └── Index/
│       └── IndexController.php
├── Model/
│   └── YourModel.php
├── view/
│   ├── adminhtml/
│   │   ├── layout/
│   │   │   └── yourmodule_index_index.xml
│   │   └── templates/
│   │       └── index.phtml
│   └── frontend/
│       ├── layout/
│       │   └── yourmodule_index_index.xml
│       └── templates/
│           └── index.phtml
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── routes.xml
└── registration.php
```

#### 2. Extend Theme Layouts

**Admin Page:**
```xml
<!-- view/adminhtml/layout/yourmodule_index_index.xml -->
<layout>
    <!-- Inherit admin theme (gets all styles/JS automatically) -->
    <update handle="admin_default"/>
    
    <!-- Add your content -->
    <referenceContainer name="content">
        <block class="Infinri\Core\Block\Template" 
               name="yourmodule.index" 
               template="Infinri_YourModule::index.phtml"/>
    </referenceContainer>
</layout>
```

**Frontend Page:**
```xml
<!-- view/frontend/layout/yourmodule_index_index.xml -->
<layout>
    <!-- Inherit frontend theme -->
    <update handle="frontend_default"/>
    
    <!-- Use 1-column layout -->
    <update handle="1column"/>
    
    <!-- Add your content -->
    <referenceContainer name="content">
        <block class="Infinri\Core\Block\Template" 
               name="yourmodule.index" 
               template="Infinri_YourModule::index.phtml"/>
    </referenceContainer>
</layout>
```

#### 3. Use Theme Variables (if adding custom styles)

```less
// YourModule/view/adminhtml/web/css/source/custom.less
@import '../../../../../Theme/view/base/web/css/source/_variables';

.your-component {
    color: @primary-color;      // Use Theme variable
    padding: @spacing-md;        // Use Theme variable
    font-family: @font-family-body;  // Use Theme variable
}
```

Then run: `npm run build`

---

## Best Practices

### ✅ DO

1. **Extend Theme layouts** - Never redefine base structure
2. **Use Theme variables** - For colors, spacing, fonts
3. **Keep templates simple** - Logic in ViewModels/Controllers
4. **Follow naming conventions** - Consistent file/class names
5. **Run npm build** - After changing LESS/JS files
6. **Use DI** - Inject dependencies, don't create them
7. **Write tests** - For all business logic
8. **Document public APIs** - Clear docblocks

### ❌ DON'T

1. **Don't duplicate Theme styles** - Extend, don't copy
2. **Don't hardcode values** - Use Theme variables
3. **Don't put business logic in templates** - Use ViewModels
4. **Don't edit Core files** - Extend via DI/plugins
5. **Don't skip module.xml** - Required for registration
6. **Don't use inline styles** - Use CSS classes
7. **Don't ignore errors** - Fix them immediately
8. **Don't skip code review** - Maintain quality

---

## Quick Reference

### Common Tasks

#### Change Primary Color
```less
// Edit: Theme/view/base/web/css/source/_variables.less
@primary-color: #3b82f6;  // Change this
```
Then: `npm run build`

#### Add New Admin Page
1. Create controller: `YourModule/Controller/Adminhtml/Page/IndexController.php`
2. Create layout: `YourModule/view/adminhtml/layout/yourmodule_page_index.xml`
3. Create template: `YourModule/view/adminhtml/templates/page/index.phtml`
4. Extend: `<update handle="admin_default"/>`

#### Add New Frontend Page
1. Create controller: `YourModule/Controller/Page/IndexController.php`
2. Create layout: `YourModule/view/frontend/layout/yourmodule_page_index.xml`
3. Create template: `YourModule/view/frontend/templates/page/index.phtml`
4. Extend: `<update handle="frontend_default"/>`

---

## File Locations Reference

| What | Where |
|------|-------|
| **Theme Variables** | `Theme/view/base/web/css/source/_variables.less` |
| **Theme Mixins** | `Theme/view/base/web/css/source/_mixins.less` |
| **Base Layout** | `Theme/view/base/layout/base_default.xml` |
| **Admin Layout** | `Theme/view/adminhtml/layout/adminhtml_default.xml` |
| **Frontend Layout** | `Theme/view/frontend/layout/frontend_default.xml` |
| **Asset Build** | `build.js` |
| **Compiled CSS** | `pub/static/{area}/css/styles.min.css` |
| **Compiled JS** | `pub/static/{area}/js/scripts.min.js` |
| **Module Layouts** | `{Module}/view/{area}/layout/*.xml` |
| **Module Templates** | `{Module}/view/{area}/templates/*.phtml` |
| **Layout System** | `Core/Model/Layout/` |
| **Block System** | `Core/Block/` |

---

**This architecture ensures scalability, maintainability, and developer productivity.** 🚀
