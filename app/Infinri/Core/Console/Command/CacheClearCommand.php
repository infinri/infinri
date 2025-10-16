<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infinri\Core\Model\Cache\TypeList;

/**
 * Cache Clear Command
 * 
 * Clears cache for specific types or all cache
 */
class CacheClearCommand extends Command
{

    /**
     * Cache TypeList
     *
     * @var TypeList|null
     */
    private ?TypeList $typeList = null;

    /**
     * Constructor
     *
     * @param TypeList|null $typeList Cache TypeList
     */
    public function __construct(?TypeList $typeList = null)
    {
        parent::__construct();
        $this->typeList = $typeList;
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear cache')
            ->setHelp('Clears application cache. Use --type option to clear specific cache types.')
            ->addOption(
                'type',
                't',
                InputOption::VALUE_OPTIONAL,
                'Specific cache type to clear (config, layout, block_html, etc.)'
            );
    }

    /**
     * Execute command
     *
     * @param InputInterface $input Input
     * @param OutputInterface $output Output
     * @return int Exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $typeList = $this->typeList ?? new TypeList();

        $type = $input->getOption('type');

        if ($type) {
            // Clear specific cache type
            if (!$typeList->hasType($type)) {
                $io->error("Cache type '{$type}' does not exist.");
                $io->note('Available types: ' . implode(', ', array_keys($typeList->getTypes())));
                return Command::FAILURE;
            }

            $io->text("Clearing {$type} cache...");
            $typeList->clear($type);
            $io->success("Cache type '{$type}' cleared successfully!");
        } else {
            // Clear all cache
            $io->text('Clearing all cache...');
            $typeList->clearAll();
            $io->success('All cache cleared successfully!');
        }

        return Command::SUCCESS;
    }
}
