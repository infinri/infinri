<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Module\ModuleList;
use Infinri\Core\Model\ComponentRegistrar;
use Infinri\Core\Model\Module\ModuleReader;

/**
 * Shows detailed status of a specific module
 */
class ModuleStatusCommand extends Command
{

    /**
     * Module Manager
     *
     * @var ModuleManager|null
     */
    private ?ModuleManager $moduleManager = null;

    /**
     * Constructor
     *
     * @param ModuleManager|null $moduleManager Module Manager
     */
    public function __construct(?ModuleManager $moduleManager = null)
    {
        parent::__construct();
        $this->moduleManager = $moduleManager;
    }

    /**
     * Configure command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('module:status')
            ->setDescription('Show module status')
            ->setHelp('Displays detailed information about a specific module.')
            ->addArgument(
                'module',
                InputArgument::REQUIRED,
                'Module name (e.g., Infinri_Core)'
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

        if ($this->moduleManager === null) {
            $registrar = ComponentRegistrar::getInstance();
            $moduleReader = new ModuleReader();
            $moduleList = new ModuleList($registrar, $moduleReader);
            $this->moduleManager = new ModuleManager($moduleList);
        }

        $moduleName = $input->getArgument('module');
        $module = $this->moduleManager->getModuleList()->getOne($moduleName);

        if ($module === null) {
            $io->error("Module '{$moduleName}' not found.");
            return Command::FAILURE;
        }

        $io->title("Module: {$moduleName}");

        $io->definitionList(
            ['Name' => $moduleName],
            ['Version' => $module['version'] ?? '1.0.0'],
            ['Status' => $this->moduleManager->isEnabled($moduleName) ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>'],
            ['Path' => $module['path'] ?? 'N/A'],
        );

        if (isset($module['sequence']) && !empty($module['sequence'])) {
            $io->section('Dependencies');
            $io->listing($module['sequence']);
        } else {
            $io->section('Dependencies');
            $io->text('<fg=yellow>No dependencies</>');
        }

        return Command::SUCCESS;
    }
}
