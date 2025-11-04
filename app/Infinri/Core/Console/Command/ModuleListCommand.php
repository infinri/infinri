<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Module\ModuleReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Lists all registered modules.
 */
class ModuleListCommand extends Command
{
    /**
     * Module Manager.
     */
    private ?ModuleManager $moduleManager = null;

    /**
     * Constructor.
     *
     * @param ModuleManager|null $moduleManager Module Manager
     */
    public function __construct(?ModuleManager $moduleManager = null)
    {
        parent::__construct();
        $this->moduleManager = $moduleManager;
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this
            ->setName('module:list')
            ->setDescription('List all modules')
            ->setHelp('Displays a list of all registered modules with their status.');
    }

    /**
     * Execute command.
     *
     * @param InputInterface  $input  Input
     * @param OutputInterface $output Output
     *
     * @return int Exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null === $this->moduleManager) {
            $registrar = ComponentRegistrar::getInstance();
            $moduleReader = new ModuleReader();
            $moduleList = new ModuleList($registrar, $moduleReader);
            $this->moduleManager = new ModuleManager($moduleList);
        }

        $io->title('Registered Modules');

        $table = new Table($output);
        $table->setHeaders(['Module', 'Version', 'Status', 'Dependencies']);

        $modules = $this->moduleManager->getModuleList()->getAll();

        foreach ($modules as $moduleName => $moduleData) {
            $status = $this->moduleManager->isEnabled($moduleName)
                ? '<fg=green>Enabled</>'
                : '<fg=red>Disabled</>';

            $dependencies = isset($moduleData['sequence']) && ! empty($moduleData['sequence'])
                ? implode(', ', $moduleData['sequence'])
                : '<fg=yellow>None</>';

            $version = $moduleData['version'] ?? '1.0.0';

            $table->addRow([
                $moduleName,
                $version,
                $status,
                $dependencies,
            ]);
        }

        $table->render();

        $enabledCount = \count($this->moduleManager->getEnabledModules());
        $totalCount = \count($modules);

        $io->newLine();
        $io->text("Total modules: {$totalCount}");
        $io->text("Enabled: {$enabledCount}");
        $io->text('Disabled: ' . ($totalCount - $enabledCount));

        return Command::SUCCESS;
    }
}
