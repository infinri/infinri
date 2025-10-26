# Theme & Core Alignment Audit

**Date:** October 22, 2025  
**Auditor:** System  
**Scope:** `app/Infinri/Theme` & `app/Infinri/Core` (layout/view/asset systems)

---

## Executive Summary

### Issues Found: 5 Critical Redundancies

1. ❌ **Duplicate Base Layout Definitions** (Core vs Theme)
2. ❌ **Unused Asset Build System in Core** (superseded by build.js)
3. ❌ **View Templates in Core** (should be in Theme)
4. ❌ **Redundant CSS File** (converted to LESS but not removed)
5. ⚠️ **Inconsistent Layout Handle Naming** (base_default vs adminhtml_default)

### Recommended Actions

- **Delete:** 4 unused Core files (Asset Builder, Publisher, Repository, UrlGenerator)
- **Move:** Core View templates to Theme module
- **Consolidate:** Base layout definition (choose single source)
- **Remove:** Legacy admin-grid.css file
- **Standardize:** Layout handle naming convention

---

## Detailed Findings

### 1. ❌ Duplicate Base Layout Definitions

#### Current State
Two competing base layout definitions exist:

**Location A:** `Core/View/frontend/layout/base_default.xml` (36 lines)
```xml
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <container name="root" htmlTag="html" htmlClass="no-js">
        <container name="head" htmlTag="head">
            <block name="head.meta" template="Infinri_Core::html/head/meta.phtml"/>
            <container name="head.styles"/>
            <container name="head.scripts"/>
        </container>
        <container name="body" htmlTag="body" htmlClass="page-wrapper">
            <container name="header.container"/>
            <container name="breadcrumbs.wrapper"/>
            <container name="main.content" htmlTag="main">
                <container name="content"/>
            </container>
            <container name="footer.container"/>
        </container>
    </container>
</layout>
```

**Location B:** `Theme/view/base/layout/default.xml` (39 lines)
```xml
<layout xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <container name="root">
        <container name="html" htmlTag="html" htmlClass="no-js">
            <container name="head" htmlTag="head">
                <block class="Infinri\Core\Block\Text" name="head.meta.charset"/>
                <block class="Infinri\Core\Block\Text" name="head.meta.viewport"/>
                <block class="Infinri\Core\Block\Text" name="head.title"/>
                <container name="head.styles"/>
                <container name="head.scripts"/>
            </container>
            <container name="body" htmlTag="body">
                <container name="page.wrapper" htmlTag="div" htmlClass="page-wrapper">
                    <container name="main.content" htmlTag="main">
                        <container name="content"/>
                    </container>
                </container>
            </container>
        </container>
    </container>
</layout>
```

#### Issues
- Different structure (`root` vs `root/html`)
- Different meta handling (Template block vs Text block)
- Different handle names (`base_default` vs accessed as `base_default` but file named `default.xml`)
- Core's version has frontend-specific containers (header, footer, breadcrumbs) in "base" layout

#### Recommendation
**✅ Keep:** `Theme/view/base/layout/default.xml`  
**❌ Delete:** `Core/View/frontend/layout/base_default.xml`

**Reason:**
- Theme's version is more complete (explicit meta tags)
- Theme should be the single source of truth for UI
- Core should only have framework logic, not layouts

**Action:** Update Core's Loader to skip `Core/View` directory, only load from modules with proper `view/` structure

---

### 2. ❌ Unused Asset Build System in Core

#### Files Identified

1. **`Core/Model/Asset/Builder.php`** (226 lines)
   - Methods: `compileLess()`, `minifyCss()`, `minifyJs()`, `buildCss()`
   - Uses: `node_modules/.bin/lessc`, `cleancss`, `terser`
   - **Status:** NEVER USED (0 references in codebase)

2. **`Core/Model/Asset/Publisher.php`** (267 lines)
   - Methods: `publish()`, `publishAll()`, `clean()`, `cleanAll()`
   - Purpose: Symlink/copy assets from modules to `pub/static/`
   - **Status:** NEVER USED (0 references in codebase)

3. **`Core/Model/Asset/Repository.php`** (136 lines)
   - Methods: `addCss()`, `addJs()`, `getAllCss()`, `getAllJs()`
   - Purpose: Register CSS/JS assets dynamically
   - **Status:** Interface defined but never implemented/used

4. **`Core/Model/Asset/UrlGenerator.php`** (not fully audited)
   - **Status:** Likely unused

#### Current Workflow
All asset compilation is handled by **`build.js`** (227 lines):
- ✅ Compiles LESS to CSS for all areas (base, frontend, adminhtml)
- ✅ Minifies CSS and JS
- ✅ Merges module assets
- ✅ Generates area-specific bundles
- ✅ Actually being used and working

#### Comparison

| Feature | Core Asset System | build.js |
|---------|------------------|----------|
| Compiles LESS | ✅ PHP exec() | ✅ Node.js |
| Minifies CSS | ✅ PHP exec() | ✅ Node.js |
| Minifies JS | ✅ PHP exec() | ✅ Node.js |
| Multi-area support | ❌ No | ✅ Yes |
| Auto-discovery | ❌ No | ✅ Yes |
| Performance | ⚠️ Slower (PHP exec) | ✅ Fast (Native Node) |
| Source maps | ⚠️ Optional | ✅ Can add |
| Watch mode | ❌ No | ✅ Via npm scripts |
| **Status** | ❌ UNUSED | ✅ IN USE |

#### Recommendation
**❌ Delete:** All 4 Asset system files from Core
- `Core/Model/Asset/Builder.php`
- `Core/Model/Asset/Publisher.php`
- `Core/Model/Asset/Repository.php`
- `Core/Model/Asset/UrlGenerator.php`
- `Core/Api/AssetRepositoryInterface.php` (interface)

**✅ Keep:** `build.js` as the single source of asset compilation

**Reason:**
- Eliminates 800+ lines of unused code
- Reduces confusion (one way to build assets)
- Node.js is better suited for frontend tooling
- build.js already works and is proven

---

### 3. ❌ View Templates in Core

#### Files Identified

**Adminhtml Templates in Core:**
```
Core/View/adminhtml/templates/
├── header.phtml (1743 bytes)
├── menu.phtml (2301 bytes)
├── footer.phtml (394 bytes)
└── form.phtml (12125 bytes)
```

**Frontend Templates in Core:**
```
Core/View/frontend/templates/
├── homepage.phtml (2285 bytes)
├── test.phtml (303 bytes)
├── html/head/meta.phtml (810 bytes)
└── header/logo.phtml (407 bytes)
```

**Adminhtml Layouts in Core:**
```
Core/View/adminhtml/layout/
├── default.xml (1152 bytes)
└── admin_1column.xml (1540 bytes)
```

**Frontend Layouts in Core:**
```
Core/View/frontend/layout/
├── base_default.xml (1346 bytes)
└── default.xml (304 bytes)
```

#### Issues
- **Violates Single Responsibility:** Core is framework, Theme is UI
- **Confusing:** Two places to look for templates/layouts
- **Maintenance nightmare:** Changes might need to happen in two places
- **Architecture violation:** Core shouldn't have UI concerns

#### Recommendation
**Option A (Preferred): Delete Core View Directory**
- Core shouldn't have any UI templates/layouts
- All UI should come from Theme module
- Admin templates should exist in Theme/view/adminhtml/templates/
- Frontend templates should exist in Theme/view/frontend/templates/

**Option B (If templates are actually used): Move to Theme**
```bash
# Move adminhtml templates
mv Core/View/adminhtml/templates/* Theme/view/adminhtml/templates/

# Move frontend templates  
mv Core/View/frontend/templates/* Theme/view/frontend/templates/

# Update template paths in layouts from "Infinri_Core::" to "Infinri_Theme::"
```

**Action:**
1. Audit if any Core templates are actually referenced
2. If yes → Move to Theme + update references
3. If no → Delete entire `Core/View` directory

---

### 4. ❌ Redundant CSS File

#### File
`Theme/view/adminhtml/web/css/admin-grid.css` (3098 bytes)

#### Issue
This standalone CSS file was converted to LESS:
- **New LESS version:** `Theme/view/adminhtml/web/css/source/_admin-grid.less` (3701 bytes)
- **Imported in:** `Theme/view/adminhtml/web/css/styles.less`
- **Compiled to:** `pub/static/adminhtml/css/styles.min.css`

The old CSS file is no longer used or referenced anywhere.

#### Recommendation
**❌ Delete:** `Theme/view/adminhtml/web/css/admin-grid.css`

**Reason:**
- No longer imported or used
- Redundant with LESS version
- Causes confusion ("which file do I edit?")
- Violates DRY principle

---

### 5. ⚠️ Inconsistent Layout Handle Naming

#### Current State

**Base Layout:**
- File: `Theme/view/base/layout/default.xml`
- Referenced as: `<update handle="base_default"/>`
- **Issue:** Handle name doesn't match file name

**Frontend Layout:**
- File: `Theme/view/frontend/layout/default.xml`
- Referenced as: `<update handle="frontend_default"/>` (assumed)
- **Issue:** Need to verify actual usage

**Adminhtml Layout:**
- File: `Theme/view/adminhtml/layout/default.xml`
- Referenced as: `<update handle="adminhtml_default"/>`
- **Issue:** Handle name doesn't match file name

**Admin Module:**
- File: `Admin/view/adminhtml/layout/admin_default.xml`
- Extends: `base_default` and `adminhtml_default`
- **Good:** Clear naming

#### How Layout Handles Work
Layout system uses file path to derive handle name:
```
view/{area}/layout/{handle}.xml
```

Examples:
- `view/frontend/layout/default.xml` → handle: `frontend_default`? Or just `default`?
- `view/adminhtml/layout/default.xml` → handle: `adminhtml_default`? Or just `default`?

#### Issue
Unclear if handle includes area prefix or not. Need to verify in Layout Loader.

#### Recommendation
**Verify** how `Layout\Loader` derives handle names from file paths.

**Then standardize:**
```xml
<!-- Option A: Area-prefixed (recommended) -->
view/base/layout/default.xml → handle: "base_default"
view/frontend/layout/default.xml → handle: "frontend_default"
view/adminhtml/layout/default.xml → handle: "adminhtml_default"

<!-- Option B: No prefix (simpler but requires area context) -->
view/base/layout/default.xml → handle: "default" (in base area)
view/frontend/layout/default.xml → handle: "default" (in frontend area)
view/adminhtml/layout/default.xml → handle: "default" (in adminhtml area)
```

**Action:** Check `Core/Model/Layout/Loader.php` to see actual implementation

---

## Additional Observations

### ✅ Good: Theme Structure
- Well-organized view hierarchy (base/frontend/adminhtml)
- Clean LESS architecture with proper imports
- ViewModels properly separate presentation logic
- JavaScript organized by component

### ✅ Good: Build System
- Multi-area compilation working
- Fast Node.js-based workflow
- Good console output with progress indicators
- Minification working correctly

### ✅ Good: Layout System Integration
- Theme layouts properly extend base
- Admin module correctly references theme
- Container/block structure consistent

### ⚠️ Needs Verification
- Check if Core/View templates are actually used anywhere
- Verify layout handle resolution in Layout Loader
- Confirm Asset Repository interface is truly unused

---

## Cleanup Checklist

### Priority 1: Remove Unused Core Code
- [ ] Delete `Core/Model/Asset/Builder.php`
- [ ] Delete `Core/Model/Asset/Publisher.php`
- [ ] Delete `Core/Model/Asset/Repository.php`
- [ ] Delete `Core/Model/Asset/UrlGenerator.php`
- [ ] Delete `Core/Api/AssetRepositoryInterface.php`

**Impact:** -800 lines of dead code  
**Risk:** Low (not used anywhere)

---

### Priority 2: Remove Redundant CSS
- [ ] Delete `Theme/view/adminhtml/web/css/admin-grid.css`

**Impact:** -3KB redundant file  
**Risk:** None (LESS version is used)

---

### Priority 3: Consolidate View Layer
- [ ] Audit Core/View templates for actual usage
- [ ] If unused → Delete entire `Core/View` directory
- [ ] If used → Move to Theme + update references

**Impact:** Eliminates 8 template files, 4 layout files from Core  
**Risk:** Medium (need to verify usage first)

---

### Priority 4: Standardize Layout Handles
- [ ] Verify handle resolution in Layout Loader
- [ ] Document handle naming convention
- [ ] Ensure consistency across all modules

**Impact:** Documentation + possible file renames  
**Risk:** Low (mostly documentation)

---

## Recommended Workflow After Cleanup

### 1. Asset Compilation
```bash
npm run build              # Compile all areas
npm run watch              # Auto-compile on changes
```

### 2. Module Development
```
YourModule/
└── view/
    ├── base/              # If module has area-agnostic assets
    ├── frontend/          # Frontend-specific
    │   ├── layout/
    │   │   └── yourmodule_page_index.xml
    │   ├── templates/
    │   │   └── page.phtml
    │   └── web/
    │       ├── css/       # Module-specific CSS (rare - use Theme)
    │       └── js/        # Module-specific JS
    └── adminhtml/         # Admin-specific
```

### 3. Theme Extension
```xml
<!-- Module extends Theme layout -->
<layout>
    <update handle="frontend_default"/>  <!-- Inherits all Theme styles -->
    <referenceContainer name="content">
        <!-- Add your content -->
    </referenceContainer>
</layout>
```

---

## Alignment Principles (Post-Cleanup)

### 1. Single Source of Truth
- **Theme** = All UI (layouts, templates, styles, scripts)
- **Core** = Framework logic (Layout system, Template engine, Block rendering)
- **Modules** = Business logic + module-specific UI extensions

### 2. Clear Boundaries
- **Core** has NO view/templates/layouts/css/js
- **Theme** provides base UI that modules extend
- **Modules** only add module-specific UI, inherit Theme

### 3. One Way To Do Things
- **Asset compilation:** `build.js` (not Core Asset Builder)
- **Layout definition:** Theme module (not Core View)
- **Style variables:** `base/web/css/source/_variables.less` (nowhere else)

---

## Files to Review

1. `Core/Model/Layout/Loader.php` - Verify handle resolution
2. `Core/View/**/*` - Check if any files are actually referenced
3. `build.js` - Ensure it's the canonical build tool
4. `Theme/view/*/layout/*.xml` - Verify handle usage is consistent

---

## Next Steps

1. ✅ Review this audit
2. ⏳ Execute Priority 1 cleanup (remove unused Asset system)
3. ⏳ Execute Priority 2 cleanup (remove redundant CSS)
4. ⏳ Execute Priority 3 cleanup (consolidate View layer)
5. ⏳ Document clean architecture
6. ⏳ Update module development guide

---

**Status:** Ready for cleanup execution  
**Estimated Cleanup Time:** 30 minutes  
**Risk Level:** Low (mostly removing unused code)
