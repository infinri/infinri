# Phase 3: SOLID Architecture Refactoring

**Timeline**: Week 3-4 | **Priority**: 🔵 MEDIUM

---

## 3.1 FrontController Refactoring (SRP)

### Problem
`Core/App/FrontController.php` (418 LOC) violates Single Responsibility Principle:
- Route parsing
- Route matching
- Controller instantiation
- Action execution
- Error handling

### Target Architecture

```
FrontController (orchestrator, ~100 LOC)
  ├─> Router (route matching)
  ├─> Dispatcher (controller execution)
  └─> ResultProcessor (response handling)
```

### Implementation

**Step 1: Extract Router**
```php
// app/Infinri/Core/App/Router.php
namespace Infinri\Core\App;

class Router
{
    public function __construct(
        private Loader $routeLoader,
        private Request $request
    ) {}
    
    public function match(Request $request): Route
    {
        $routes = $this->routeLoader->load();
        $path = $request->getPath();
        
        foreach ($routes as $pattern => $routeConfig) {
            if ($match = $this->matchPattern($pattern, $path)) {
                return new Route(
                    module: $routeConfig['module'],
                    controller: $routeConfig['controller'],
                    action: $routeConfig['action'],
                    params: $match['params']
                );
            }
        }
        
        throw new RouteNotFoundException("No route found for: {$path}");
    }
    
    private function matchPattern(string $pattern, string $path): ?array
    {
        // Pattern matching logic moved from FrontController
        // Return ['params' => [...]] or null
    }
}
```

**Step 2: Create Route Value Object**
```php
// app/Infinri/Core/App/Route.php
namespace Infinri\Core\App;

class Route
{
    public function __construct(
        public readonly string $module,
        public readonly string $controller,
        public readonly string $action,
        public readonly array $params = []
    ) {}
    
    public function getControllerClass(): string
    {
        return "Infinri\\{$this->module}\\Controller\\{$this->controller}";
    }
}
```

**Step 3: Extract Dispatcher**
```php
// app/Infinri/Core/App/Dispatcher.php
namespace Infinri\Core\App;

class Dispatcher
{
    public function __construct(
        private ObjectManager $objectManager
    ) {}
    
    public function dispatch(Route $route, Request $request): Response
    {
        $controllerClass = $route->getControllerClass();
        
        if (!class_exists($controllerClass)) {
            throw new ControllerNotFoundException("Controller not found: {$controllerClass}");
        }
        
        $controller = $this->objectManager->create($controllerClass);
        $actionMethod = $route->action . 'Action';
        
        if (!method_exists($controller, $actionMethod)) {
            throw new ActionNotFoundException("Action not found: {$actionMethod}");
        }
        
        // Set route params on request
        foreach ($route->params as $key => $value) {
            $request->setParam($key, $value);
        }
        
        return $controller->{$actionMethod}();
    }
}
```

**Step 4: Slim Down FrontController**
```php
// app/Infinri/Core/App/FrontController.php (refactored)
namespace Infinri\Core\App;

class FrontController
{
    public function __construct(
        private Router $router,
        private Dispatcher $dispatcher,
        private Request $request
    ) {}
    
    public function dispatch(): Response
    {
        try {
            $route = $this->router->match($this->request);
            return $this->dispatcher->dispatch($route, $this->request);
        } catch (RouteNotFoundException $e) {
            return $this->handle404($e);
        } catch (ControllerNotFoundException $e) {
            return $this->handle500($e);
        }
    }
    
    private function handle404(\Exception $e): Response
    {
        // 404 handling
    }
    
    private function handle500(\Exception $e): Response
    {
        // 500 handling
    }
}
```

**Metrics**:
- FrontController: 418 LOC → ~100 LOC (76% reduction)
- Cyclomatic complexity: ~20 → ~5 (75% reduction)
- Each class has single responsibility ✅

---

## 3.2 UiComponentRenderer Refactoring (SRP)

### Problem
`Core/View/Element/UiComponentRenderer.php` handles multiple responsibilities:
- XML resolution
- DataProvider instantiation
- Grid rendering
- Form rendering
- Toolbar rendering

### Target Architecture

```
UiComponentRenderer (factory, ~50 LOC)
  ├─> ComponentResolver (XML + DataProvider)
  ├─> GridRenderer (grid-specific)
  ├─> FormRenderer (form-specific)
  └─> ToolbarRenderer (shared)
```

### Implementation

**Step 1: Component Resolver**
```php
// app/Infinri/Core/View/Element/ComponentResolver.php
namespace Infinri\Core\View\Element;

class ComponentResolver
{
    public function __construct(
        private ObjectManager $objectManager
    ) {}
    
    public function findXml(string $componentName): \SimpleXMLElement
    {
        // Move findComponentXml() logic here
    }
    
    public function getDataProvider(\SimpleXMLElement $xml): ?object
    {
        $dataSourceNode = $xml->xpath('//dataSource')[0] ?? null;
        if (!$dataSourceNode) {
            return null;
        }
        
        $class = (string)$dataSourceNode->dataProvider['class'];
        return $this->objectManager->create($class);
    }
}
```

**Step 2: Grid Renderer**
```php
// app/Infinri/Core/View/Element/GridRenderer.php
namespace Infinri\Core\View\Element;

class GridRenderer
{
    public function render(\SimpleXMLElement $xml, array $data): string
    {
        $columns = $this->getColumns($xml);
        $toolbar = $this->toolbarRenderer->render($xml);
        
        return $this->buildGrid($columns, $data, $toolbar);
    }
    
    private function getColumns(\SimpleXMLElement $xml): array
    {
        // Move from UiComponentRenderer
    }
    
    private function buildGrid(array $columns, array $data, string $toolbar): string
    {
        // Move renderGrid() logic here
    }
}
```

**Step 3: Form Renderer**
```php
// app/Infinri/Core/View/Element/FormRenderer.php
namespace Infinri\Core\View\Element;

class FormRenderer
{
    public function render(\SimpleXMLElement $xml, array $data): string
    {
        $fieldsets = $this->getFieldsets($xml);
        $buttons = $this->toolbarRenderer->renderButtons($xml);
        
        return $this->buildForm($fieldsets, $data, $buttons);
    }
    
    private function getFieldsets(\SimpleXMLElement $xml): array
    {
        // Move from UiFormRenderer
    }
    
    private function buildForm(array $fieldsets, array $data, string $buttons): string
    {
        // Form building logic
    }
}
```

**Step 4: Refactor UiComponentRenderer**
```php
// app/Infinri/Core/View/Element/UiComponentRenderer.php (refactored)
namespace Infinri\Core\View\Element;

class UiComponentRenderer
{
    public function __construct(
        private ComponentResolver $resolver,
        private GridRenderer $gridRenderer,
        private FormRenderer $formRenderer
    ) {}
    
    public function render(string $componentName): string
    {
        $xml = $this->resolver->findXml($componentName);
        $dataProvider = $this->resolver->getDataProvider($xml);
        $data = $dataProvider?->getData() ?? [];
        
        return match($xml->getName()) {
            'listing' => $this->gridRenderer->render($xml, $data),
            'form' => $this->formRenderer->render($xml, $data),
            default => throw new \Exception("Unknown component type")
        };
    }
}
```

---

## 3.3 Remove HTML from Controllers

### Problem
Controllers generate HTML using `<<<HTML` syntax.

### Solution

**Find Offenders**:
```bash
grep -rn "<<<HTML\|<<<JAVASCRIPT" app/Infinri/*/Controller/
```

**Migration Pattern**:

**BEFORE** (Controller generates HTML):
```php
// app/Infinri/Core/Controller/Product/ViewController.php
public function execute()
{
    $products = $this->repository->getAll();
    
    $html = sprintf(<<<HTML
        <div class="products">
            %s
        </div>
    HTML, $this->renderProducts($products));
    
    return $this->response->setBody($html);
}
```

**AFTER** (Controller uses layout):
```php
// app/Infinri/Core/Controller/Product/ViewController.php
public function execute()
{
    $products = $this->repository->getAll();
    return $this->layoutFactory->render('product_view', ['products' => $products]);
}
```

**Create Template**:
```php
// app/Infinri/Core/view/frontend/templates/product/view.phtml
<div class="products">
    <?php foreach ($block->getProducts() as $product): ?>
        <div class="product-card">
            <h3><?= $block->escapeHtml($product->getName()) ?></h3>
            <p><?= $block->escapeHtml($product->getDescription()) ?></p>
        </div>
    <?php endforeach; ?>
</div>
```

**Create Block**:
```php
// app/Infinri/Core/Block/Product/View.php
namespace Infinri\Core\Block\Product;

class View extends \Infinri\Core\Block\Template
{
    public function getProducts(): array
    {
        return $this->getData('products') ?? [];
    }
}
```

---

## 3.4 Media Picker Refactoring (SRP)

### Problem
Media Picker controller mixes file I/O, HTML generation, and controller logic.

### Implementation

**Step 1: Create MediaLibrary Service**
```php
// app/Infinri/Core/Model/Media/MediaLibrary.php
namespace Infinri\Core\Model\Media;

class MediaLibrary
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    
    public function getFiles(string $path, int $page = 1, int $perPage = 50): array
    {
        $allFiles = glob($path . '/*');
        $validFiles = array_filter($allFiles, fn($f) => $this->isValidImage($f));
        
        // Paginate
        $offset = ($page - 1) * $perPage;
        $files = array_slice($validFiles, $offset, $perPage);
        
        return array_map(fn($f) => $this->getFileInfo($f), $files);
    }
    
    public function getFileInfo(string $path): FileInfo
    {
        return new FileInfo(
            path: $path,
            name: basename($path),
            size: filesize($path),
            extension: pathinfo($path, PATHINFO_EXTENSION),
            mimeType: mime_content_type($path),
            url: $this->getFileUrl($path)
        );
    }
    
    private function isValidImage(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::ALLOWED_EXTENSIONS) && is_file($path);
    }
    
    private function getFileUrl(string $path): string
    {
        // Convert filesystem path to URL
    }
}
```

**Step 2: Create FileInfo Value Object**
```php
// app/Infinri/Core/Model/Media/FileInfo.php
namespace Infinri\Core\Model\Media;

class FileInfo
{
    public function __construct(
        public readonly string $path,
        public readonly string $name,
        public readonly int $size,
        public readonly string $extension,
        public readonly string $mimeType,
        public readonly string $url
    ) {}
    
    public function getFormattedSize(): string
    {
        return $this->formatBytes($this->size);
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
```

**Step 3: Slim Controller**
```php
// app/Infinri/Cms/Controller/Adminhtml/Media/Picker.php (refactored)
public function execute()
{
    $page = $this->request->getInt('page', 1);
    $files = $this->mediaLibrary->getFiles('/pub/media', $page);
    
    return $this->layoutFactory->render('media_picker', [
        'files' => $files,
        'page' => $page
    ]);
}
```

**Step 4: Create Template**
```php
// app/Infinri/Cms/view/adminhtml/templates/media/picker.phtml
<div class="media-picker">
    <div class="media-grid">
        <?php foreach ($block->getFiles() as $file): ?>
            <div class="media-card" data-url="<?= $block->escapeHtmlAttr($file->url) ?>">
                <img src="<?= $block->escapeUrl($file->url) ?>" alt="<?= $block->escapeHtmlAttr($file->name) ?>">
                <span class="filename"><?= $block->escapeHtml($file->name) ?></span>
                <span class="filesize"><?= $block->escapeHtml($file->getFormattedSize()) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Pagination -->
    <div class="pagination">
        <!-- Pagination controls -->
    </div>
</div>
```

---

## Verification Checklist

- [ ] FrontController split into Router + Dispatcher
- [ ] FrontController LOC < 150
- [ ] UiComponentRenderer split into specialized renderers
- [ ] UiComponentRenderer LOC < 100
- [ ] No controllers generate HTML
- [ ] All HTML in templates
- [ ] MediaLibrary service created
- [ ] Media picker uses service + template
- [ ] All tests passing
- [ ] Cyclomatic complexity reduced by 50%+

---

## Files Created

**Router/Dispatcher**:
- `app/Infinri/Core/App/Router.php`
- `app/Infinri/Core/App/Dispatcher.php`
- `app/Infinri/Core/App/Route.php`

**UI Renderers**:
- `app/Infinri/Core/View/Element/ComponentResolver.php`
- `app/Infinri/Core/View/Element/GridRenderer.php`
- `app/Infinri/Core/View/Element/FormRenderer.php`
- `app/Infinri/Core/View/Element/ToolbarRenderer.php`

**Media Library**:
- `app/Infinri/Core/Model/Media/MediaLibrary.php`
- `app/Infinri/Core/Model/Media/FileInfo.php`
- `app/Infinri/Cms/view/adminhtml/templates/media/picker.phtml`

**Tests**:
- `tests/Unit/Core/App/RouterTest.php`
- `tests/Unit/Core/App/DispatcherTest.php`
- `tests/Unit/Core/Model/Media/MediaLibraryTest.php`

## Files Modified

- `app/Infinri/Core/App/FrontController.php` (slimmed down)
- `app/Infinri/Core/View/Element/UiComponentRenderer.php` (slimmed down)
- `app/Infinri/Cms/Controller/Adminhtml/Media/Picker.php` (slimmed down)
- Any controllers with HTML generation

---

## Success Criteria

- ✅ All classes < 200 LOC
- ✅ Each class has single responsibility
- ✅ Cyclomatic complexity < 10 per method
- ✅ No HTML in controllers
- ✅ All tests passing (100%)
- ✅ Code coverage > 80%
