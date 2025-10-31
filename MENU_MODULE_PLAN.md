# Menu Module Implementation Plan

**Version:** 1.0  
**Date:** 2025-10-31  
**Status:** Planning Phase

---

## 📋 Executive Summary

This document outlines the complete architecture and implementation plan for the **Infinri Menu Module** - a dynamic, database-driven navigation system that eliminates hardcoded menu links.

### **Current State**
- Navigation is **hardcoded** in `Theme\ViewModel\Header::getNavigation()` (lines 53-77)
- Menu items cannot be managed via admin panel
- No support for dynamic page linking
- Future catalog integration would require code changes

### **Target State**
- Fully dynamic, database-driven menu system
- Admin interface for menu management (CRUD)
- Automatic linking to CMS pages
- Hierarchical menu support (nested items)
- Extensible for future entity types (categories, products, custom links)
- Zero hardcoding - all navigation controlled via admin

---

## 🏗️ Architecture Overview

### **Design Principles**
- ✅ **SOLID compliance** - Single responsibility, open for extension
- ✅ **DRY** - Centralized menu logic, no duplication
- ✅ **Magento patterns** - Repository, DataPatches, UI Components
- ✅ **Clean separation** - Menu module owns all menu logic
- ✅ **Future-proof** - Extensible for catalog, custom entities

### **Module Structure**
```
app/Infinri/Menu/
├── Api/
│   ├── MenuItemRepositoryInterface.php
│   └── MenuRepositoryInterface.php
├── Model/
│   ├── Menu.php                        # Menu entity (container)
│   ├── MenuItem.php                    # Menu item entity
│   ├── Repository/
│   │   ├── MenuRepository.php
│   │   └── MenuItemRepository.php
│   └── ResourceModel/
│       ├── Menu.php
│       └── MenuItem.php
├── Service/
│   ├── MenuBuilder.php                 # Business logic: build menu tree
│   └── MenuItemResolver.php            # Resolve URLs for menu items
├── ViewModel/
│   └── Navigation.php                  # Presentation logic for menus
├── Controller/Adminhtml/
│   ├── Menu/                           # Menu CRUD
│   └── MenuItem/                       # Menu item CRUD
├── Ui/Component/
│   ├── Form/                           # DataProviders for forms
│   └── Listing/                        # DataProviders for grids
├── Setup/Patch/Data/
│   └── InstallDefaultMenus.php
├── etc/
│   ├── db_schema.xml                   # Database schema
│   ├── di.xml
│   ├── module.xml
│   └── adminhtml/
│       ├── routes.xml
│       └── menu.xml
└── view/
    ├── adminhtml/ui_component/
    │   ├── menu_listing.xml
    │   ├── menu_form.xml
    │   ├── menu_item_listing.xml
    │   └── menu_item_form.xml
    └── frontend/templates/
        └── navigation.phtml
```

---

## 🗄️ Database Schema

### **Table: `menu`** (Menu Containers)
```sql
CREATE TABLE menu (
    menu_id SERIAL PRIMARY KEY,
    identifier VARCHAR(255) UNIQUE NOT NULL,  -- e.g., 'main-navigation'
    title VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

**Example:** `identifier='main-navigation'`, `title='Main Navigation'`

### **Table: `menu_item`** (Hierarchical Items)
```sql
CREATE TABLE menu_item (
    item_id SERIAL PRIMARY KEY,
    menu_id INTEGER NOT NULL REFERENCES menu(menu_id) ON DELETE CASCADE,
    parent_item_id INTEGER REFERENCES menu_item(item_id) ON DELETE CASCADE,
    
    -- Item Configuration
    title VARCHAR(255) NOT NULL,
    link_type VARCHAR(50) NOT NULL,     -- 'cms_page', 'category', 'custom_url', 'external'
    resource_id INTEGER,                -- ID of linked resource
    custom_url VARCHAR(500),
    
    -- Styling & Behavior
    css_class VARCHAR(255),
    icon_class VARCHAR(255),
    open_in_new_tab BOOLEAN NOT NULL DEFAULT FALSE,
    
    -- Ordering
    sort_order INTEGER NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

### **CMS Page Integration**
Extend `cms_page` table:
```sql
ALTER TABLE cms_page ADD COLUMN include_in_menu BOOLEAN NOT NULL DEFAULT FALSE;
ALTER TABLE cms_page ADD COLUMN menu_sort_order INTEGER NOT NULL DEFAULT 0;
```

---

## 🔧 Core Components

### **1. MenuBuilder Service**
```php
namespace Infinri\Menu\Service;

class MenuBuilder
{
    public function buildMenu(string $identifier): array
    {
        // Get menu items from database
        $menuItems = $this->menuItemRepository->getByMenuIdentifier($identifier);
        
        // Build tree structure (parent/child)
        $tree = $this->buildTree($menuItems);
        
        // Resolve URLs based on link_type
        return $this->resolveUrls($tree);
    }
    
    private function resolveUrls(array $items): array
    {
        foreach ($items as &$item) {
            $item['url'] = match($item['link_type']) {
                'cms_page' => $this->resolveCmsPageUrl($item['resource_id']),
                'custom_url' => $item['custom_url'],
                'external' => $item['custom_url'],
                // Future: 'category' => $this->resolveCategoryUrl($item['resource_id']),
                default => '/'
            };
            
            if (!empty($item['children'])) {
                $item['children'] = $this->resolveUrls($item['children']);
            }
        }
        return $items;
    }
}
```

### **2. Navigation ViewModel**
```php
namespace Infinri\Menu\ViewModel;

class Navigation
{
    public function __construct(
        private MenuBuilder $menuBuilder,
        private Request $request
    ) {}
    
    public function getMainNavigation(): array
    {
        $menuItems = $this->menuBuilder->buildMenu('main-navigation');
        return $this->setActiveStates($menuItems);
    }
}
```

### **3. Update Theme Header ViewModel**
```php
// app/Infinri/Theme/ViewModel/Header.php

public function __construct(
    private \Infinri\Menu\ViewModel\Navigation $menuViewModel  // NEW
) {}

public function getNavigation(): array
{
    // REPLACE hardcoded array with dynamic menu
    return $this->menuViewModel->getMainNavigation();
}
```

---

## 📋 Implementation Phases

### **Phase 1: Core Menu Module** (Week 1)
**Tasks:**
- [ ] Create module structure (`app/Infinri/Menu/`)
- [ ] Define `db_schema.xml` (menu + menu_item tables)
- [ ] Implement Menu and MenuItem models
- [ ] Implement repositories
- [ ] Implement MenuBuilder service
- [ ] Write unit tests
- [ ] Run `setup:upgrade` to create tables

**Acceptance:** ✅ Tables created, CRUD working, tests passing

---

### **Phase 2: Data Patches** (Week 1)
**Tasks:**
- [ ] Create `InstallDefaultMenus.php` data patch
- [ ] Create "Main Navigation" and "Footer Links" menus
- [ ] Populate with existing CMS pages
- [ ] Run `setup:upgrade`

**Acceptance:** ✅ Default menus exist with CMS page links

---

### **Phase 3: Admin Interface - Menus** (Week 2)
**Tasks:**
- [ ] Create menu_listing.xml (UI Component)
- [ ] Create menu_form.xml (UI Component)
- [ ] Create Menu DataProviders
- [ ] Create Menu controllers (Index, Edit, Save, Delete)
- [ ] Add admin routes and sidebar menu item
- [ ] Test CRUD workflow

**Acceptance:** ✅ Can manage menus via admin panel

---

### **Phase 4: Admin Interface - Menu Items** (Week 2-3)
**Tasks:**
- [ ] Create menu_item_listing.xml
- [ ] Create menu_item_form.xml with dynamic fields:
  - Link type selector
  - CMS Page dropdown (populated dynamically)
  - Custom URL field
  - Parent item selector (for nested menus)
  - Sort order, CSS classes, etc.
- [ ] Create MenuItem DataProviders
- [ ] Create MenuItem controllers
- [ ] Add drag-and-drop reordering
- [ ] Test full CRUD workflow

**Acceptance:** ✅ Can manage menu items, nest items, reorder

---

### **Phase 5: CMS Page Integration** (Week 3)
**Tasks:**
- [ ] Extend `cms_page` table with menu columns
- [ ] Update CMS page form with "Include in Menu" checkbox
- [ ] Create observer for page save event
  - Auto-create/update menu item when checked
  - Remove menu item when unchecked
- [ ] Test workflow

**Acceptance:** ✅ CMS pages can be added to menu via checkbox

---

### **Phase 6: Frontend Integration** (Week 3)
**Tasks:**
- [ ] Create Menu\ViewModel\Navigation
- [ ] Update Theme\ViewModel\Header to use Menu ViewModel
- [ ] Test navigation on frontend
- [ ] Add CSS for nested menus (if used)
- [ ] Verify active states work correctly

**Acceptance:** ✅ Frontend navigation loads from database

---

### **Phase 7: Testing & Documentation** (Week 4)
**Tasks:**
- [ ] Write unit tests (repositories, MenuBuilder)
- [ ] Write integration tests (admin workflow, frontend rendering)
- [ ] Write E2E tests
- [ ] Update documentation
- [ ] Performance testing (benchmark with 50+ items)

**Acceptance:** ✅ 90%+ test coverage, docs complete

---

## 🎯 Success Criteria

### **Functional Requirements**
- ✅ Admin can create/edit/delete menus
- ✅ Admin can create/edit/delete menu items
- ✅ Admin can reorder items via drag-and-drop
- ✅ Admin can nest menu items (multi-level)
- ✅ Admin can link to CMS pages, custom URLs, external URLs
- ✅ CMS pages auto-add to menu via checkbox
- ✅ Frontend renders from database (no hardcoded links)
- ✅ Active page highlighted
- ✅ Menu changes reflect immediately

### **Non-Functional Requirements**
- ✅ SOLID principles
- ✅ DRY - no duplication
- ✅ 90%+ test coverage
- ✅ Magento patterns
- ✅ Performance: Menu loads in < 50ms

---

## 🔮 Future Enhancements

- **Catalog Integration**: Link to categories when catalog module is added
- **Conditional Display**: Show/hide based on user roles
- **Multi-Store**: Different menus per store view
- **Mega Menus**: Rich content in dropdowns
- **Analytics**: Track click-through rates

---

## 📚 Related Documentation

- Core Module: `/app/Infinri/Core/README.md`
- CMS Module: `/app/Infinri/Cms/README.md`
- Theme Module: `/app/Infinri/Theme/ARCHITECTURE.md`

---

## Appendix: Example Menu Data

```json
{
  "menu_id": 1,
  "identifier": "main-navigation",
  "items": [
    {
      "title": "Home",
      "link_type": "cms_page",
      "resource_id": 1,
      "url": "/",
      "children": []
    },
    {
      "title": "About",
      "link_type": "cms_page",
      "resource_id": 5,
      "url": "/cms/page/view?key=about",
      "children": [
        {
          "title": "Team",
          "link_type": "cms_page",
          "resource_id": 8,
          "url": "/cms/page/view?key=team"
        }
      ]
    },
    {
      "title": "Contact",
      "link_type": "cms_page",
      "resource_id": 7,
      "url": "/cms/page/view?key=contact",
      "children": []
    }
  ]
}
```

---

**Last Updated**: 2025-10-31  
**Next Review**: After Phase 1 completion  
**Owner**: Infinri Development Team
