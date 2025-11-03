<?php
declare(strict_types=1);

namespace Infinri\Core\Model;

use Exception;
use InvalidArgumentException;
use Infinri\Core\Api\ComponentRegistrarInterface;

/**
 * Singleton registry for all components (modules, themes, libraries, languages).
 * Components register themselves by calling ComponentRegistrar::register() in their registration.php files.
 */
class ComponentRegistrar implements ComponentRegistrarInterface
{
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var array<string, array<string, string>> Registered components storage
     * Format: [type => [name => path]]
     */
    private array $paths;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct()
    {
        $this->paths = [
            self::MODULE => [],
            self::THEME => [],
            self::LIBRARY => [],
            self::LANGUAGE => [],
        ];
    }

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Static helper for registration (matches Magento pattern)
     *
     * @param string $type Component type
     * @param string $name Component name
     * @param string $path Absolute path to component directory
     * @return void
     */
    public static function register(string $type, string $name, string $path): void
    {
        self::getInstance()->registerComponent($type, $name, $path);
    }

    /**
     * Register a component
     *
     * @param string $type Component type
     * @param string $name Component name
     * @param string $path Absolute path to component directory
     * @return void
     * @throws InvalidArgumentException If type is invalid
     */
    public function registerComponent(string $type, string $name, string $path): void
    {
        if (!isset($this->paths[$type])) {
            throw new InvalidArgumentException(
                sprintf('Invalid component type: %s. Must be one of: %s',
                    $type,
                    implode(', ', [self::MODULE, self::THEME, self::LIBRARY, self::LANGUAGE])
                )
            );
        }

        // Normalize path (remove trailing slashes)
        $path = rtrim($path, '/\\');

        // Validate path exists
        if (!is_dir($path)) {
            throw new InvalidArgumentException(
                sprintf('Component path does not exist: %s', $path)
            );
        }

        // Register component
        $this->paths[$type][$name] = $path;
    }

    /**
     * @inheritDoc
     */
    public function getPaths(string $type): array
    {
        return $this->paths[$type] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getPath(string $type, string $name): ?string
    {
        return $this->paths[$type][$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function isRegistered(string $type, string $name): bool
    {
        return isset($this->paths[$type][$name]);
    }

    /**
     * Get all registered components (all types)
     *
     * @return array<string, array<string, string>>
     */
    public function getAllPaths(): array
    {
        return $this->paths;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     *
     * @return void
     * @throws Exception Always throws to prevent unserialization
     */
    public function __wakeup(): void
    {
        throw new Exception('Cannot unserialize singleton');
    }
}
