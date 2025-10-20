# Infinri CMS Module
 
**Requires:** Infinri_Core ^1.0.0

---

## Overview

The **Infinri CMS Module** provides comprehensive content management functionality for the Infinri platform. Built following Magento architecture patterns, it includes a complete admin interface for managing pages and blocks, an integrated media manager with image picker, and a widget-based page builder system.

---

## Architecture

### Content Management
- **Pages** - Database-driven page system with full CRUD operations
- **Blocks** - Reusable content components that can be embedded anywhere
- **Widgets** - Modular content components (HTML, Block Reference, Image, Video)
- **Media Manager** - Integrated image management with modal picker

### Admin Interface
- **UI Component System** - XML-based grid and form definitions
- **DataProviders** - Decoupled data fetching for grids and forms
- **Abstract Controllers** - Reusable CRUD patterns for consistency
- **Image Picker Integration** - Insert images at cursor position in content editor

### Frontend Rendering
- **Layout System** - XML-based layout composition
- **LayoutFactory** - Simplified rendering pipeline
- **Template System** - Modular .phtml templates
- **SEO Support** - Meta tags, URL keys, structured data

---

## Features

### CMS Pages
- Full CRUD operations via admin panel
- Rich text content with integrated image picker
- SEO fields (meta title, description, keywords)
- URL key management for clean URLs
- Active/inactive status toggle
- Homepage protection (page_id=1 cannot be deleted or deactivated)
- Layout system integration

### CMS Blocks
- Reusable content components
- Unique identifier-based retrieval
- Admin interface for management
- Can be embedded in pages or layouts
- Active/inactive status

### Widget System
- **HTML Widget** - Custom HTML content
- **Block Reference Widget** - Embed existing blocks
- **Image Widget** - Image display with alt text and styling
- **Video Widget** - Embed videos with controls
- Widget factory for dynamic instantiation
- Sort order support for drag-and-drop (foundation)

### Media Manager Integration
- Modal-based image picker in admin forms
- Browse existing images by folder
- Upload new images directly from picker
- Auto-insert at cursor position in content
- Organized folder structure
- Copy URL functionality

---

## Admin Interface

### Page Management

**Grid View:** `/admin/cms/page/index`
- List all pages with search and filtering
- Status badges (Enabled/Disabled)
- Action column (Edit, Delete)
- Mass actions support
- Pagination

**Edit Form:** `/admin/cms/page/edit?id={id}`
- Single form for create and edit operations
- Three fieldsets: General Information, Content, SEO
- Integrated image picker button
- Field validation (required fields marked)
- Action buttons: Save, Save & Continue, Delete, Back

**Workflow:**
1. Navigate to page grid
2. Click "Add New Page" or edit existing page
3. Fill in title, content, URL key, meta tags
4. Click "Browse Images" to insert media
5. Save or Save & Continue

### Block Management

**Grid View:** `/admin/cms/block/index`
**Edit Form:** `/admin/cms/block/edit?id={id}`

Similar interface to page management, optimized for reusable content blocks.

---

## Homepage Protection

The homepage (page_id=1) has special protections to ensure site stability:

1. **Cannot be deleted** - `PageRepository::delete()` throws exception
2. **Cannot be deactivated** - Entity-level validation prevents `is_active=false`
3. **Always loaded** - `Index/Index` controller specifically loads homepage
4. **Database constraint** - Seeded with `is_homepage=true` flag

---

## Installation

### Database Setup

```bash
php bin/console setup:upgrade
```

This command will:
- Create `cms_page`, `cms_block`, and `cms_page_widget` tables
- Apply data patches (default pages: Home, 404, 500, Maintenance)
- Set up indexes and constraints

### Verify Installation

```bash
# Check if homepage exists
psql -U your_user -d your_db -c "SELECT page_id, title, url_key, is_homepage FROM cms_page WHERE is_homepage = true;"

# Expected output:
# page_id | title | url_key | is_homepage
# 1       | Home  | home    | t
```

---

## Programmatic Usage

### Repository Interface

```php
use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Cms\Model\Repository\BlockRepository;

// Load page by ID
$page = $pageRepository->getById(5);

// Load page by URL key
$page = $pageRepository->getByUrlKey('about-us');

// Load homepage
$homepage = $pageRepository->getHomepage();

// Get all active pages
$pages = $pageRepository->getAll($activeOnly = true);

// Load block by identifier
$block = $blockRepository->getByIdentifier('welcome_message');

// Render block content in template
<?= $block->getContent() ?>
```

### Creating Content

**Via Admin Panel** (Recommended):
1. Navigate to `/admin/cms/page/index`
2. Click "Add New Page"
3. Fill form and save

**Programmatic** (for data patches/scripts):
```php
$page = new Page();
$page->setTitle('About Us');
$page->setContent('<h1>About Us</h1><p>Our story...</p>');
$page->setUrlKey('about-us');
$page->setMetaTitle('About Us - My Site');
$page->setIsActive(true);
$pageRepository->save($page);
```

---

## Routes

### Frontend

| URL | Controller | Description |
|-----|------------|-------------|
| `/` | `Cms/Index/Index` | Homepage (loads page_id=1) |
| `/cms/page/view?id=5` | `Cms/Page/View` | View page by ID |
| `/cms/page/view?key=about-us` | `Cms/Page/View` | View page by URL key |

### Admin

| URL | Controller | Description |
|-----|------------|-------------|
| `/admin/cms/page/index` | `Cms/Adminhtml/Page/Index` | Page grid |
| `/admin/cms/page/edit` | `Cms/Adminhtml/Page/Edit` | Create new page |
| `/admin/cms/page/edit?id=5` | `Cms/Adminhtml/Page/Edit` | Edit existing page |
| `/admin/cms/page/save` | `Cms/Adminhtml/Page/Save` | Save page (POST) |
| `/admin/cms/page/delete` | `Cms/Adminhtml/Page/Delete` | Delete page (POST) |
| `/admin/cms/block/*` | `Cms/Adminhtml/Block/*` | Block management (same pattern) |

---

## Database Schema

### cms_page

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| page_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| title | VARCHAR(255) | NOT NULL | Page title |
| content | TEXT | NULL | HTML content |
| url_key | VARCHAR(255) | UNIQUE, NOT NULL | URL identifier |
| meta_title | VARCHAR(255) | NULL | SEO title tag |
| meta_description | TEXT | NULL | SEO description |
| meta_keywords | TEXT | NULL | SEO keywords |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Visibility status |
| is_homepage | BOOLEAN | NOT NULL, DEFAULT FALSE | Homepage flag |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Last update timestamp |

**Indexes:** `is_active`, `is_homepage`

### cms_block

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| block_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| identifier | VARCHAR(255) | UNIQUE, NOT NULL | Unique key for loading |
| title | VARCHAR(255) | NOT NULL | Block title (admin display) |
| content | TEXT | NULL | HTML content |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Visibility status |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Last update timestamp |

**Indexes:** `is_active`

### cms_page_widget

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| widget_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| page_id | INT | FOREIGN KEY → cms_page(page_id), CASCADE | Parent page |
| widget_type | VARCHAR(50) | NOT NULL | Widget class (html, block, image, video) |
| widget_data | TEXT | NULL | JSON configuration |
| sort_order | INT | NOT NULL, DEFAULT 0 | Display order |
| is_active | BOOLEAN | NOT NULL, DEFAULT TRUE | Visibility status |
| created_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Creation timestamp |
| updated_at | TIMESTAMP | NOT NULL, DEFAULT NOW() | Last update timestamp |

**Indexes:** `page_id`, `sort_order`, `is_active`  
**Foreign Key:** `ON DELETE CASCADE` (deleting page removes its widgets)

---

## Security

### Content Escaping

Admin-created content is rendered as raw HTML to support rich text formatting. Access to the admin panel must be restricted to trusted users only.

```php
// Frontend templates
<?= $page->getContent() ?> // Raw HTML - admin controlled
```

### SQL Injection Protection

All database queries use PDO prepared statements with bound parameters:

```php
$stmt = $connection->prepare("SELECT * FROM cms_page WHERE url_key = :url_key");
$stmt->execute(['url_key' => $urlKey]);
```

### Input Validation

- URL keys validated for allowed characters (alphanumeric, dash, underscore)
- Required fields enforced at entity and database level
- Homepage protection prevents accidental deletion/deactivation

---

## Module Structure

```
app/Infinri/Cms/
├── Api/
│   ├── BlockRepositoryInterface.php
│   ├── PageRepositoryInterface.php
│   └── WidgetRepositoryInterface.php
├── Block/
│   ├── PageRenderer.php
│   └── Widget/
│       ├── AbstractWidget.php
│       ├── BlockReference.php
│       ├── Html.php
│       ├── Image.php
│       ├── Video.php
│       └── WidgetFactory.php
├── Controller/
│   ├── Adminhtml/
│   │   ├── AbstractDeleteController.php
│   │   ├── AbstractEditController.php
│   │   ├── AbstractSaveController.php
│   │   ├── Block/
│   │   │   ├── Delete.php
│   │   │   ├── Edit.php
│   │   │   ├── Index.php
│   │   │   └── Save.php
│   │   └── Page/
│   │       ├── Delete.php
│   │       ├── Edit.php
│   │       ├── Index.php
│   │       └── Save.php
│   ├── Index/
│   │   └── Index.php (Frontend homepage)
│   └── Page/
│       └── View.php (Frontend page view)
├── Helper/
│   └── Data.php
├── Model/
│   ├── AbstractContentEntity.php
│   ├── Block.php
│   ├── Page.php
│   ├── Widget.php
│   ├── Repository/
│   │   ├── AbstractContentRepository.php
│   │   ├── BlockRepository.php
│   │   ├── PageRepository.php
│   │   └── WidgetRepository.php
│   └── ResourceModel/
│       ├── Block.php
│       ├── Page.php
│       └── Widget.php
├── Setup/
│   └── Patch/
│       └── Data/
│           └── InstallDefaultCmsPages.php
├── Ui/
│   └── Component/
│       ├── Form/
│       │   ├── AbstractDataProvider.php
│       │   ├── BlockDataProvider.php
│       │   └── DataProvider.php
│       └── Listing/
│           ├── AbstractDataProvider.php
│           ├── BlockDataProvider.php
│           ├── Column/
│           │   └── PageActions.php
│           └── DataProvider.php
├── etc/
│   ├── adminhtml/
│   │   └── routes.xml
│   ├── db_schema.xml
│   ├── di.xml
│   ├── module.xml
│   └── routes.xml
├── view/
│   ├── adminhtml/
│   │   └── ui_component/
│   │       ├── cms_block_form.xml
│   │       ├── cms_block_listing.xml
│   │       ├── cms_page_form.xml
│   │       └── cms_page_listing.xml
│   └── frontend/
│       ├── layout/
│       │   ├── cms_index_index.xml
│       │   └── cms_page_view.xml
│       └── templates/
│           └── page/
│               └── view.phtml
├── README.md
└── registration.php
```
---
