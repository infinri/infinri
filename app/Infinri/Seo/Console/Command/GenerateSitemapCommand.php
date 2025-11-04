<?php

declare(strict_types=1);

namespace Infinri\Seo\Console\Command;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Model\Config\ScopeConfig;
use Infinri\Core\Model\ObjectManager;
use Infinri\Seo\Service\SitemapGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Usage: php bin/console seo:sitemap:generate [--base-url=http://example.com].
 */
class GenerateSitemapCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('seo:sitemap:generate')
            ->setDescription('Generate static sitemap.xml file')
            ->setHelp('This command generates a static sitemap.xml file in the pub/ directory for better performance on large sites')
            ->addOption(
                'base-url',
                null,
                InputOption::VALUE_OPTIONAL,
                'Base URL for the sitemap (e.g., https://example.com). If not provided, reads from configuration.'
            );
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Generating static sitemap...</info>');

        try {
            // Get dependencies via ObjectManager
            $objectManager = ObjectManager::getInstance();
            /** @var PageRepository $pageRepository */
            $pageRepository = $objectManager->get(PageRepository::class);
            /** @var ScopeConfig $scopeConfig */
            $scopeConfig = $objectManager->get(ScopeConfig::class);

            $generator = new SitemapGenerator($pageRepository);

            // Get base URL from option or config
            $baseUrl = $input->getOption('base-url');
            if (! $baseUrl) {
                $baseUrl = $scopeConfig->getValue('general/site/base_url');
                if (! $baseUrl) {
                    $baseUrl = 'http://localhost';
                    $output->writeln('<comment>Warning: No base URL configured, using default: ' . $baseUrl . '</comment>');
                }
            }

            // Remove trailing slash
            $baseUrl = rtrim($baseUrl, '/');

            $output->writeln('Base URL: ' . $baseUrl);

            // Generate sitemap XML
            $xml = $generator->generate($baseUrl);

            // Write to pub/sitemap.xml
            $sitemapPath = \dirname(__DIR__, 5) . '/pub/sitemap.xml';
            $result = file_put_contents($sitemapPath, $xml);

            if (false === $result) {
                $output->writeln('<error>Failed to write sitemap to: ' . $sitemapPath . '</error>');

                return Command::FAILURE;
            }

            // Get page count from XML
            $xmlObj = simplexml_load_string($xml);
            $urlCount = \count($xmlObj->url ?? []);

            $output->writeln('');
            $output->writeln('<info>✓ Successfully generated sitemap!</info>');
            $output->writeln('  File: ' . $sitemapPath);
            $output->writeln('  URLs: ' . $urlCount);
            $output->writeln('  Size: ' . $this->formatBytes(\strlen($xml)));
            $output->writeln('');
            $output->writeln('<comment>Tip: Add this to your crontab for automatic updates:</comment>');
            $output->writeln('  0 2 * * * cd /path/to/project && php bin/console seo:sitemap:generate');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Error generating sitemap: ' . $e->getMessage() . '</error>');
            $output->writeln('<error>Stack trace:</error>');
            $output->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    /**
     * Format bytes to human-readable string.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }
}
