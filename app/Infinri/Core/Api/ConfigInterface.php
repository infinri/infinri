<?php
declare(strict_types=1);

namespace Infinri\Core\Api;

/**
 * Provides access to system configuration values.
 */
interface ConfigInterface
{
    /**
     * Get configuration value by path
     *
     * @param string $path Configuration path (e.g., 'theme/general/logo')
     * @param mixed $default Default value if config not found
     * @return mixed
     */
    public function get(string $path, mixed $default = null): mixed;

    /**
     * Get configuration value with scope support
     *
     * @param string $path Configuration path
     * @param string $scope Scope type (default, website, store)
     * @param string|int|null $scopeCode Scope code
     * @return mixed
     */
    public function getValue(string $path, string $scope = 'default', string|int|null $scopeCode = null): mixed;

    /**
     * Check if configuration flag is set (boolean check)
     *
     * @param string $path Configuration path
     * @param string $scope Scope type
     * @param string|int|null $scopeCode Scope code
     * @return bool
     */
    public function isSetFlag(string $path, string $scope = 'default', string|int|null $scopeCode = null): bool;
}
