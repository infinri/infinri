<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Model\Cache;

use PHPUnit\Framework\TestCase;
use Infinri\Core\Model\Cache\Factory;
use Infinri\Core\Model\Cache\Pool;

/**
 * Test enhanced cache factory with Redis-first architecture
 */
class FactoryTest extends TestCase
{
    private Factory $factory;
    private string $testBasePath;

    protected function setUp(): void
    {
        $this->testBasePath = sys_get_temp_dir() . '/infinri_cache_test';
        $this->factory = new Factory($this->testBasePath);
        
        // Ensure test directory exists
        if (!is_dir($this->testBasePath)) {
            mkdir($this->testBasePath, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test cache directory
        if (is_dir($this->testBasePath)) {
            $this->removeDirectory($this->testBasePath);
        }
    }

    public function testFactoryCreatesPoolInstance(): void
    {
        $pool = $this->factory->create('test_namespace');
        
        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testFactoryReusesPoolInstances(): void
    {
        $pool1 = $this->factory->create('test_namespace');
        $pool2 = $this->factory->create('test_namespace');
        
        $this->assertSame($pool1, $pool2);
    }

    public function testFactoryCreatesDifferentPoolsForDifferentNamespaces(): void
    {
        $pool1 = $this->factory->create('namespace1');
        $pool2 = $this->factory->create('namespace2');
        
        $this->assertNotSame($pool1, $pool2);
    }

    public function testFactoryCreatesPoolWithCustomAdapter(): void
    {
        $pool = $this->factory->create('test', 'filesystem');
        
        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testFactoryCreatesPoolWithCustomTtl(): void
    {
        $pool = $this->factory->create('test', null, 1800);
        
        $this->assertInstanceOf(Pool::class, $pool);
    }

    public function testGetReturnsExistingPool(): void
    {
        $created = $this->factory->create('test_get');
        $retrieved = $this->factory->get('test_get');
        
        $this->assertSame($created, $retrieved);
    }

    public function testGetReturnsNullForNonExistentPool(): void
    {
        $retrieved = $this->factory->get('non_existent');
        
        $this->assertNull($retrieved);
    }

    public function testClearAllClearsAllPools(): void
    {
        $pool1 = $this->factory->create('test1');
        $pool2 = $this->factory->create('test2');
        
        // Add some test data
        $pool1->set('key1', 'value1');
        $pool2->set('key2', 'value2');
        
        $this->factory->clearAll();
        
        // Data should be cleared
        $this->assertNull($pool1->get('key1'));
        $this->assertNull($pool2->get('key2'));
    }

    public function testIsAdapterAvailableReturnsTrueForFilesystem(): void
    {
        $this->assertTrue($this->factory->isAdapterAvailable('filesystem'));
    }

    public function testIsAdapterAvailableReturnsTrueForArray(): void
    {
        $this->assertTrue($this->factory->isAdapterAvailable('array'));
    }

    public function testIsAdapterAvailableReturnsBooleanForRedis(): void
    {
        // Redis availability depends on extension and configuration
        $available = $this->factory->isAdapterAvailable('redis');
        $this->assertIsBool($available);
    }

    public function testIsAdapterAvailableReturnsFalseForInvalidAdapter(): void
    {
        $this->assertFalse($this->factory->isAdapterAvailable('invalid_adapter'));
    }

    public function testGetCurrentAdapterReturnsString(): void
    {
        $adapter = $this->factory->getCurrentAdapter();
        $this->assertIsString($adapter);
        $this->assertNotEmpty($adapter);
    }

    public function testGetAdapterMetricsReturnsCompleteArray(): void
    {
        $metrics = $this->factory->getAdapterMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('adapter', $metrics);
        $this->assertArrayHasKey('available', $metrics);
        $this->assertArrayHasKey('fallback_used', $metrics);
        $this->assertArrayHasKey('redis_available', $metrics);
        $this->assertArrayHasKey('apcu_available', $metrics);
        $this->assertArrayHasKey('instance_count', $metrics);
        
        $this->assertIsString($metrics['adapter']);
        $this->assertIsBool($metrics['available']);
        $this->assertIsBool($metrics['fallback_used']);
        $this->assertIsBool($metrics['redis_available']);
        $this->assertIsBool($metrics['apcu_available']);
        $this->assertIsInt($metrics['instance_count']);
    }

    public function testGetAdapterMetricsReflectsInstanceCount(): void
    {
        $initialMetrics = $this->factory->getAdapterMetrics();
        $initialCount = $initialMetrics['instance_count'];
        
        $this->factory->create('test_count1');
        $this->factory->create('test_count2');
        
        $newMetrics = $this->factory->getAdapterMetrics();
        
        $this->assertEquals($initialCount + 2, $newMetrics['instance_count']);
    }

    public function testSetDefaultAdapterChangesDefaultAdapter(): void
    {
        $originalAdapter = $this->factory->getCurrentAdapter();
        
        $this->factory->setDefaultAdapter('array');
        
        $this->assertEquals('array', $this->factory->getCurrentAdapter());
        $this->assertNotEquals($originalAdapter, $this->factory->getCurrentAdapter());
    }

    public function testFactoryHandlesInvalidAdapterGracefully(): void
    {
        // Should fallback to filesystem for invalid adapter
        $pool = $this->factory->create('test_invalid', 'invalid_adapter');
        
        $this->assertInstanceOf(Pool::class, $pool);
        
        // Should be able to use the pool normally
        $pool->set('test_key', 'test_value');
        $this->assertEquals('test_value', $pool->get('test_key'));
    }

    /**
     * Recursively remove directory and contents
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
