# Infinri Admin Module

**Version:** 1.0.0-dev  
**Status:** In Development  
**Requires:** Infinri_Core ^1.0.0

---

## Overview

The **Infinri Admin Module** provides the foundation for the admin panel. It includes a dashboard, extensible menu system, and unified admin layout that other modules inject into.

---

## Architecture

### Menu System

The admin navigation is built from XML configuration files discovered across all enabled modules.

**Key Components:**
- **Menu Builder** - Discovers and loads menu.xml files from all modules
- **Menu Item** - Represents a single menu entry with hierarchy support
- **XML Configuration** - Each module defines its menu items in `etc/adminhtml/menu.xml`

**Menu Structure:**
```
Dashboard (root)
Content (root)
├─ Pages (child)
├─ Blocks (child)
└─ Media Manager (child)
System (root)
├─ Configuration (child)
└─ Cache Management (child)
```

---

## Menu Configuration

### Defining Menu Items

Each module can add menu items by creating `etc/adminhtml/menu.xml`:

```xml
<?xml version="1.0"?>
<config xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:noNamespaceSchemaLocation="urn:infinri:framework:Admin/etc/menu.xsd">
    <menu>
        <!-- Root menu item (no parent) -->
        <add id="Vendor_Module::section" 
             title="My Section" 
             module="Vendor_Module" 
             sortOrder="50" 
             resource="Vendor_Module::section"/>
        
        <!-- Child menu item -->
        <add id="Vendor_Module::item" 
             title="My Item" 
             module="Vendor_Module" 
             parent="Vendor_Module::section"
             sortOrder="10" 
             action="admin/mymodule/index" 
             resource="Vendor_Module::item"/>
    </menu>
</config>
```

### XML Attributes

| Attribute | Required | Description |
|-----------|----------|-------------|
| id | Yes | Unique identifier (format: `Module::identifier`) |
| title | Yes | Display text in menu |
| module | Yes | Module that owns this item |
| sortOrder | No | Display order (default: 0) |
| action | No | URL path (e.g., `admin/cms/page/index`) |
| parent | No | Parent item ID for hierarchy |
| resource | No | ACL resource for permissions (future) |

---

## Dashboard

**URL:** `/admin` or `/admin/dashboard`

The dashboard provides:
- Quick statistics (pages, blocks, media count)
- System status indicators
- Quick action buttons to common admin tasks
- Overview of recent activity

---

## Module Structure

```
app/Infinri/Admin/
├── Controller/
│   └── Dashboard/
│       └── Index.php              # Dashboard landing page
├── Model/
│   └── Menu/
│       ├── Builder.php            # Builds menu from XML files
│       └── Item.php               # Menu item representation
├── etc/
│   ├── module.xml                 # Module definition
│   ├── adminhtml/
│   │   ├── routes.xml             # Admin routing
│   │   └── menu.xml               # Admin menu items
├── registration.php
└── README.md
```

---

## Usage

### Accessing the Dashboard

```
http://localhost:8080/admin
http://localhost:8080/admin/dashboard
```

### Adding Menu Items from Your Module

1. Create `YourModule/etc/adminhtml/menu.xml`
2. Define your menu items with appropriate parent and sortOrder
3. Menu automatically appears after module is enabled

**Example - CMS Module Menu:**

```xml
<menu>
    <!-- Add to existing Content section -->
    <add id="Infinri_Cms::pages" 
         title="Pages" 
         module="Infinri_Cms" 
         parent="Infinri_Admin::content"
         sortOrder="10" 
         action="admin/cms/page/index"/>
    
    <add id="Infinri_Cms::blocks" 
         title="Blocks" 
         module="Infinri_Cms" 
         parent="Infinri_Admin::content"
         sortOrder="20" 
         action="admin/cms/block/index"/>
</menu>
```

---

## Programmatic Usage

### Building the Menu

```php
use Infinri\Admin\Model\Menu\Builder;

$builder = new Builder();
$menuTree = $builder->build();  // Returns root Item[] array

foreach ($menuTree as $item) {
    echo $item->getTitle();  // "Dashboard", "Content", "System"
    
    if ($item->hasChildren()) {
        foreach ($item->getChildren() as $child) {
            echo $child->getTitle();  // "Pages", "Blocks", etc.
        }
    }
}
```

### Menu Item Methods

```php
$item->getId();              // "Infinri_Admin::dashboard"
$item->getTitle();           // "Dashboard"
$item->getUrl();             // "/admin/dashboard"
$item->getAction();          // "admin/dashboard"
$item->getSortOrder();       // 10
$item->hasChildren();        // true/false
$item->getChildren();        // Item[]
$item->isActive($currentUrl); // true if current page
```

---

## Extensibility

### How Other Modules Inject Menu Items

1. **Create menu.xml** in `YourModule/etc/adminhtml/menu.xml`
2. **Define parent** using existing section IDs:
   - `Infinri_Admin::dashboard` - Dashboard (no children)
   - `Infinri_Admin::content` - Content section
   - `Infinri_Admin::system` - System section
3. **Set sortOrder** to control position
4. **Enable module** - Menu items appear automatically

### Menu Discovery Process

```
Builder->build()
  ↓
discoverMenuFiles() → finds menu.xml in all modules
  ↓
loadMenuFile() → parses XML and creates Item objects
  ↓
buildTree() → organizes items into hierarchy
  ↓
Returns root items with children attached
```

---

## Future Enhancements

- **ACL System** - Permission-based menu filtering
- **Admin Layout** - Unified layout XML for all admin pages
- **Breadcrumbs** - Auto-generated from menu hierarchy
- **Admin Toolbar** - Global admin actions
- **User Profile** - Admin user management
- **Notifications** - System notifications and alerts

---

## Routes

| URL | Controller | Description |
|-----|------------|-------------|
| `/admin` | `Admin/Dashboard/Index` | Redirect to dashboard |
| `/admin/dashboard` | `Admin/Dashboard/Index` | Admin dashboard landing page |

---

## Dependencies

- **Infinri_Core** - Core framework functionality
- Menu XML files from enabled modules (optional, for menu items)

---
