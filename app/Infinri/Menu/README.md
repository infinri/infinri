# Infinri Menu Module

**Version:** 1.0.0  
**Status:** Phase 1 Complete (Core Implementation)

---

## Overview

The Menu Module provides a dynamic, database-driven navigation system that eliminates hardcoded menu links. It follows Magento patterns and integrates seamlessly with the CMS module.

---

## Features Implemented ✅

### Phase 1: Core Menu Module (COMPLETE)

**✅ Module Structure**
- Registration and module.xml configured
- Dependencies: Infinri_Core, Infinri_Cms
- Module enabled in `app/etc/config.php`

**✅ Database Schema**
- `menu` table - Menu containers (main-navigation, footer-links, etc.)
- `menu_item` table - Hierarchical menu items with support for:
  - CMS page links
  - Custom URLs
  - External links
  - Nested menus (parent/child relationships)
  - Sort ordering
- `cms_page` extended with `include_in_menu` and `menu_sort_order` columns

**✅ Models & Repositories**
- `Menu` model with full validation
- `MenuItem` model with link type constants
- `MenuRepository` - CRUD operations for menus
- `MenuItemRepository` - CRUD operations with tree support
- ResourceModels for database operations

**✅ Service Layer**
- `MenuBuilder` - Builds hierarchical menu trees
- `MenuItemResolver` - Resolves URLs based on link type
- Support for CMS pages, custom URLs, external links
- Extensible for future category links

**✅ ViewModel**
- `Navigation` ViewModel for presentation logic
- Active state detection based on current URL
- Support for multiple menus (main, footer, mobile)
- Hierarchical active state propagation

**✅ Data Patches**
- `InstallDefaultMenus` - Creates 3 default menus:
  - main-navigation
  - footer-links
  - mobile-menu
- `AddCmsPagesToMainMenu` - Populates main nav with existing CMS pages
- Dependency management between patches

---

## Database Tables

### `menu`
```sql
menu_id          SERIAL PRIMARY KEY
identifier       VARCHAR(255) UNIQUE NOT NULL
title            VARCHAR(255) NOT NULL
is_active        BOOLEAN NOT NULL DEFAULT TRUE
created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

### `menu_item`
```sql
item_id          SERIAL PRIMARY KEY
menu_id          INTEGER NOT NULL REFERENCES menu(menu_id) ON DELETE CASCADE
parent_item_id   INTEGER REFERENCES menu_item(item_id) ON DELETE CASCADE
title            VARCHAR(255) NOT NULL
link_type        VARCHAR(50) NOT NULL -- cms_page, category, custom_url, external
resource_id      INTEGER
custom_url       VARCHAR(500)
css_class        VARCHAR(255)
icon_class       VARCHAR(255)
open_in_new_tab  BOOLEAN NOT NULL DEFAULT FALSE
sort_order       INTEGER NOT NULL DEFAULT 0
is_active        BOOLEAN NOT NULL DEFAULT TRUE
created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
```

---

## Usage

### Building a Menu
```php
use Infinri\Menu\Service\MenuBuilder;

// Inject via constructor
public function __construct(private MenuBuilder $menuBuilder) {}

// Build menu tree
$menuItems = $this->menuBuilder->buildMenu('main-navigation');

// Result: Hierarchical array with resolved URLs and active states
```

### Using in ViewModel
```php
use Infinri\Menu\ViewModel\Navigation;

// Inject via constructor
public function __construct(private Navigation $navigation) {}

// Get main navigation
$nav = $this->navigation->getMainNavigation();

// Get footer links
$footer = $this->navigation->getFooterNavigation();

// Check if menu has items
if ($this->navigation->hasItems('main-navigation')) {
    // Render menu
}
```

### Menu Item Structure
```php
[
    'item_id' => 1,
    'title' => 'Home',
    'url' => '/',
    'link_type' => 'cms_page',
    'css_class' => 'nav-home',
    'icon_class' => 'fa-home',
    'active' => true,
    'children' => [
        // Nested items...
    ]
]
```

---

## Next Steps

### Phase 2: Admin Interface (Pending)
- [ ] Menu listing grid UI component
- [ ] Menu form UI component
- [ ] Menu item listing grid
- [ ] Menu item form with dynamic fields
- [ ] Drag-and-drop reordering
- [ ] Admin routes and controllers

### Phase 3: Frontend Integration (Pending)
- [ ] Update `Theme\ViewModel\Header` to use Menu ViewModel
- [ ] Remove hardcoded navigation array
- [ ] Test navigation rendering
- [ ] Add CSS for nested menus

### Phase 4: CMS Integration (Pending)
- [ ] Add "Include in Menu" checkbox to CMS page form
- [ ] Create observer for page save event
- [ ] Auto-create/update menu items

---

## Manual Data Patch Application

If data patches didn't apply automatically, run this SQL:

```sql
-- Get menu ID
SELECT menu_id FROM menu WHERE identifier = 'main-navigation';

-- If empty, insert menus:
INSERT INTO menu (identifier, title, is_active, created_at, updated_at) VALUES
('main-navigation', 'Main Navigation', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('footer-links', 'Footer Links', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
('mobile-menu', 'Mobile Menu', true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

-- Insert menu items (replace menu_id=1 with actual ID):
INSERT INTO menu_item (
    menu_id, parent_item_id, title, link_type, resource_id, 
    sort_order, is_active, created_at, updated_at
) 
SELECT 
    1, NULL, title, 'cms_page', page_id,
    (ROW_NUMBER() OVER (ORDER BY page_id)) * 10, true,
    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM cms_page 
WHERE is_active = true 
AND url_key NOT IN ('404', '500', 'maintenance');
```

---

## Architecture Benefits

✅ **SOLID Compliance** - Single responsibility, open for extension  
✅ **DRY** - No code duplication, centralized logic  
✅ **Magento Patterns** - Repositories, DataPatches, UI Components  
✅ **Future-Proof** - Ready for catalog integration  
✅ **Testable** - Clear separation of concerns

---

## Testing

Unit tests pending for:
- MenuRepository CRUD operations
- MenuItemRepository tree building
- MenuBuilder hierarchy construction
- MenuItemResolver URL resolution
- Navigation ViewModel active states

---

**Last Updated**: 2025-10-31  
**Phase 1 Completion**: 100%  
**Overall Completion**: 25% (1 of 4 phases)
