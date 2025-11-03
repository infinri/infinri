<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Model\Cache;

use PHPUnit\Framework\TestCase;
use Infinri\Core\Model\Cache\CacheConfig;

/**
 * Test cache configuration and adapter selection logic
 */
class CacheConfigTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear any existing environment variables for clean tests
        unset($_ENV['CACHE_DRIVER']);
        unset($_ENV['APP_ENV']);
        unset($_ENV['CACHE_PREFIX']);
        unset($_ENV['REDIS_HOST']);
    }

    public function testGetOptimalAdapterReturnsFilesystemWhenNoRedisOrApcu(): void
    {
        // Mock environment without Redis or APCu
        $_ENV['CACHE_DRIVER'] = 'file';
        
        $adapter = CacheConfig::getOptimalAdapter();
        
        $this->assertEquals('filesystem', $adapter);
    }

    public function testGetDefaultTtlReturnsProductionValues(): void
    {
        $_ENV['APP_ENV'] = 'production';
        
        $ttl = CacheConfig::getDefaultTtl();
        
        $this->assertEquals(3600, $ttl); // 1 hour for production
    }

    public function testGetDefaultTtlReturnsDevelopmentValues(): void
    {
        $_ENV['APP_ENV'] = 'development';
        
        $ttl = CacheConfig::getDefaultTtl();
        
        $this->assertEquals(300, $ttl); // 5 minutes for development
    }

    public function testGetCachePrefixReturnsConfiguredValue(): void
    {
        $_ENV['CACHE_PREFIX'] = 'test_prefix_';
        
        $prefix = CacheConfig::getCachePrefix();
        
        $this->assertEquals('test_prefix_', $prefix);
    }

    public function testGetCachePrefixReturnsDefaultValue(): void
    {
        $prefix = CacheConfig::getCachePrefix();
        
        $this->assertEquals('infinri_', $prefix);
    }

    public function testIsProductionReturnsTrueForProductionEnv(): void
    {
        $_ENV['APP_ENV'] = 'production';
        
        $this->assertTrue(CacheConfig::isProduction());
        
        $_ENV['APP_ENV'] = 'prod';
        
        $this->assertTrue(CacheConfig::isProduction());
    }

    public function testIsProductionReturnsFalseForDevelopmentEnv(): void
    {
        $_ENV['APP_ENV'] = 'development';
        
        $this->assertFalse(CacheConfig::isProduction());
        
        $_ENV['APP_ENV'] = 'dev';
        
        $this->assertFalse(CacheConfig::isProduction());
    }

    public function testIsCliModeDetectsCliCorrectly(): void
    {
        // This test runs in CLI mode, so should return true
        $this->assertTrue(CacheConfig::isCliMode());
    }

    public function testIsRedisAvailableReturnsFalseWhenExtensionNotLoaded(): void
    {
        // In test environment, Redis extension might not be loaded
        if (!extension_loaded('redis')) {
            $this->assertFalse(CacheConfig::isRedisAvailable());
        } else {
            // If Redis is loaded, test depends on configuration
            $this->assertIsBool(CacheConfig::isRedisAvailable());
        }
    }

    public function testIsApcuAvailableReturnsFalseInCliMode(): void
    {
        // APCu is not available in CLI mode
        $this->assertFalse(CacheConfig::isApcuAvailable());
    }

    public function testGetRedisConfigReturnsDefaultValues(): void
    {
        $config = CacheConfig::getRedisConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('host', $config);
        $this->assertArrayHasKey('port', $config);
        $this->assertArrayHasKey('password', $config);
        $this->assertArrayHasKey('database', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('read_timeout', $config);
        
        $this->assertEquals('localhost', $config['host']);
        $this->assertEquals(6379, $config['port']);
        $this->assertEquals(0, $config['database']);
    }

    public function testGetRedisConfigReturnsEnvironmentValues(): void
    {
        $_ENV['REDIS_HOST'] = 'redis.example.com';
        $_ENV['REDIS_PORT'] = '6380';
        $_ENV['REDIS_PASSWORD'] = 'secret';
        $_ENV['REDIS_DATABASE'] = '2';
        
        $config = CacheConfig::getRedisConfig();
        
        $this->assertEquals('redis.example.com', $config['host']);
        $this->assertEquals(6380, $config['port']);
        $this->assertEquals('secret', $config['password']);
        $this->assertEquals(2, $config['database']);
    }

    public function testGetCacheTypeConfigReturnsCorrectStructure(): void
    {
        $config = CacheConfig::getCacheTypeConfig('test');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('adapter', $config);
        $this->assertArrayHasKey('ttl', $config);
        $this->assertArrayHasKey('prefix', $config);
        
        $this->assertStringContains('test_', $config['prefix']);
    }

    public function testGetCacheTypeConfigAppliesTtlOverrides(): void
    {
        $_ENV['APP_ENV'] = 'production';
        
        $configConfig = CacheConfig::getCacheTypeConfig('config');
        $layoutConfig = CacheConfig::getCacheTypeConfig('layout');
        $assetConfig = CacheConfig::getCacheTypeConfig('asset');
        
        $this->assertEquals(7200, $configConfig['ttl']); // 2 hours
        $this->assertEquals(3600, $layoutConfig['ttl']); // 1 hour
        $this->assertEquals(86400, $assetConfig['ttl']); // 24 hours
    }

    public function testIsCacheTypeEnabledReturnsCorrectValues(): void
    {
        $_ENV['CACHE_CONFIG_ENABLED'] = 'true';
        $_ENV['CACHE_LAYOUT_ENABLED'] = 'false';
        
        $this->assertTrue(CacheConfig::isCacheTypeEnabled('config'));
        $this->assertFalse(CacheConfig::isCacheTypeEnabled('layout'));
    }

    public function testIsCacheTypeEnabledRespectsDevDisableCache(): void
    {
        $_ENV['APP_ENV'] = 'development';
        $_ENV['DEV_DISABLE_CACHE'] = 'true';
        $_ENV['CACHE_CONFIG_ENABLED'] = 'true';
        
        $this->assertFalse(CacheConfig::isCacheTypeEnabled('config'));
    }

    public function testIsCacheTypeEnabledIgnoresDevDisableCacheInProduction(): void
    {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DEV_DISABLE_CACHE'] = 'true';
        $_ENV['CACHE_CONFIG_ENABLED'] = 'true';
        
        $this->assertTrue(CacheConfig::isCacheTypeEnabled('config'));
    }
}
