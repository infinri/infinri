<?php

declare(strict_types=1);

namespace Infinri\Core\Service;

use PDO;

/**
 * Provides system health monitoring and status checks
 */
class SystemHealthChecker
{
    public function __construct(
        private readonly PDO $connection
    ) {}

    /**
     * Check overall system health
     *
     * @return array Health status with details
     */
    public function getHealthStatus(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'filesystem' => $this->checkFilesystem(),
            'php_extensions' => $this->checkPhpExtensions(),
            'memory' => $this->checkMemoryUsage(),
        ];

        $overallHealth = $this->calculateOverallHealth($checks);

        return [
            'status' => $overallHealth['status'],
            'message' => $overallHealth['message'],
            'icon' => $overallHealth['icon'],
            'checks' => $checks,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Get simple health status for dashboard
     *
     * @return array Simple status for display
     */
    public function getSimpleStatus(): array
    {
        $health = $this->getHealthStatus();
        
        return [
            'value' => $health['icon'],
            'label' => $health['message'],
            'status' => $health['status']
        ];
    }

    /**
     * Check database connectivity and performance
     *
     * @return bool Database health status
     */
    private function checkDatabase(): bool
    {
        try {
            $start = microtime(true);
            $stmt = $this->connection->query('SELECT 1');
            $duration = (microtime(true) - $start) * 1000; // Convert to milliseconds

            if (!$stmt) {
                return false;
            }

            // Consider healthy if query takes less than 100ms
            return $duration < 100;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check filesystem permissions and disk space
     *
     * @return bool Filesystem health status
     */
    private function checkFilesystem(): bool
    {
        $paths = [
            dirname(__DIR__, 4) . '/var/cache',
            dirname(__DIR__, 4) . '/var/log',
            dirname(__DIR__, 4) . '/pub/media',
        ];

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                if (!mkdir($path, 0755, true)) {
                    return false;
                }
            }

            if (!is_writable($path)) {
                return false;
            }
        }

        // Check disk space (warn if less than 100MB free)
        $freeBytes = disk_free_space(dirname(__DIR__, 4));
        $minBytes = 100 * 1024 * 1024; // 100MB

        return $freeBytes === false ? true : $freeBytes > $minBytes;
    }

    /**
     * Check required PHP extensions
     *
     * @return bool PHP extensions status
     */
    private function checkPhpExtensions(): bool
    {
        $requiredExtensions = [
            'pdo',
            'pdo_pgsql',
            'mbstring',
            'json',
            'xml',
        ];

        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check memory usage
     *
     * @return bool Memory usage status
     */
    private function checkMemoryUsage(): bool
    {
        $memoryLimit = ini_get('memory_limit');
        $currentUsage = memory_get_usage(true);

        // Convert memory limit to bytes
        $limitBytes = $this->convertToBytes($memoryLimit);

        if ($limitBytes === -1) {
            return true; // No limit
        }

        // Consider healthy if using less than 80% of memory limit
        $usagePercentage = ($currentUsage / $limitBytes) * 100;
        return $usagePercentage < 80;
    }

    /**
     * Convert PHP memory notation to bytes
     *
     * @param string $value Memory value (e.g., "128M", "1G")
     * @return int Bytes
     */
    private function convertToBytes(string $value): int
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $number = (int) $value;

        return match ($last) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Calculate overall health from individual checks
     *
     * @param array $checks Individual health checks
     * @return array Overall health status
     */
    private function calculateOverallHealth(array $checks): array
    {
        $failedChecks = array_filter($checks, fn($check) => !$check);
        $failedCount = count($failedChecks);

        if ($failedCount === 0) {
            return [
                'status' => 'healthy',
                'message' => 'System Healthy',
                'icon' => '✅'
            ];
        }

        if ($failedCount === 1) {
            return [
                'status' => 'warning',
                'message' => 'Minor Issues',
                'icon' => '⚠️'
            ];
        }

        return [
            'status' => 'critical',
            'message' => 'System Issues',
            'icon' => '❌'
        ];
    }
}
