# Phase 6: Performance Optimization

**Timeline**: Week 7-8 | **Priority**: ⚡ LOW

---

## 6.1 Layout Processor Optimization

### Problem
Nested loops over XML nodes cause O(N²) complexity. Complex layouts slow down page rendering.

### Current Bottlenecks

**Profile the processor**:
```php
// Add timing to Processor.php
$start = microtime(true);
$this->processRemove($xml);
$removeTime = microtime(true) - $start;

$start = microtime(true);
$this->processMove($xml);
$moveTime = microtime(true) - $start;

error_log("Layout processing: remove={$removeTime}s, move={$moveTime}s");
```

### Implementation

**Step 1: Single-Pass Processing**

**BEFORE** (multiple passes):
```php
// app/Infinri/Core/Model/Layout/Processor.php
public function process(SimpleXMLElement $xml): SimpleXMLElement
{
    $this->processRemove($xml);      // Pass 1
    $this->processMove($xml);        // Pass 2
    $this->processReference($xml);   // Pass 3
    return $xml;
}
```

**AFTER** (single pass):
```php
public function process(SimpleXMLElement $xml): SimpleXMLElement
{
    // Build index first (O(N))
    $index = $this->buildNodeIndex($xml);
    
    // Single pass with indexed lookups (O(N))
    $this->processDirectives($xml, $index);
    
    return $xml;
}

private function buildNodeIndex(SimpleXMLElement $xml): array
{
    $index = [];
    
    foreach ($xml->xpath('//*[@name]') as $node) {
        $name = (string)$node['name'];
        $index[$name] = $node;
    }
    
    return $index;
}

private function processDirectives(SimpleXMLElement $xml, array $index): void
{
    // Process all directives in one pass
    foreach ($xml->xpath('//*') as $node) {
        $nodeName = $node->getName();
        
        switch ($nodeName) {
            case 'remove':
                $this->handleRemove($node, $index);
                break;
            case 'move':
                $this->handleMove($node, $index);
                break;
            case 'referenceContainer':
            case 'referenceBlock':
                $this->handleReference($node, $index);
                break;
        }
    }
}
```

**Step 2: Use DOMDocument Instead of SimpleXML**

SimpleXML is slow for modifications. DOMDocument is faster.

```php
// app/Infinri/Core/Model/Layout/Processor.php
use DOMDocument;
use DOMXPath;

class Processor
{
    public function process(DOMDocument $doc): DOMDocument
    {
        $xpath = new DOMXPath($doc);
        
        // Much faster XPath queries
        $removes = $xpath->query('//remove');
        foreach ($removes as $remove) {
            $targetName = $remove->getAttribute('name');
            $targets = $xpath->query("//*[@name='{$targetName}']");
            
            foreach ($targets as $target) {
                $target->parentNode->removeChild($target);
            }
        }
        
        return $doc;
    }
}
```

**Step 3: Layout Caching**

```php
// app/Infinri/Core/Model/Layout/Cache.php
namespace Infinri\Core\Model\Layout;

use Infinri\Core\Model\Cache\CacheInterface;

class Cache
{
    private const CACHE_PREFIX = 'layout_';
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(
        private CacheInterface $cache,
        private Config $config
    ) {}
    
    public function get(string $handle, string $area): ?string
    {
        if ($this->config->getBool('dev/debug/enabled')) {
            return null; // No cache in dev mode
        }
        
        $key = $this->getCacheKey($handle, $area);
        return $this->cache->get($key);
    }
    
    public function set(string $handle, string $area, string $xml): void
    {
        if ($this->config->getBool('dev/debug/enabled')) {
            return; // No cache in dev mode
        }
        
        $key = $this->getCacheKey($handle, $area);
        $this->cache->set($key, $xml, self::CACHE_TTL);
    }
    
    public function invalidate(string $handle = null): void
    {
        if ($handle) {
            $key = $this->getCacheKey($handle, '*');
            $this->cache->delete($key);
        } else {
            $this->cache->deleteByPrefix(self::CACHE_PREFIX);
        }
    }
    
    private function getCacheKey(string $handle, string $area): string
    {
        return self::CACHE_PREFIX . md5("{$area}_{$handle}");
    }
}
```

**Integrate caching**:
```php
// app/Infinri/Core/Model/View/LayoutFactory.php
public function render(string $handle, array $data = []): string
{
    // Try cache first
    $cachedXml = $this->layoutCache->get($handle, 'frontend');
    
    if ($cachedXml) {
        $xml = new SimpleXMLElement($cachedXml);
    } else {
        $handles = $this->resolveHandles($handle);
        $xmlFiles = $this->loader->load($handles);
        $mergedXml = $this->merger->merge($xmlFiles);
        $xml = $this->processor->process($mergedXml);
        
        // Cache processed XML
        $this->layoutCache->set($handle, 'frontend', $xml->asXML());
    }
    
    // Continue with rendering...
}
```

**Benchmark Results** (target):
```
Before optimization:
- Simple layout: 50ms
- Complex layout: 200ms

After optimization:
- Simple layout: 10ms (80% faster)
- Complex layout: 40ms (80% faster)
- Cached layout: <1ms (99% faster)
```

---

## 6.2 Grid Rendering Optimization

### Problem
Nested loops (buttons × columns × rows) become slow with large datasets.

### Implementation

**Step 1: Pagination at Query Level**

```php
// app/Infinri/Cms/Ui/Component/Listing/DataProvider.php
public function getData(): array
{
    $page = $this->request->getInt('page', 1);
    $pageSize = $this->request->getInt('pageSize', 20);
    
    // Paginate at database level
    $offset = ($page - 1) * $pageSize;
    $pages = $this->repository->getList($pageSize, $offset);
    $total = $this->repository->count();
    
    return [
        'items' => $pages,
        'totalRecords' => $total,
        'page' => $page,
        'pageSize' => $pageSize
    ];
}
```

```php
// app/Infinri/Cms/Model/Repository/PageRepository.php
public function getList(int $limit, int $offset = 0): array
{
    $sql = "SELECT * FROM cms_page ORDER BY page_id DESC LIMIT ? OFFSET ?";
    $stmt = $this->connection->prepare($sql);
    $stmt->execute([$limit, $offset]);
    
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

public function count(): int
{
    $sql = "SELECT COUNT(*) FROM cms_page";
    return (int)$this->connection->query($sql)->fetchColumn();
}
```

**Step 2: Grid HTML Caching**

```php
// app/Infinri/Core/View/Element/GridRenderer.php
public function render(SimpleXMLElement $xml, array $data): string
{
    $page = $data['page'] ?? 1;
    $cacheKey = $this->getCacheKey($xml, $page);
    
    // Try cache
    $cached = $this->cache->get($cacheKey);
    if ($cached && !$this->config->getBool('dev/debug/enabled')) {
        return $cached;
    }
    
    // Render grid
    $html = $this->buildGrid($xml, $data);
    
    // Cache for 5 minutes
    $this->cache->set($cacheKey, $html, 300);
    
    return $html;
}

private function getCacheKey(SimpleXMLElement $xml, int $page): string
{
    $componentName = (string)$xml['name'];
    return "grid_{$componentName}_page_{$page}";
}
```

**Step 3: AJAX Pagination**

```javascript
// app/Infinri/Core/view/adminhtml/web/js/grid.js
class DataGrid {
    constructor(gridElement) {
        this.grid = gridElement;
        this.currentPage = 1;
        this.bindEvents();
    }
    
    bindEvents() {
        this.grid.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadPage(parseInt(link.dataset.page));
            });
        });
    }
    
    async loadPage(page) {
        this.currentPage = page;
        const url = `${window.location.pathname}?page=${page}`;
        
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            
            const html = await response.text();
            this.updateGrid(html);
        } catch (error) {
            console.error('Failed to load page:', error);
        }
    }
    
    updateGrid(html) {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newGrid = doc.querySelector('.data-grid');
        
        this.grid.innerHTML = newGrid.innerHTML;
        this.bindEvents();
    }
}

// Initialize all grids
document.querySelectorAll('.data-grid').forEach(grid => {
    new DataGrid(grid);
});
```

**Benchmark Results** (target):
```
Before optimization:
- 1000 rows: 2000ms
- 10000 rows: 20000ms (unusable)

After optimization:
- 20 rows/page: 100ms (20x faster)
- With cache: <10ms (200x faster)
- AJAX pagination: instant (no page reload)
```

---

## 6.3 Database Query Optimization

### Problem
- N+1 query patterns
- Missing indexes
- Inefficient queries

### Implementation

**Step 1: Enable Query Logging**

```php
// app/Infinri/Core/Model/Database/Connection.php
class Connection
{
    private array $queryLog = [];
    private bool $logEnabled = false;
    
    public function enableQueryLog(): void
    {
        $this->logEnabled = true;
    }
    
    public function query(string $sql)
    {
        $start = microtime(true);
        $result = parent::query($sql);
        $time = microtime(true) - $start;
        
        if ($this->logEnabled) {
            $this->queryLog[] = [
                'sql' => $sql,
                'time' => $time,
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ];
        }
        
        return $result;
    }
    
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }
}
```

**Step 2: Find N+1 Queries**

```php
// scripts/analyze-queries.php
<?php
require __DIR__ . '/../app/bootstrap.php';

$connection = $objectManager->get(\Infinri\Core\Model\Database\Connection::class);
$connection->enableQueryLog();

// Simulate page load
$pageRepository = $objectManager->get(\Infinri\Cms\Model\Repository\PageRepository::class);
$pages = $pageRepository->getAll();

foreach ($pages as $page) {
    // This might trigger N queries
    $page->getBlocks(); // ❌ N+1 query
}

$queries = $connection->getQueryLog();
echo "Total queries: " . count($queries) . "\n";

// Group by SQL pattern
$patterns = [];
foreach ($queries as $query) {
    $pattern = preg_replace('/\d+/', '?', $query['sql']);
    $patterns[$pattern] = ($patterns[$pattern] ?? 0) + 1;
}

arsort($patterns);
echo "\nMost frequent queries:\n";
foreach (array_slice($patterns, 0, 10) as $pattern => $count) {
    echo "  [{$count}x] {$pattern}\n";
}
```

**Step 3: Fix N+1 with Eager Loading**

**BEFORE** (N+1 query):
```php
// Get all pages (1 query)
$pages = $this->pageRepository->getAll();

// Get blocks for each page (N queries)
foreach ($pages as $page) {
    $blocks = $page->getBlocks(); // SELECT * FROM cms_block WHERE page_id = ?
}
```

**AFTER** (2 queries):
```php
// Get all pages (1 query)
$pages = $this->pageRepository->getAll();
$pageIds = array_column($pages, 'page_id');

// Get all blocks in one query (1 query)
$blocks = $this->blockRepository->getByPageIds($pageIds);

// Group blocks by page
$blocksByPage = [];
foreach ($blocks as $block) {
    $blocksByPage[$block->getPageId()][] = $block;
}

// Attach blocks to pages
foreach ($pages as $page) {
    $page->setBlocks($blocksByPage[$page->getId()] ?? []);
}
```

**Step 4: Add Database Indexes**

```xml
<!-- app/Infinri/Core/etc/db_schema.xml -->
<schema>
    <table name="cms_page">
        <!-- Add indexes for frequently queried columns -->
        <index referenceId="CMS_PAGE_URL_KEY" indexType="btree">
            <column name="url_key"/>
        </index>
        <index referenceId="CMS_PAGE_IS_ACTIVE" indexType="btree">
            <column name="is_active"/>
        </index>
        <index referenceId="CMS_PAGE_CREATED_AT" indexType="btree">
            <column name="created_at"/>
        </index>
    </table>
    
    <table name="cms_block">
        <index referenceId="CMS_BLOCK_PAGE_ID" indexType="btree">
            <column name="page_id"/>
        </index>
    </table>
</schema>
```

**Step 5: Query Result Caching**

```php
// app/Infinri/Core/Model/Repository/AbstractRepository.php
abstract class AbstractRepository
{
    protected function cacheQuery(string $key, callable $query, int $ttl = 300)
    {
        $cached = $this->cache->get($key);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $query();
        $this->cache->set($key, $result, $ttl);
        
        return $result;
    }
}

// Usage in repository
public function getById(int $id): ?Page
{
    return $this->cacheQuery("page_{$id}", function() use ($id) {
        $sql = "SELECT * FROM cms_page WHERE page_id = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    });
}
```

**Benchmark Results** (target):
```
Before optimization:
- Homepage: 50 queries, 500ms
- Admin grid: 100 queries, 1000ms

After optimization:
- Homepage: 5 queries, 50ms (90% reduction)
- Admin grid: 3 queries, 100ms (97% reduction)
```

---

## 6.4 Media Library Optimization

### Problem
`glob()` without pagination blocks with 1000+ images.

### Implementation

**Step 1: Database-Backed Media Index**

```xml
<!-- app/Infinri/Core/etc/db_schema.xml -->
<table name="media_file">
    <column name="file_id" xsi:type="int" identity="true" nullable="false"/>
    <column name="path" xsi:type="varchar" length="255" nullable="false"/>
    <column name="name" xsi:type="varchar" length="255" nullable="false"/>
    <column name="size" xsi:type="int" nullable="false"/>
    <column name="mime_type" xsi:type="varchar" length="100" nullable="false"/>
    <column name="created_at" xsi:type="timestamp" nullable="false" default="CURRENT_TIMESTAMP"/>
    
    <constraint xsi:type="primary" referenceId="PRIMARY">
        <column name="file_id"/>
    </constraint>
    
    <index referenceId="MEDIA_FILE_PATH" indexType="btree">
        <column name="path"/>
    </index>
    <index referenceId="MEDIA_FILE_NAME" indexType="btree">
        <column name="name"/>
    </index>
</table>
```

**Step 2: Media Indexer**

```php
// app/Infinri/Core/Model/Media/Indexer.php
namespace Infinri\Core\Model\Media;

class Indexer
{
    public function __construct(
        private \PDO $connection
    ) {}
    
    public function reindex(string $directory = '/pub/media'): int
    {
        $fullPath = __DIR__ . '/../../../..' . $directory;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath)
        );
        
        $indexed = 0;
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $this->isImage($file)) {
                $this->indexFile($file);
                $indexed++;
            }
        }
        
        return $indexed;
    }
    
    private function indexFile(\SplFileInfo $file): void
    {
        $sql = "INSERT INTO media_file (path, name, size, mime_type) 
                VALUES (?, ?, ?, ?)
                ON CONFLICT (path) DO UPDATE SET 
                    size = EXCLUDED.size,
                    mime_type = EXCLUDED.mime_type";
        
        $stmt = $this->connection->prepare($sql);
        $stmt->execute([
            $file->getPathname(),
            $file->getFilename(),
            $file->getSize(),
            mime_content_type($file->getPathname())
        ]);
    }
    
    private function isImage(\SplFileInfo $file): bool
    {
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        return in_array(strtolower($file->getExtension()), $extensions);
    }
}
```

**Step 3: Paginated Media Retrieval**

```php
// app/Infinri/Core/Model/Media/MediaLibrary.php
public function getFiles(string $path, int $page = 1, int $perPage = 50): array
{
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT * FROM media_file 
            WHERE path LIKE ? 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $this->connection->prepare($sql);
    $stmt->execute([$path . '%', $perPage, $offset]);
    
    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
}

public function getTotalFiles(string $path): int
{
    $sql = "SELECT COUNT(*) FROM media_file WHERE path LIKE ?";
    $stmt = $this->connection->prepare($sql);
    $stmt->execute([$path . '%']);
    
    return (int)$stmt->fetchColumn();
}
```

**Step 4: Thumbnail Generation & Caching**

```php
// app/Infinri/Core/Model/Media/ThumbnailGenerator.php
namespace Infinri\Core\Model\Media;

class ThumbnailGenerator
{
    private const CACHE_DIR = '/pub/media/cache/thumbnails';
    
    public function generate(string $imagePath, int $width, int $height): string
    {
        $cacheKey = md5($imagePath . "_{$width}x{$height}");
        $cachePath = self::CACHE_DIR . "/{$cacheKey}.jpg";
        
        // Return cached if exists
        if (file_exists($cachePath)) {
            return $cachePath;
        }
        
        // Generate thumbnail
        $image = imagecreatefromstring(file_get_contents($imagePath));
        $thumbnail = imagescale($image, $width, $height);
        
        // Save to cache
        File::ensureDirectoryExists(dirname($cachePath));
        imagejpeg($thumbnail, $cachePath, 85);
        
        imagedestroy($image);
        imagedestroy($thumbnail);
        
        return $cachePath;
    }
}
```

**Benchmark Results** (target):
```
Before optimization:
- 1000 files: 5000ms (glob + stat each file)
- 10000 files: 50000ms (unusable)

After optimization:
- 50 files/page: 50ms (100x faster)
- Thumbnails: <10ms (cached)
- File upload: Auto-indexed
```

---

## 6.5 Caching System Implementation

### Problem
No caching layer - everything recomputed on every request.

### Implementation

**Step 1: Cache Interface**

```php
// app/Infinri/Core/Model/Cache/CacheInterface.php
namespace Infinri\Core\Model\Cache;

interface CacheInterface
{
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function deleteByPrefix(string $prefix): bool;
    public function clear(): bool;
    public function has(string $key): bool;
}
```

**Step 2: File Cache Backend**

```php
// app/Infinri/Core/Model/Cache/FileCache.php
namespace Infinri\Core\Model\Cache;

class FileCache implements CacheInterface
{
    private const CACHE_DIR = '/var/cache';
    
    public function get(string $key): mixed
    {
        $file = $this->getCacheFile($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($file));
        
        // Check expiration
        if ($data['expires_at'] < time()) {
            unlink($file);
            return null;
        }
        
        return $data['value'];
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        $file = $this->getCacheFile($key);
        File::ensureDirectoryExists(dirname($file));
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
        
        return file_put_contents($file, serialize($data)) !== false;
    }
    
    public function delete(string $key): bool
    {
        $file = $this->getCacheFile($key);
        return file_exists($file) && unlink($file);
    }
    
    private function getCacheFile(string $key): string
    {
        $hash = md5($key);
        $dir = substr($hash, 0, 2);
        return __DIR__ . '/../../../../' . self::CACHE_DIR . "/{$dir}/{$hash}";
    }
}
```

**Step 3: Redis Cache Backend**

```php
// app/Infinri/Core/Model/Cache/RedisCache.php
namespace Infinri\Core\Model\Cache;

class RedisCache implements CacheInterface
{
    private \Redis $redis;
    
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379
    ) {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
    }
    
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : null;
    }
    
    public function set(string $key, mixed $value, int $ttl = 3600): bool
    {
        return $this->redis->setex($key, $ttl, serialize($value));
    }
    
    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }
    
    public function deleteByPrefix(string $prefix): bool
    {
        $keys = $this->redis->keys($prefix . '*');
        return $this->redis->del($keys) > 0;
    }
    
    public function clear(): bool
    {
        return $this->redis->flushDB();
    }
}
```

**Step 4: Full-Page Cache**

```php
// app/Infinri/Core/App/Middleware/FullPageCache.php
namespace Infinri\Core\App\Middleware;

class FullPageCache
{
    private const CACHE_PREFIX = 'fpc_';
    private const CACHE_TTL = 3600;
    
    public function __construct(
        private CacheInterface $cache,
        private Request $request,
        private Config $config
    ) {}
    
    public function before(): ?Response
    {
        // Only cache GET requests
        if ($this->request->getMethod() !== 'GET') {
            return null;
        }
        
        // Don't cache admin pages
        if (str_starts_with($this->request->getPath(), '/admin')) {
            return null;
        }
        
        // Don't cache if user is logged in
        if ($this->request->getCookie('customer_id')) {
            return null;
        }
        
        $cacheKey = $this->getCacheKey();
        $cached = $this->cache->get($cacheKey);
        
        if ($cached) {
            return new Response(
                body: $cached,
                headers: ['X-Cache' => 'HIT']
            );
        }
        
        return null;
    }
    
    public function after(Response $response): Response
    {
        if ($this->shouldCache()) {
            $cacheKey = $this->getCacheKey();
            $this->cache->set($cacheKey, $response->getBody(), self::CACHE_TTL);
        }
        
        return $response->setHeader('X-Cache', 'MISS');
    }
    
    private function getCacheKey(): string
    {
        $uri = $this->request->getPath();
        $params = $this->request->getParams();
        ksort($params);
        
        return self::CACHE_PREFIX . md5($uri . serialize($params));
    }
    
    private function shouldCache(): bool
    {
        return $this->config->getBool('cache/fpc/enabled');
    }
}
```

---

## Verification Checklist

- [ ] Layout processor optimized (single pass)
- [ ] Layout caching implemented
- [ ] Grid pagination at query level
- [ ] Grid HTML caching added
- [ ] AJAX pagination working
- [ ] Query logging enabled
- [ ] N+1 queries identified and fixed
- [ ] Database indexes added
- [ ] Query result caching implemented
- [ ] Media indexer created
- [ ] Thumbnail generation & caching
- [ ] Cache interface implemented
- [ ] File cache backend working
- [ ] Redis cache backend working
- [ ] Full-page cache middleware added
- [ ] Performance benchmarks run
- [ ] All targets met

---

## Performance Targets

| Metric | Before | Target | Actual |
|--------|--------|--------|--------|
| Homepage load | 500ms | <200ms | ___ |
| Admin grid | 1000ms | <100ms | ___ |
| Layout compile | 200ms | <50ms | ___ |
| Media picker (1000 files) | 5000ms | <100ms | ___ |
| Database queries/page | 50 | <5 | ___ |
| With FPC | N/A | <10ms | ___ |

---

## Monitoring

**Add to production**:
```php
// app/Infinri/Core/Model/Performance/Monitor.php
class Monitor
{
    public function logPageLoad(): void
    {
        $time = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
        $memory = memory_get_peak_usage(true);
        $queries = count($this->connection->getQueryLog());
        
        error_log(sprintf(
            "Performance: time=%.3fs memory=%s queries=%d url=%s",
            $time,
            Data::formatBytes($memory),
            $queries,
            $this->request->getPath()
        ));
    }
}
```

---

## Success Criteria

- ✅ All pages load <200ms (without cache)
- ✅ Cached pages load <10ms
- ✅ Grid renders <100ms (1000 rows)
- ✅ Layout compile <50ms
- ✅ Database queries <5 per page
- ✅ All tests passing (100%)
- ✅ No performance regressions
