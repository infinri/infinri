<?php

declare(strict_types=1);

namespace Infinri\Core\Model;

use Psr\Container\ContainerInterface;

/**
 * This is a facade over PHP-DI container.
 */
class ObjectManager
{
    /**
     * @var self|null Singleton instance
     */
    private static ?self $instance = null;

    /**
     * @var ContainerInterface DI Container
     */
    private ContainerInterface $container;

    /**
     * Private constructor for singleton.
     */
    private function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get singleton instance.
     *
     * @throws \RuntimeException If not configured yet
     */
    public static function getInstance(): self
    {
        if (null === self::$instance) {
            throw new \RuntimeException('ObjectManager not configured. Call setInstance() first.');
        }

        return self::$instance;
    }

    /**
     * Set the singleton instance.
     */
    public static function setInstance(ContainerInterface $container): self
    {
        self::$instance = new self($container);

        return self::$instance;
    }

    /**
     * Reset singleton instance (for testing).
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Get object from container (singleton).
     *
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws \Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface If class not found
     */
    public function get(string $className): object
    {
        return $this->container->get($className);
    }

    /**
     * Create new instance (non-singleton).
     *
     * @template T of object
     *
     * @param class-string<T>      $className
     * @param array<string, mixed> $arguments Constructor arguments
     *
     * @return T
     *
     * @throws \Psr\Container\NotFoundExceptionInterface|\Psr\Container\ContainerExceptionInterface If class not found
     */
    public function create(string $className, array $arguments = []): object
    {
        if (empty($arguments)) {
            // Use container's make if no arguments
            if (method_exists($this->container, 'make')) {
                return $this->container->make($className);
            }

            // Fallback to get (which may return singleton)
            return $this->container->get($className);
        }

        // Create with specific arguments
        return new $className(...$arguments);
    }

    /**
     * Check if container has a definition for the class.
     */
    public function has(string $className): bool
    {
        return $this->container->has($className);
    }

    /**
     * Get the underlying container.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
