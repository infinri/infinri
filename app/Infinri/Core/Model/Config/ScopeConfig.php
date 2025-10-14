<?php
declare(strict_types=1);

namespace Infinri\Core\Model\Config;

use Infinri\Core\Api\ConfigInterface;

/**
 * Scope Configuration
 * 
 * Provides access to configuration values with scope support (default, website, store).
 */
class ScopeConfig implements ConfigInterface
{
    /**
     * Scope types
     */
    public const SCOPE_DEFAULT = 'default';
    public const SCOPE_WEBSITE = 'website';
    public const SCOPE_STORE = 'store';

    /**
     * @var array<string, mixed>|null Cached configuration
     */
    private ?array $config = null;

    public function __construct(
        private readonly Loader $configLoader
    ) {
    }

    /**
     * @inheritDoc
     */
    public function get(string $path, mixed $default = null): mixed
    {
        return $this->getValue($path, self::SCOPE_DEFAULT, null) ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function getValue(string $path, string $scope = self::SCOPE_DEFAULT, string|int|null $scopeCode = null): mixed
    {
        if ($this->config === null) {
            $this->config = $this->configLoader->load();
        }

        // Navigate through the path
        $value = $this->getValueByPath($this->config, $scope, $path);

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function isSetFlag(string $path, string $scope = self::SCOPE_DEFAULT, string|int|null $scopeCode = null): bool
    {
        $value = $this->getValue($path, $scope, $scopeCode);

        // Convert to boolean
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    /**
     * Get value by navigating through config path
     *
     * @param array<string, mixed> $config
     * @param string $scope
     * @param string $path
     * @return mixed
     */
    private function getValueByPath(array $config, string $scope, string $path): mixed
    {
        // Get scope config
        if (!isset($config[$scope])) {
            return null;
        }

        $scopeConfig = $config[$scope];

        // Split path into parts (e.g., "theme/general/logo" -> ["theme", "general", "logo"])
        $parts = explode('/', $path);

        // Navigate through the array
        $current = $scopeConfig;
        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * Clear cached configuration
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->config = null;
    }

    /**
     * Get all configuration for a scope
     *
     * @param string $scope
     * @return array<string, mixed>
     */
    public function getAllByScope(string $scope = self::SCOPE_DEFAULT): array
    {
        if ($this->config === null) {
            $this->config = $this->configLoader->load();
        }

        return $this->config[$scope] ?? [];
    }
}
