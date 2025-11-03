<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Infinri\Core\Model\Cache\TypeList;

/**
 * Shows status of all cache types
 */
class CacheStatusCommand extends Command
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
        $this->setName('cache:status')
            ->setDescription('Show cache status')
            ->setHelp('Displays the status of all cache types (enabled/disabled).');
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

        $io->title('Cache Status');

        $table = new Table($output);
        $table->setHeaders(['Type', 'Label', 'Status', 'Description']);

        $types = $typeList->getTypes();

        foreach ($types as $type => $metadata) {
            $status = $typeList->isEnabled($type) ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>';

            $table->addRow([
                $type,
                $metadata['label'],
                $status,
                $metadata['description'],
            ]);
        }

        $table->render();

        $enabledCount = count($typeList->getEnabledTypes());
        $totalCount = count($types);

        $io->newLine();
        $io->text("Enabled: {$enabledCount}/{$totalCount} cache types");

        return Command::SUCCESS;
    }
}
