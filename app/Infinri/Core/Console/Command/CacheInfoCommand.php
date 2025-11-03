<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infinri\Core\Model\Cache\Factory;
use Infinri\Core\Model\Cache\CacheConfig;

/**
 * Display cache configuration and performance information
 */
class CacheInfoCommand extends Command
{
    protected static string $defaultName = 'cache:info';

    public function __construct(
        private readonly ?Factory $cacheFactory = null
    ) {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('cache:info')
            ->setDescription('Display cache configuration and performance information')
            ->setHelp('Shows current cache adapter, availability, and performance metrics.');
    }

    /**
     * Execute command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Cache System Information');

        try {
            // Get cache factory or create one
            $factory = $this->cacheFactory ?? new Factory();

            // Display adapter information
            $this->displayAdapterInfo($io, $factory);

            // Display configuration
            $this->displayConfiguration($io);

            // Display performance metrics
            $this->displayPerformanceMetrics($io, $factory);

            // Display cache type status
            $this->displayCacheTypeStatus($io);

            $io->success('Cache information displayed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to retrieve cache information: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Display adapter information
     */
    private function displayAdapterInfo(SymfonyStyle $io, Factory $factory): void
    {
        $io->section('📊 Cache Adapter Status');

        $metrics = $factory->getAdapterMetrics();
        $optimal = CacheConfig::getOptimalAdapter();

        $rows = [
            ['Current Adapter', $metrics['adapter']],
            ['Optimal Adapter', $optimal],
            ['Fallback Used', $metrics['fallback_used'] ? '⚠️ Yes' : '✅ No'],
            ['Redis Available', $metrics['redis_available'] ? '✅ Yes' : '❌ No'],
            ['APCu Available', $metrics['apcu_available'] ? '✅ Yes' : '❌ No'],
            ['Active Instances', (string) $metrics['instance_count']],
        ];

        $io->table(['Property', 'Value'], $rows);

        if ($metrics['fallback_used']) {
            $io->warning([
                'Fallback adapter is being used!',
                "Optimal: {$optimal}, Current: {$metrics['adapter']}",
                'Check Redis/APCu configuration for better performance.'
            ]);
        }
    }

    /**
     * Display configuration details
     */
    private function displayConfiguration(SymfonyStyle $io): void
    {
        $io->section('⚙️ Configuration');

        $rows = [
            ['Environment', $_ENV['APP_ENV'] ?? 'development'],
            ['Cache Driver', $_ENV['CACHE_DRIVER'] ?? 'file'],
            ['Cache Prefix', CacheConfig::getCachePrefix()],
            ['Default TTL', CacheConfig::getDefaultTtl() . ' seconds'],
            ['Production Mode', CacheConfig::isProduction() ? '✅ Yes' : '❌ No'],
            ['CLI Mode', CacheConfig::isCliMode() ? '✅ Yes' : '❌ No'],
        ];

        // Add Redis configuration if available
        if (CacheConfig::isRedisAvailable()) {
            $redisConfig = CacheConfig::getRedisConfig();
            $rows[] = ['Redis Host', $redisConfig['host'] . ':' . $redisConfig['port']];
            $rows[] = ['Redis Database', (string) $redisConfig['database']];
            $rows[] = ['Redis Auth', !empty($redisConfig['password']) ? '✅ Yes' : '❌ No'];
        }

        $io->table(['Setting', 'Value'], $rows);
    }

    /**
     * Display performance metrics
     * @throws InvalidArgumentException
     */
    private function displayPerformanceMetrics(SymfonyStyle $io, Factory $factory): void
    {
        $io->section('⚡ Performance Metrics');

        // Test cache performance
        $testResults = $this->performCacheTest($factory);

        $rows = [
            ['Write Performance', $testResults['write_time'] . ' ms'],
            ['Read Performance', $testResults['read_time'] . ' ms'],
            ['Delete Performance', $testResults['delete_time'] . ' ms'],
            ['Memory Usage', $this->formatBytes(memory_get_usage(true))],
            ['Peak Memory', $this->formatBytes(memory_get_peak_usage(true))],
        ];

        $io->table(['Metric', 'Value'], $rows);

        // Performance recommendations
        if ($testResults['write_time'] > 10) {
            $io->note('Cache write performance is slow. Consider using Redis for better performance.');
        }
    }

    /**
     * Display cache type status
     */
    private function displayCacheTypeStatus(SymfonyStyle $io): void
    {
        $io->section('📋 Cache Type Status');

        $cacheTypes = ['config', 'layout', 'block_html', 'full_page', 'translation', 'asset'];
        $rows = [];

        foreach ($cacheTypes as $type) {
            $enabled = CacheConfig::isCacheTypeEnabled($type);
            $config = CacheConfig::getCacheTypeConfig($type);
            
            $rows[] = [
                ucfirst(str_replace('_', ' ', $type)),
                $enabled ? '✅ Enabled' : '❌ Disabled',
                $config['ttl'] . 's',
                $config['adapter']
            ];
        }

        $io->table(['Cache Type', 'Status', 'TTL', 'Adapter'], $rows);
    }

    /**
     * Perform simple cache performance test
     * @throws InvalidArgumentException
     */
    private function performCacheTest(Factory $factory): array
    {
        $cache = $factory->create('test_performance');
        $testKey = 'performance_test_' . time();
        $testValue = str_repeat('x', 1000); // 1KB test data

        // Test write performance
        $start = microtime(true);
        $cache->set($testKey, $testValue, 60);
        $writeTime = round((microtime(true) - $start) * 1000, 2);

        // Test read performance
        $start = microtime(true);
        $cache->get($testKey);
        $readTime = round((microtime(true) - $start) * 1000, 2);

        // Test delete performance
        $start = microtime(true);
        $cache->delete($testKey);
        $deleteTime = round((microtime(true) - $start) * 1000, 2);

        return [
            'write_time' => $writeTime,
            'read_time' => $readTime,
            'delete_time' => $deleteTime,
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
