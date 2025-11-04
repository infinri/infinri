<?php

declare(strict_types=1);

namespace Infinri\Seo\Console\Command;

use Infinri\Cms\Model\Repository\PageRepository;
use Infinri\Core\Model\ObjectManager;
use Infinri\Seo\Service\UrlRewriteGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Usage: php bin/console seo:urlrewrite:regenerate.
 */
class RegenerateUrlRewritesCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('seo:urlrewrite:regenerate')
            ->setDescription('Regenerate URL rewrites for all CMS pages')
            ->setHelp('This command regenerates URL rewrites for all existing CMS pages');
    }

    /**
     * Execute the command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Regenerating URL rewrites...</info>');

        // Get dependencies via ObjectManager
        $objectManager = ObjectManager::getInstance();
        $pageRepository = $objectManager->get(PageRepository::class);
        $urlRewriteGenerator = $objectManager->get(UrlRewriteGenerator::class);

        $pages = $pageRepository->getAll();
        $output->writeln(\sprintf('Found %d pages', \count($pages)));

        $count = 0;
        foreach ($pages as $page) {
            try {
                $pageId = $page->getPageId();
                $urlKey = $page->getUrlKey();

                if (null === $pageId || null === $urlKey) {
                    $output->writeln(\sprintf(
                        '  <error>✗</error> Skipped: %s (missing ID or URL key)',
                        $page->getTitle() ?? 'Unknown'
                    ));
                    continue;
                }

                $urlRewriteGenerator->generateForCmsPage($pageId, $urlKey);
                $count++;
                $output->writeln(\sprintf(
                    '  <comment>✓</comment> Generated: %s → /%s',
                    $page->getTitle(),
                    $page->getUrlKey()
                ));
            } catch (\Exception $e) {
                $output->writeln(\sprintf(
                    '  <error>✗</error> Failed: %s (%s)',
                    $page->getTitle(),
                    $e->getMessage()
                ));
            }
        }

        $output->writeln('');
        $output->writeln(\sprintf(
            '<info>Successfully generated %d URL rewrites</info>',
            $count
        ));

        return Command::SUCCESS;
    }
}
