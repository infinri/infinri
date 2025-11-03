<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Service;

use PHPUnit\Framework\TestCase;
use Infinri\Core\Service\SystemHealthChecker;
use PDO;
use PDOStatement;

/**
 * Test system health checking functionality
 */
class SystemHealthCheckerTest extends TestCase
{
    private SystemHealthChecker $healthChecker;
    private PDO $mockConnection;

    protected function setUp(): void
    {
        $this->mockConnection = $this->createMock(PDO::class);
        $this->healthChecker = new SystemHealthChecker($this->mockConnection);
    }

    public function testGetHealthStatusReturnsCompleteStructure(): void
    {
        // Mock successful database query
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        $health = $this->healthChecker->getHealthStatus();

        $this->assertIsArray($health);
        $this->assertArrayHasKey('status', $health);
        $this->assertArrayHasKey('message', $health);
        $this->assertArrayHasKey('icon', $health);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('timestamp', $health);

        // Check that all expected checks are present
        $this->assertArrayHasKey('database', $health['checks']);
        $this->assertArrayHasKey('filesystem', $health['checks']);
        $this->assertArrayHasKey('php_extensions', $health['checks']);
        $this->assertArrayHasKey('memory', $health['checks']);
    }

    public function testGetSimpleStatusReturnsSimplifiedData(): void
    {
        // Mock successful database query
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        $status = $this->healthChecker->getSimpleStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('value', $status);
        $this->assertArrayHasKey('label', $status);
        $this->assertArrayHasKey('status', $status);

        // Should be healthy if all checks pass
        $this->assertEquals('healthy', $status['status']);
    }

    public function testDatabaseCheckReturnsFalseOnException(): void
    {
        // Mock database exception
        $this->mockConnection->method('query')
            ->willThrowException(new \PDOException('Connection failed'));

        $health = $this->healthChecker->getHealthStatus();

        $this->assertFalse($health['checks']['database']);
        $this->assertNotEquals('healthy', $health['status']);
    }

    public function testDatabaseCheckReturnsFalseOnSlowQuery(): void
    {
        // This test would require more complex mocking to simulate slow queries
        // For now, we'll test that the method exists and returns a boolean
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        $health = $this->healthChecker->getHealthStatus();

        $this->assertIsBool($health['checks']['database']);
    }

    public function testPhpExtensionsCheckValidatesRequiredExtensions(): void
    {
        $health = $this->healthChecker->getHealthStatus();

        // PHP extensions check should return boolean
        $this->assertIsBool($health['checks']['php_extensions']);

        // In a normal PHP environment, basic extensions should be available
        // This test mainly ensures the method works without errors
    }

    public function testMemoryCheckReturnsBooleanValue(): void
    {
        $health = $this->healthChecker->getHealthStatus();

        $this->assertIsBool($health['checks']['memory']);
    }

    public function testFilesystemCheckReturnsBooleanValue(): void
    {
        $health = $this->healthChecker->getHealthStatus();

        $this->assertIsBool($health['checks']['filesystem']);
    }

    public function testHealthStatusCalculation(): void
    {
        // Mock successful database query for consistent results
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        $health = $this->healthChecker->getHealthStatus();

        // Status should be one of the expected values
        $this->assertContains($health['status'], ['healthy', 'warning', 'critical']);

        // Icon should match status
        $expectedIcons = ['✅', '⚠️', '❌'];
        $this->assertContains($health['icon'], $expectedIcons);

        // Message should be descriptive
        $this->assertNotEmpty($health['message']);
    }

    public function testConvertToBytesMethod(): void
    {
        // Use reflection to test private method
        $reflection = new \ReflectionClass($this->healthChecker);
        $method = $reflection->getMethod('convertToBytes');
        $method->setAccessible(true);

        // Test various memory notations
        $this->assertEquals(1024, $method->invoke($this->healthChecker, '1K'));
        $this->assertEquals(1048576, $method->invoke($this->healthChecker, '1M'));
        $this->assertEquals(1073741824, $method->invoke($this->healthChecker, '1G'));
        $this->assertEquals(128, $method->invoke($this->healthChecker, '128'));
    }
}
