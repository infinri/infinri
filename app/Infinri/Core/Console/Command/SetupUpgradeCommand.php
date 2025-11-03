<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Infinri\Core\Model\Setup\SchemaSetup;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Setup\Patch\PatchRegistry;
use Infinri\Core\Setup\Patch\PatchApplier;

/**
 * Like Magento's setup:upgrade - processes db_schema.xml files and applies data patches
 */
class SetupUpgradeCommand extends Command
{
    protected static string $defaultName = 'setup:upgrade';

    public function __construct(
        private readonly ?ModuleManager $moduleManager = null,
        private readonly ?SchemaSetup   $schemaSetup = null
    ) {
        parent::__construct();
    }

    /**
     * Configure command
     */
    protected function configure(): void
    {
        $this->setName('setup:upgrade')
            ->setDescription('Upgrade database schema and data (process db_schema.xml files and data patches)')
            ->setHelp('This command processes db_schema.xml files from all enabled modules and applies data patches.');
    }

    /**
     * Execute command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Running setup:upgrade');

        try {
            // Get dependencies if not injected (for standalone execution)
            if ($this->schemaSetup === null || $this->moduleManager === null) {
                $io->error('SchemaSetup or ModuleManager not available. Run from application context.');
                return Command::FAILURE;
            }

            // Step 1: Schema upgrades
            $io->section('Processing Database Schema');
            $io->text('Processing db_schema.xml files from enabled modules...');

            // Get enabled modules
            $enabledModules = $this->moduleManager->getEnabledModuleNames();

            if (empty($enabledModules)) {
                $io->warning('No enabled modules found.');
                return Command::FAILURE;
            }

            $io->text(sprintf('Found %d enabled modules', count($enabledModules)));

            // Process schema for each module
            $tablesCreated = 0;
            $tablesUpdated = 0;

            foreach ($enabledModules as $moduleName) {
                $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);

                if (!$moduleData) {
                    continue;
                }

                $schemaFile = $moduleData['path'] . '/etc/db_schema.xml';

                if (!file_exists($schemaFile)) {
                    $io->text("  - Skipping {$moduleName} (no db_schema.xml)");
                    continue;
                }

                $io->text("  - Processing {$moduleName}...");

                try {
                    // Check if file actually exists and is readable
                    if (!is_readable($schemaFile)) {
                        $io->warning("    Schema file not readable: {$schemaFile}");
                        continue;
                    }

                    $io->text("    Schema file: {$schemaFile}");
                    $result = $this->schemaSetup->processModuleSchema($moduleName, $schemaFile);
                    $tablesCreated += $result['created'] ?? 0;
                    $tablesUpdated += $result['updated'] ?? 0;
                    $io->text("    ✓ Created: {$result['created']}, Updated: {$result['updated']}");

                    // Show details if verbose
                    if ($output->isVerbose() && isset($result['details'])) {
                        $io->text("    Details: " . $result['details']);
                    }
                } catch (\Exception $e) {
                    $io->error("    ✗ Error processing {$moduleName}: " . $e->getMessage());
                    if ($output->isVerbose()) {
                        $io->text($e->getTraceAsString());
                    }
                }
            }

            $io->success(sprintf('Database schema updated: %d tables created, %d tables updated', $tablesCreated, $tablesUpdated));

            // Step 2: Apply Data Patches
            $io->section('Applying Data Patches');
            $this->applyDataPatches($io);

            $io->success('Setup upgrade completed successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Setup upgrade failed: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    /**
     * Apply data patches
     */
    private function applyDataPatches(SymfonyStyle $io): void
    {
        // Get database connection from SchemaSetup
        $connection = $this->schemaSetup->getPdo();

        $patchRegistry = new PatchRegistry();
        $patchApplier = new PatchApplier($connection);

        // Discover patches
        $io->text('Discovering data patches from all modules...');
        $patchRegistry->discoverPatches();
        $patches = $patchRegistry->getPatches();

        if (empty($patches)) {
            $io->text('  <comment>No data patches found</comment>');
            return;
        }

        $io->text(sprintf('  Found <info>%d</info> data patch(es)', count($patches)));

        // Sort by dependencies
        $sortedPatches = $patchRegistry->sortByDependencies($patches);

        // Apply patches
        $appliedCount = 0;
        $skippedCount = 0;

        foreach ($sortedPatches as $patchClass) {
            $shortName = substr($patchClass, strrpos($patchClass, '\\') + 1);

            if ($patchApplier->isApplied($patchClass)) {
                $io->text("  ⏭️  <comment>Already applied:</comment> {$shortName}");
                $skippedCount++;
            } else {
                $io->text("  🔧 <info>Applying:</info> {$shortName}...");
                try {
                    // Instantiate patch with PDO connection
                    $patch = new $patchClass($connection);
                    $patch->apply();
                    $patchApplier->markAsApplied($patchClass);
                    $io->text("     <info>✅ Done</info>");
                    $appliedCount++;
                } catch (\Exception $e) {
                    $io->error("     ❌ Failed: " . $e->getMessage());
                    throw $e;
                }
            }
        }

        $io->text('');
        $io->text(sprintf(
            'Data patches: <info>%d applied</info>, <comment>%d skipped</comment>',
            $appliedCount,
            $skippedCount
        ));
    }
}
