<?php

declare(strict_types=1);

namespace Infinri\Core\Model\Di;

use DI\ContainerBuilder;
use Infinri\Core\Model\Module\ModuleManager;
use Psr\Container\ContainerInterface;

/**
 * Builds and configures the PHP-DI container by loading di.xml from all enabled modules.
 */
class ContainerFactory
{
    public function __construct(
        private readonly ModuleManager $moduleManager,
        private readonly XmlReader $xmlReader
    ) {
    }

    /**
     * Create and configure the DI container.
     *
     * @param bool $useCache Whether to use compiled container cache
     *
     * @throws \Exception
     */
    public function create(bool $useCache = false): ContainerInterface
    {
        $builder = new ContainerBuilder();

        // Enable compilation for production
        if ($useCache) {
            $cacheDir = __DIR__ . '/../../../../../var/cache/di';
            $proxiesDir = $cacheDir . '/proxies';

            // Ensure cache directories exist
            if (! is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            if (! is_dir($proxiesDir)) {
                mkdir($proxiesDir, 0755, true);
            }

            $builder->enableCompilation($cacheDir);
            $builder->writeProxiesToFile(true, $proxiesDir);
        }

        // Load DI definitions from all modules
        $definitions = $this->loadDefinitions();

        $builder->addDefinitions($definitions);

        return $builder->build();
    }

    /**
     * Load DI definitions from all enabled modules.
     *
     * @return array<string, mixed>
     */
    private function loadDefinitions(): array
    {
        $definitions = [];

        // Get modules in dependency order
        $modules = $this->moduleManager->getModulesInOrder();

        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);

            if (! $moduleData || ! isset($moduleData['path'])) {
                continue;
            }

            $diConfig = $this->xmlReader->read($moduleData['path']);

            if (null === $diConfig) {
                continue;
            }

            // Merge definitions
            $definitions = $this->mergeDefinitions($definitions, $diConfig);
        }

        return $this->convertToPhpDiDefinitions($definitions);
    }

    /**
     * Merge DI configurations.
     *
     * @param array<string, mixed> $base
     * @param array<string, mixed> $new
     *
     * @return array<string, mixed>
     */
    private function mergeDefinitions(array $base, array $new): array
    {
        // Merge preferences
        if (isset($new['preferences'])) {
            $base['preferences'] = array_merge(
                $base['preferences'] ?? [],
                $new['preferences']
            );
        }

        // Merge type configurations
        if (isset($new['types'])) {
            foreach ($new['types'] as $typeName => $typeConfig) {
                if (isset($base['types'][$typeName])) {
                    // Merge with existing type config
                    $base['types'][$typeName] = array_merge_recursive(
                        $base['types'][$typeName],
                        $typeConfig
                    );
                } else {
                    $base['types'][$typeName] = $typeConfig;
                }
            }
        }

        // Merge virtual types
        if (isset($new['virtualTypes'])) {
            $base['virtualTypes'] = array_merge(
                $base['virtualTypes'] ?? [],
                $new['virtualTypes']
            );
        }

        return $base;
    }

    /**
     * Convert XML definitions to PHP-DI format.
     *
     * @param array<string, mixed> $definitions
     *
     * @return array<string, mixed>
     */
    private function convertToPhpDiDefinitions(array $definitions): array
    {
        $phpDiDefinitions = [];

        // Add singleton factories for classes with getInstance()
        $phpDiDefinitions['Infinri\Core\Model\ComponentRegistrar'] = \DI\factory(function () {
            return \Infinri\Core\Model\ComponentRegistrar::getInstance();
        });

        // Convert preferences (interface -> implementation)
        if (isset($definitions['preferences'])) {
            foreach ($definitions['preferences'] as $interface => $implementation) {
                $phpDiDefinitions[$interface] = \DI\get($implementation);
            }
        }

        // Convert type configurations
        if (isset($definitions['types'])) {
            foreach ($definitions['types'] as $typeName => $typeConfig) {
                if (! empty($typeConfig['arguments'])) {
                    $autowire = \DI\autowire($typeName);

                    // Set each constructor parameter individually
                    foreach ($this->convertArguments($typeConfig['arguments']) as $paramName => $paramValue) {
                        $autowire = $autowire->constructorParameter($paramName, $paramValue);
                    }

                    $phpDiDefinitions[$typeName] = $autowire;
                }
            }
        }

        return $phpDiDefinitions;
    }

    /**
     * Convert XML arguments to PHP-DI parameter format.
     *
     * @param array<string, array<string, mixed>> $arguments
     *
     * @return array<string, mixed>
     */
    private function convertArguments(array $arguments): array
    {
        $converted = [];

        foreach ($arguments as $name => $argument) {
            $type = $argument['type'];
            $value = $argument['value'];

            if ('object' === $type) {
                // Object references should use DI\get() - must use full namespace to avoid conflicts
                $converted[$name] = \DI\get($value);
            } else {
                // Literal values
                $converted[$name] = $value;
            }
        }

        return $converted;
    }
}
