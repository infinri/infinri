<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Di;

/**
 * Manages plugins (interceptors) for Aspect-Oriented Programming (AOP)
 * Allows before/around/after method interception
 */
class PluginManager
{
    /**
     * Registered plugins
     *
     * @var array<string, array>
     */
    private array $plugins = [];

    /**
     * Plugin instances cache
     *
     * @var array<string, object>
     */
    private array $pluginInstances = [];

    /**
     * DI Container
     *
     * @var \DI\Container|null
     */
    private ?\DI\Container $container;

    /**
     * Constructor
     *
     * @param \DI\Container|null $container DI Container
     */
    public function __construct(?\DI\Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * Register a plugin
     *
     * @param string $className Target class name
     * @param string $pluginName Plugin identifier
     * @param string $pluginClass Plugin class name
     * @param int $sortOrder Execution order (lower = earlier)
     * @param array<string> $methods Methods to intercept (empty = all)
     * @return void
     */
    public function registerPlugin(
        string $className,
        string $pluginName,
        string $pluginClass,
        int    $sortOrder = 10,
        array  $methods = []
    ): void
    {
        if (!isset($this->plugins[$className])) {
            $this->plugins[$className] = [];
        }

        $this->plugins[$className][$pluginName] = [
            'class' => $pluginClass,
            'sortOrder' => $sortOrder,
            'methods' => $methods,
        ];

        // Sort by sortOrder
        uasort($this->plugins[$className], function ($a, $b) {
            return $a['sortOrder'] <=> $b['sortOrder'];
        });
    }

    /**
     * Get plugins for a class
     *
     * @param string $className Class name
     * @return array Plugins
     */
    public function getPlugins(string $className): array
    {
        return $this->plugins[$className] ?? [];
    }

    /**
     * Check if class has plugins
     *
     * @param string $className Class name
     * @return bool True if has plugins
     */
    public function hasPlugins(string $className): bool
    {
        return isset($this->plugins[$className]) && !empty($this->plugins[$className]);
    }

    /**
     * Execute before plugins
     *
     * @param object $subject Target object
     * @param string $method Method name
     * @param array<mixed> $arguments Method arguments
     * @return array<mixed> Modified arguments
     * @throws \Exception
     */
    public function executeBefore(object $subject, string $method, array $arguments): array
    {
        $className = get_class($subject);
        $plugins = $this->getPluginsForMethod($className, $method);

        foreach ($plugins as $pluginData) {
            $plugin = $this->getPluginInstance($pluginData['class']);
            $beforeMethod = 'before' . ucfirst($method);

            if (method_exists($plugin, $beforeMethod)) {
                $result = $plugin->$beforeMethod($subject, ...$arguments);

                // If before plugin returns array, use as new arguments
                if (is_array($result)) {
                    $arguments = $result;
                }
            }
        }

        return $arguments;
    }

    /**
     * Execute around plugins
     *
     * @param object $subject Target object
     * @param callable $proceed Original method
     * @param string $method Method name
     * @param array<mixed> $arguments Method arguments
     * @return mixed Result
     * @throws \Exception
     */
    public function executeAround(object $subject, callable $proceed, string $method, array $arguments): mixed
    {
        $className = get_class($subject);
        $plugins = $this->getPluginsForMethod($className, $method);

        if (empty($plugins)) {
            return $proceed(...$arguments);
        }

        // Build chain of around plugins
        $chain = $proceed;

        foreach (array_reverse($plugins) as $pluginData) {
            $plugin = $this->getPluginInstance($pluginData['class']);
            $aroundMethod = 'around' . ucfirst($method);

            if (method_exists($plugin, $aroundMethod)) {
                $chain = function (...$args) use ($plugin, $aroundMethod, $subject, $chain) {
                    return $plugin->$aroundMethod($subject, $chain, ...$args);
                };
            }
        }

        return $chain(...$arguments);
    }

    /**
     * Execute after plugins
     *
     * @param object $subject Target object
     * @param mixed $result Method result
     * @param string $method Method name
     * @param array<mixed> $arguments Method arguments
     * @return mixed Modified result
     * @throws \Exception
     */
    public function executeAfter(object $subject, mixed $result, string $method, array $arguments): mixed
    {
        $className = get_class($subject);
        $plugins = $this->getPluginsForMethod($className, $method);

        foreach ($plugins as $pluginData) {
            $plugin = $this->getPluginInstance($pluginData['class']);
            $afterMethod = 'after' . ucfirst($method);

            if (method_exists($plugin, $afterMethod)) {
                $result = $plugin->$afterMethod($subject, $result, ...$arguments);
            }
        }

        return $result;
    }

    /**
     * Get plugins for specific method
     *
     * @param string $className Class name
     * @param string $method Method name
     * @return array Plugins
     */
    private function getPluginsForMethod(string $className, string $method): array
    {
        $plugins = $this->getPlugins($className);
        $filtered = [];

        foreach ($plugins as $pluginName => $pluginData) {
            // If methods array is empty, plugin applies to all methods
            if (empty($pluginData['methods']) || in_array($method, $pluginData['methods'], true)) {
                $filtered[$pluginName] = $pluginData;
            }
        }

        return $filtered;
    }

    /**
     * Get plugin instance
     *
     * @param string $pluginClass Plugin class name
     * @return object Plugin instance
     * @throws \Exception If plugin class is not found
     */
    private function getPluginInstance(string $pluginClass): object
    {
        if (!isset($this->pluginInstances[$pluginClass])) {
            if ($this->container) {
                $this->pluginInstances[$pluginClass] = $this->container->get($pluginClass);
            } else {
                $this->pluginInstances[$pluginClass] = new $pluginClass();
            }
        }

        return $this->pluginInstances[$pluginClass];
    }

    /**
     * Clear all plugins
     *
     * @return void
     */
    public function clear(): void
    {
        $this->plugins = [];
        $this->pluginInstances = [];
    }
}
