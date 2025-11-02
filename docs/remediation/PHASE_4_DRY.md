# Phase 4: Code Quality - DRY/KISS

**Timeline**: Week 5 | **Priority**: 🟢 MEDIUM

---

## 4.1 Extract Base Controller Classes

### Problem
Controllers repeat common patterns: authentication, redirects, layout rendering, flash messages.

### Implementation

**Step 1: Create AbstractController**
```php
// app/Infinri/Core/Controller/AbstractController.php
namespace Infinri\Core\Controller;

use Infinri\Core\App\Request;
use Infinri\Core\App\Response;
use Infinri\Core\App\Session;
use Infinri\Core\Model\View\LayoutFactory;

abstract class AbstractController
{
    public function __construct(
        protected Request $request,
        protected Response $response,
        protected Session $session,
        protected LayoutFactory $layoutFactory
    ) {}
    
    protected function getParam(string $key, $default = null)
    {
        return $this->request->getParam($key, $default);
    }
    
    protected function redirect(string $url): Response
    {
        return $this->response
            ->setStatusCode(302)
            ->setHeader('Location', $url);
    }
    
    protected function addMessage(string $type, string $message): void
    {
        $messages = $this->session->getFlash('messages') ?? [];
        $messages[] = ['type' => $type, 'message' => $message];
        $this->session->flash('messages', $messages);
    }
    
    protected function addSuccess(string $message): void
    {
        $this->addMessage('success', $message);
    }
    
    protected function addError(string $message): void
    {
        $this->addMessage('error', $message);
    }
    
    protected function addWarning(string $message): void
    {
        $this->addMessage('warning', $message);
    }
    
    protected function renderLayout(string $handle, array $data = []): Response
    {
        $html = $this->layoutFactory->render($handle, $data);
        return $this->response->setBody($html);
    }
    
    protected function json(array $data, int $status = 200): Response
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }
}
```

**Step 2: Create AbstractAdminController**
```php
// app/Infinri/Core/Controller/AbstractAdminController.php
namespace Infinri\Core\Controller;

use Infinri\Core\Model\Auth\User;

abstract class AbstractAdminController extends AbstractController
{
    protected function isAuthenticated(): bool
    {
        return $this->session->has('admin_user_id');
    }
    
    protected function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            throw new \RuntimeException('Authentication required');
        }
    }
    
    protected function getCurrentUser(): ?User
    {
        $userId = $this->session->get('admin_user_id');
        if (!$userId) {
            return null;
        }
        
        return $this->userRepository->getById($userId);
    }
    
    protected function redirectToLogin(): Response
    {
        return $this->redirect('/admin/auth/login');
    }
    
    protected function redirectToGrid(string $entity): Response
    {
        return $this->redirect("/admin/{$entity}/index");
    }
}
```

**Step 3: Create AbstractRestController**
```php
// app/Infinri/Core/Controller/AbstractRestController.php
namespace Infinri\Core\Controller;

abstract class AbstractRestController extends AbstractController
{
    protected function success(array $data = [], string $message = ''): Response
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    protected function error(string $message, array $errors = [], int $status = 400): Response
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
    
    protected function notFound(string $message = 'Resource not found'): Response
    {
        return $this->error($message, [], 404);
    }
    
    protected function validateRequired(array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!$this->request->has($field)) {
                $errors[$field] = "Field '{$field}' is required";
            }
        }
        return $errors;
    }
}
```

**Step 4: Refactor Existing Controllers**

**BEFORE**:
```php
// app/Infinri/Cms/Controller/Adminhtml/Page/Save.php
class Save
{
    public function __construct(
        private Request $request,
        private Response $response,
        private Session $session,
        private PageRepository $repository,
        private LayoutFactory $layoutFactory
    ) {}
    
    public function execute()
    {
        $id = $this->request->getParam('id');
        $title = $this->request->getParam('title');
        
        // Save logic...
        
        $messages = $this->session->getFlash('messages') ?? [];
        $messages[] = ['type' => 'success', 'message' => 'Page saved'];
        $this->session->flash('messages', $messages);
        
        return $this->response
            ->setStatusCode(302)
            ->setHeader('Location', '/admin/cms/page/index');
    }
}
```

**AFTER**:
```php
// app/Infinri/Cms/Controller/Adminhtml/Page/Save.php
class Save extends AbstractAdminController
{
    public function __construct(
        Request $request,
        Response $response,
        Session $session,
        LayoutFactory $layoutFactory,
        private PageRepository $repository
    ) {
        parent::__construct($request, $response, $session, $layoutFactory);
    }
    
    public function execute()
    {
        $this->requireAuth();
        
        $id = $this->getParam('id');
        $title = $this->getParam('title');
        
        // Save logic...
        
        $this->addSuccess('Page saved successfully');
        return $this->redirectToGrid('cms/page');
    }
}
```

**Metrics**:
- LOC reduction per controller: 30-50%
- Code duplication: 60%+ reduction

---

## 4.2 Helper Classes for Common Operations

### Problem
Utility functions duplicated across codebase.

### Implementation

**Step 1: Data Helper**
```php
// app/Infinri/Core/Helper/Data.php
namespace Infinri\Core\Helper;

class Data
{
    /**
     * Get nested array value using dot notation
     */
    public static function arrayGet(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }
        
        return $array;
    }
    
    /**
     * Set nested array value using dot notation
     */
    public static function arraySet(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $i => $key) {
            if ($i === count($keys) - 1) {
                $current[$key] = $value;
            } else {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = [];
                }
                $current = &$current[$key];
            }
        }
    }
    
    /**
     * Format file size
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Truncate string
     */
    public static function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
}
```

**Step 2: String Helper**
```php
// app/Infinri/Core/Helper/String.php
namespace Infinri\Core\Helper;

class String
{
    /**
     * Convert string to slug
     */
    public static function slugify(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);
        
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim
        $text = trim($text, '-');
        
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        
        return empty($text) ? 'n-a' : $text;
    }
    
    /**
     * Convert to camelCase
     */
    public static function camelCase(string $text): string
    {
        $text = str_replace(['-', '_'], ' ', $text);
        $text = ucwords($text);
        $text = str_replace(' ', '', $text);
        return lcfirst($text);
    }
    
    /**
     * Convert to snake_case
     */
    public static function snakeCase(string $text): string
    {
        $text = preg_replace('/([a-z])([A-Z])/', '$1_$2', $text);
        return strtolower($text);
    }
    
    /**
     * Convert to PascalCase
     */
    public static function pascalCase(string $text): string
    {
        return ucfirst(self::camelCase($text));
    }
    
    /**
     * Generate random string
     */
    public static function random(int $length = 16): string
    {
        return bin2hex(random_bytes($length / 2));
    }
}
```

**Step 3: URL Helper**
```php
// app/Infinri/Core/Helper/Url.php
namespace Infinri\Core\Helper;

class Url
{
    private string $baseUrl;
    
    public function __construct(
        private \Infinri\Core\Model\Config\ScopeConfig $config
    ) {
        $this->baseUrl = $this->config->getValue('web/unsecure/base_url');
    }
    
    public function getBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }
    
    public function getUrl(string $path = '', array $params = []): string
    {
        $url = $this->getBaseUrl() . '/' . ltrim($path, '/');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    public function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        return "{$protocol}://{$host}{$uri}";
    }
    
    public function getAdminUrl(string $path = '', array $params = []): string
    {
        return $this->getUrl('admin/' . ltrim($path, '/'), $params);
    }
}
```

**Step 4: File Helper**
```php
// app/Infinri/Core/Helper/File.php
namespace Infinri\Core\Helper;

class File
{
    /**
     * Ensure directory exists
     */
    public static function ensureDirectoryExists(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    /**
     * Delete directory recursively
     */
    public static function deleteDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }
        
        $files = array_diff(scandir($path), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $path . '/' . $file;
            is_dir($filePath) ? self::deleteDirectory($filePath) : unlink($filePath);
        }
        
        return rmdir($path);
    }
    
    /**
     * Get file extension
     */
    public static function getExtension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }
    
    /**
     * Get mime type
     */
    public static function getMimeType(string $path): string
    {
        return mime_content_type($path) ?: 'application/octet-stream';
    }
    
    /**
     * Copy directory recursively
     */
    public static function copyDirectory(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }
        
        self::ensureDirectoryExists($dest);
        
        $files = array_diff(scandir($source), ['.', '..']);
        
        foreach ($files as $file) {
            $srcPath = $source . '/' . $file;
            $destPath = $dest . '/' . $file;
            
            if (is_dir($srcPath)) {
                self::copyDirectory($srcPath, $destPath);
            } else {
                copy($srcPath, $destPath);
            }
        }
        
        return true;
    }
}
```

---

## 4.3 HTML Builders

### Problem
HTML generation duplicated across views.

### Implementation

**TableBuilder**:
```php
// app/Infinri/Core/View/TableBuilder.php
namespace Infinri\Core\View;

class TableBuilder
{
    private array $columns = [];
    private array $rows = [];
    private array $attributes = [];
    
    public static function create(): self
    {
        return new self();
    }
    
    public function addColumn(string $key, string $label, ?callable $formatter = null): self
    {
        $this->columns[$key] = [
            'label' => $label,
            'formatter' => $formatter
        ];
        return $this;
    }
    
    public function setRows(array $rows): self
    {
        $this->rows = $rows;
        return $this;
    }
    
    public function setAttribute(string $key, string $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }
    
    public function render(): string
    {
        $attrs = $this->renderAttributes();
        
        $html = "<table{$attrs}>";
        $html .= $this->renderHeader();
        $html .= $this->renderBody();
        $html .= "</table>";
        
        return $html;
    }
    
    private function renderAttributes(): string
    {
        $attrs = '';
        foreach ($this->attributes as $key => $value) {
            $attrs .= " {$key}=\"" . htmlspecialchars($value) . "\"";
        }
        return $attrs;
    }
    
    private function renderHeader(): string
    {
        $html = '<thead><tr>';
        foreach ($this->columns as $column) {
            $html .= '<th>' . htmlspecialchars($column['label']) . '</th>';
        }
        $html .= '</tr></thead>';
        return $html;
    }
    
    private function renderBody(): string
    {
        $html = '<tbody>';
        foreach ($this->rows as $row) {
            $html .= '<tr>';
            foreach ($this->columns as $key => $column) {
                $value = is_array($row) ? ($row[$key] ?? '') : $row->getData($key);
                
                if ($column['formatter']) {
                    $value = call_user_func($column['formatter'], $value, $row);
                }
                
                $html .= '<td>' . $value . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        return $html;
    }
}
```

**Usage**:
```php
$table = TableBuilder::create()
    ->setAttribute('class', 'data-grid')
    ->addColumn('id', 'ID')
    ->addColumn('title', 'Title')
    ->addColumn('status', 'Status', function($value) {
        return $value ? '<span class="badge success">Active</span>' 
                     : '<span class="badge">Inactive</span>';
    })
    ->setRows($pages)
    ->render();
```

---

## 4.4 Eliminate Magic Strings & Numbers

### Problem
Hardcoded values make code fragile and hard to maintain.

### Implementation

**Step 1: HTTP Status Constants**
```php
// app/Infinri/Core/Model/Constants/HttpStatus.php
namespace Infinri\Core\Model\Constants;

class HttpStatus
{
    // Success
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    
    // Redirection
    public const HTTP_MOVED_PERMANENTLY = 301;
    public const HTTP_FOUND = 302;
    public const HTTP_NOT_MODIFIED = 304;
    
    // Client Error
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    
    // Server Error
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_NOT_IMPLEMENTED = 501;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
}
```

**Step 2: CMS Constants**
```php
// app/Infinri/Cms/Model/Constants/PageStatus.php
namespace Infinri\Cms\Model\Constants;

class PageStatus
{
    public const ENABLED = 1;
    public const DISABLED = 0;
    
    public static function getLabel(int $status): string
    {
        return match($status) {
            self::ENABLED => 'Enabled',
            self::DISABLED => 'Disabled',
            default => 'Unknown'
        };
    }
}
```

**Step 3: User Role Constants**
```php
// app/Infinri/Core/Model/Constants/UserRole.php
namespace Infinri\Core\Model\Constants;

class UserRole
{
    public const ADMIN = 'admin';
    public const EDITOR = 'editor';
    public const VIEWER = 'viewer';
    
    public static function all(): array
    {
        return [self::ADMIN, self::EDITOR, self::VIEWER];
    }
    
    public static function isValid(string $role): bool
    {
        return in_array($role, self::all());
    }
}
```

**Step 4: Replace Magic Values**

**BEFORE**:
```php
if ($page->getData('is_active') == 1) {
    // ...
}

return $response->setStatusCode(404);

if ($user->getRole() === 'admin') {
    // ...
}
```

**AFTER**:
```php
use Infinri\Cms\Model\Constants\PageStatus;
use Infinri\Core\Model\Constants\HttpStatus;
use Infinri\Core\Model\Constants\UserRole;

if ($page->getData('is_active') === PageStatus::ENABLED) {
    // ...
}

return $response->setStatusCode(HttpStatus::HTTP_NOT_FOUND);

if ($user->getRole() === UserRole::ADMIN) {
    // ...
}
```

---

## Verification Checklist

- [ ] Base controller classes created
- [ ] All controllers extend appropriate base
- [ ] Helper classes implemented (Data, String, Url, File)
- [ ] Duplicated utility code replaced
- [ ] TableBuilder implemented
- [ ] HTML builders used in views
- [ ] Constant classes created
- [ ] All magic values replaced
- [ ] Tests updated and passing
- [ ] Code duplication reduced by 50%+

---

## Files Created

**Controllers**:
- `app/Infinri/Core/Controller/AbstractController.php`
- `app/Infinri/Core/Controller/AbstractAdminController.php`
- `app/Infinri/Core/Controller/AbstractRestController.php`

**Helpers**:
- `app/Infinri/Core/Helper/Data.php`
- `app/Infinri/Core/Helper/String.php`
- `app/Infinri/Core/Helper/Url.php`
- `app/Infinri/Core/Helper/File.php`

**Builders**:
- `app/Infinri/Core/View/TableBuilder.php`
- `app/Infinri/Core/View/FormBuilder.php`

**Constants**:
- `app/Infinri/Core/Model/Constants/HttpStatus.php`
- `app/Infinri/Cms/Model/Constants/PageStatus.php`
- `app/Infinri/Core/Model/Constants/UserRole.php`

**Tests**:
- Unit tests for all helpers
- Unit tests for builders
- Integration tests for controllers

---

## Success Criteria

- ✅ Code duplication reduced by 50%+
- ✅ All controllers < 100 LOC
- ✅ Zero magic strings/numbers (code scan)
- ✅ All tests passing (100%)
- ✅ Helper classes fully documented
