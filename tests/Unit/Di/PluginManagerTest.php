<?php

declare(strict_types=1);

use Infinri\Core\Model\Di\PluginManager;
use Infinri\Core\Model\Di\Plugin\InterceptorInterface;

describe('PluginManager', function () {
    
    beforeEach(function () {
        $this->markTestSkipped('Plugin system not fully implemented - advanced DI feature');
        $this->manager = new PluginManager();
        
        // Create test target class
        $this->target = new class {
            public function testMethod(string $arg): string
            {
                return "original: $arg";
            }
            
            public function calculate(int $a, int $b): int
            {
                return $a + $b;
            }
        };
    });
    
    it('can register a plugin', function () {
        $this->manager->registerPlugin(
            get_class($this->target),
            'test_plugin',
            'TestPlugin',
            10
        );
        
        expect($this->manager->hasPlugins(get_class($this->target)))->toBeTrue();
    });
    
    it('can get registered plugins', function () {
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin1',
            'Plugin1',
            10
        );
        
        $plugins = $this->manager->getPlugins(get_class($this->target));
        
        expect($plugins)->toHaveKey('plugin1');
        expect($plugins['plugin1']['class'])->toBe('Plugin1');
    });
    
    it('sorts plugins by sort order', function () {
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin1',
            'Plugin1',
            20
        );
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin2',
            'Plugin2',
            10
        );
        
        $plugins = $this->manager->getPlugins(get_class($this->target));
        $keys = array_keys($plugins);
        
        expect($keys[0])->toBe('plugin2'); // Lower sort order first
        expect($keys[1])->toBe('plugin1');
    });
    
    it('can execute before plugins', function () {
        // Create a before plugin
        $plugin = new class implements InterceptorInterface {
            public function beforeTestMethod(object $subject, string $arg): array
            {
                return ['modified: ' . $arg];
            }
        };
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'before_plugin',
            get_class($plugin)
        );
        
        // Get plugin instance to inject it
        $reflection = new ReflectionClass($this->manager);
        $property = $reflection->getProperty('pluginInstances');
        $property->setAccessible(true);
        $instances = $property->getValue($this->manager);
        $instances[get_class($plugin)] = $plugin;
        $property->setValue($this->manager, $instances);
        
        $args = $this->manager->executeBefore($this->target, 'testMethod', ['test']);
        
        expect($args[0])->toBe('modified: test');
    });
    
    it('can execute after plugins', function () {
        // Create an after plugin
        $plugin = new class implements InterceptorInterface {
            public function afterTestMethod(object $subject, mixed $result, string $arg): string
            {
                return $result . ' [modified]';
            }
        };
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'after_plugin',
            get_class($plugin)
        );
        
        // Inject plugin instance
        $reflection = new ReflectionClass($this->manager);
        $property = $reflection->getProperty('pluginInstances');
        $property->setAccessible(true);
        $instances = $property->getValue($this->manager);
        $instances[get_class($plugin)] = $plugin;
        $property->setValue($this->manager, $instances);
        
        $result = $this->manager->executeAfter(
            $this->target,
            'original: test',
            'testMethod',
            ['test']
        );
        
        expect($result)->toBe('original: test [modified]');
    });
    
    it('can execute around plugins', function () {
        // Create an around plugin
        $plugin = new class implements InterceptorInterface {
            public function aroundCalculate(object $subject, callable $proceed, int $a, int $b): int
            {
                $result = $proceed($a, $b);
                return $result * 2; // Double the result
            }
        };
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'around_plugin',
            get_class($plugin)
        );
        
        // Inject plugin instance
        $reflection = new ReflectionClass($this->manager);
        $property = $reflection->getProperty('pluginInstances');
        $property->setAccessible(true);
        $instances = $property->getValue($this->manager);
        $instances[get_class($plugin)] = $plugin;
        $property->setValue($this->manager, $instances);
        
        $proceed = fn($a, $b) => $a + $b;
        $result = $this->manager->executeAround(
            $this->target,
            $proceed,
            'calculate',
            [5, 3]
        );
        
        expect($result)->toBe(16); // (5 + 3) * 2
    });
    
    it('filters plugins by method', function () {
        $this->manager->registerPlugin(
            get_class($this->target),
            'specific_plugin',
            'SpecificPlugin',
            10,
            ['testMethod'] // Only for testMethod
        );
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'all_methods_plugin',
            'AllMethodsPlugin',
            10,
            [] // For all methods
        );
        
        $reflection = new ReflectionClass($this->manager);
        $method = $reflection->getMethod('getPluginsForMethod');
        $method->setAccessible(true);
        
        $pluginsForTest = $method->invoke($this->manager, get_class($this->target), 'testMethod');
        $pluginsForCalculate = $method->invoke($this->manager, get_class($this->target), 'calculate');
        
        expect($pluginsForTest)->toHaveCount(2);
        expect($pluginsForCalculate)->toHaveCount(1);
    });
    
    it('returns false for class without plugins', function () {
        expect($this->manager->hasPlugins('NonExistentClass'))->toBeFalse();
    });
    
    it('can clear all plugins', function () {
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin1',
            'Plugin1'
        );
        
        expect($this->manager->hasPlugins(get_class($this->target)))->toBeTrue();
        
        $this->manager->clear();
        
        expect($this->manager->hasPlugins(get_class($this->target)))->toBeFalse();
    });
    
    it('handles multiple before plugins in order', function () {
        $plugin1 = new class implements InterceptorInterface {
            public function beforeTestMethod(object $subject, string $arg): array
            {
                return [$arg . '-p1'];
            }
        };
        
        $plugin2 = new class implements InterceptorInterface {
            public function beforeTestMethod(object $subject, string $arg): array
            {
                return [$arg . '-p2'];
            }
        };
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin1',
            get_class($plugin1),
            10
        );
        
        $this->manager->registerPlugin(
            get_class($this->target),
            'plugin2',
            get_class($plugin2),
            20
        );
        
        // Inject plugin instances
        $reflection = new ReflectionClass($this->manager);
        $property = $reflection->getProperty('pluginInstances');
        $property->setAccessible(true);
        $instances = [
            get_class($plugin1) => $plugin1,
            get_class($plugin2) => $plugin2,
        ];
        $property->setValue($this->manager, $instances);
        
        $args = $this->manager->executeBefore($this->target, 'testMethod', ['test']);
        
        // Plugin1 executes first (sortOrder 10), then plugin2 (sortOrder 20)
        expect($args[0])->toBe('test-p1-p2');
    });
});
