<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Cache;

/**
 * Manages different cache types used by the framework
 * Each cache type can be enabled/disabled and cleared independently
 */
class TypeList
{
    /**
     * Cache types
     */
    public const TYPE_CONFIG = 'config';
    public const TYPE_LAYOUT = 'layout';
    public const TYPE_BLOCK_HTML = 'block_html';
    public const TYPE_FULL_PAGE = 'full_page';
    public const TYPE_TRANSLATION = 'translation';
    public const TYPE_ASSET = 'asset';

    /**
     * Cache Factory
     *
     * @var Factory
     */
    private Factory $factory;

    /**
     * Enabled cache types
     *
     * @var array<string, bool>
     */
    private array $enabled = [];

    /**
     * Cache type metadata
     *
     * @var array<string, array>
     */
    private array $types = [];

    /**
     * Constructor
     *
     * @param Factory|null $factory Cache factory
     */
    public function __construct(?Factory $factory = null)
    {
        $this->factory = $factory ?? new Factory();
        $this->initializeTypes();
    }

    /**
     * Initialize cache types with metadata
     *
     * @return void
     */
    private function initializeTypes(): void
    {
        $this->types = [
            self::TYPE_CONFIG => [
                'label' => 'Configuration',
                'description' => 'System configuration, module settings',
                'enabled' => true,
            ],
            self::TYPE_LAYOUT => [
                'label' => 'Layout',
                'description' => 'Layout XML files, processed layouts',
                'enabled' => true,
            ],
            self::TYPE_BLOCK_HTML => [
                'label' => 'Block HTML',
                'description' => 'Rendered block HTML output',
                'enabled' => true,
            ],
            self::TYPE_FULL_PAGE => [
                'label' => 'Full Page',
                'description' => 'Complete page HTML output',
                'enabled' => false, // Disabled by default
            ],
            self::TYPE_TRANSLATION => [
                'label' => 'Translation',
                'description' => 'Translation strings',
                'enabled' => true,
            ],
            self::TYPE_ASSET => [
                'label' => 'Asset',
                'description' => 'Compiled CSS/JS assets',
                'enabled' => true,
            ],
        ];

        // Initialize enabled state
        foreach ($this->types as $type => $metadata) {
            $this->enabled[$type] = $metadata['enabled'];
        }
    }

    /**
     * Get cache pool for a type
     *
     * @param string $type Cache type
     * @return Pool|null Cache pool or null if type disabled
     */
    public function getCache(string $type): ?Pool
    {
        if (!$this->isEnabled($type)) {
            return null;
        }

        return $this->factory->create($type);
    }

    /**
     * Check if cache type is enabled
     *
     * @param string $type Cache type
     * @return bool True if enabled
     */
    public function isEnabled(string $type): bool
    {
        return $this->enabled[$type] ?? false;
    }

    /**
     * Enable a cache type
     *
     * @param string $type Cache type
     * @return void
     */
    public function enable(string $type): void
    {
        if (isset($this->types[$type])) {
            $this->enabled[$type] = true;
        }
    }

    /**
     * Disable a cache type
     *
     * @param string $type Cache type
     * @return void
     */
    public function disable(string $type): void
    {
        if (isset($this->types[$type])) {
            $this->enabled[$type] = false;
        }
    }

    /**
     * Clear cache for a specific type
     *
     * @param string $type Cache type
     * @return bool True on success
     */
    public function clear(string $type): bool
    {
        $cache = $this->factory->create($type);
        return $cache->clear();
    }

    /**
     * Clear all cache types
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->factory->clearAll();
    }

    /**
     * Get all cache types
     *
     * @return array Array of cache types with metadata
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * Get enabled cache types
     *
     * @return array Array of enabled cache type names
     */
    public function getEnabledTypes(): array
    {
        $enabled = [];

        foreach ($this->enabled as $type => $isEnabled) {
            if ($isEnabled) {
                $enabled[] = $type;
            }
        }

        return $enabled;
    }

    /**
     * Get cache type metadata
     *
     * @param string $type Cache type
     * @return array|null Metadata array or null if not found
     */
    public function getTypeMetadata(string $type): ?array
    {
        return $this->types[$type] ?? null;
    }

    /**
     * Check if cache type exists
     *
     * @param string $type Cache type
     * @return bool True if exists
     */
    public function hasType(string $type): bool
    {
        return isset($this->types[$type]);
    }
}
