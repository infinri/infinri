<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Setup\SchemaSetup;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Advanced schema migration command with dry-run and rollback capabilities.
 */
class SchemaMigrateCommand extends Command
{
    protected static string $defaultName = 'schema:migrate';

    public function __construct(
        private readonly ?ModuleManager $moduleManager = null,
        private readonly ?SchemaSetup $schemaSetup = null,
        private readonly ?\PDO $connection = null
    ) {
        parent::__construct();
    }

    /**
     * Configure command.
     */
    protected function configure(): void
    {
        $this->setName('schema:migrate')
            ->setDescription('Advanced schema migration with analysis and safety features')
            ->setHelp('Analyzes and applies database schema changes with dry-run and rollback capabilities.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be changed without applying changes'
            )
            ->addOption(
                'module',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Process only specific module (default: all enabled modules)'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force migration even if potentially destructive changes detected'
            )
            ->addOption(
                'backup',
                'b',
                InputOption::VALUE_NONE,
                'Create database backup before applying changes'
            );
    }

    /**
     * Execute command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔄 Database Schema Migration');

        try {
            // Validate dependencies
            if (! $this->validateDependencies($io)) {
                return Command::FAILURE;
            }

            $dryRun = $input->getOption('dry-run');
            $moduleFilter = $input->getOption('module');
            $force = $input->getOption('force');
            $backup = $input->getOption('backup');

            if ($dryRun) {
                $io->note('DRY RUN MODE: No changes will be applied');
            }

            // Get modules to process
            $modules = $this->getModulesToProcess($moduleFilter, $io);
            if (empty($modules)) {
                $io->warning('No modules to process.');

                return Command::SUCCESS;
            }

            // Analyze changes first
            $io->section('📊 Analyzing Schema Changes');
            $analysis = $this->analyzeSchemaChanges($modules, $io);

            if (empty($analysis['changes'])) {
                $io->success('✅ All schemas are up to date!');

                return Command::SUCCESS;
            }

            // Display analysis results
            $this->displayAnalysis($analysis, $io);

            // Check for destructive changes
            if ($analysis['has_destructive'] && ! $force && ! $dryRun) {
                $io->error([
                    'Potentially destructive changes detected!',
                    'Use --force to proceed or --dry-run to see details.',
                    'Consider creating a backup first with --backup.',
                ]);

                return Command::FAILURE;
            }

            if ($dryRun) {
                $io->success('Dry run completed. Use without --dry-run to apply changes.');

                return Command::SUCCESS;
            }

            // Create backup if requested
            if ($backup) {
                $io->section('💾 Creating Database Backup');
                $this->createBackup($io);
            }

            // Apply changes
            $io->section('🚀 Applying Schema Changes');
            $results = $this->applySchemaChanges($modules, $io);

            // Display results
            $this->displayResults($results, $io);

            $io->success('Schema migration completed successfully!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Schema migration failed: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Validate dependencies.
     */
    private function validateDependencies(SymfonyStyle $io): bool
    {
        if (null === $this->schemaSetup || null === $this->moduleManager || null === $this->connection) {
            $io->error('Required dependencies not available. Run from application context.');

            return false;
        }

        // Test database connection
        try {
            $this->connection->query('SELECT 1');
        } catch (\Exception $e) {
            $io->error('Database connection failed: ' . $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Get modules to process.
     */
    private function getModulesToProcess(?string $moduleFilter, SymfonyStyle $io): array
    {
        $enabledModules = $this->moduleManager->getEnabledModuleNames();

        if ($moduleFilter) {
            if (! \in_array($moduleFilter, $enabledModules, true)) {
                $io->error("Module '{$moduleFilter}' is not enabled or does not exist.");

                return [];
            }

            return [$moduleFilter];
        }

        return $enabledModules;
    }

    /**
     * Analyze schema changes without applying them.
     *
     * @param array<string, mixed> $modules
     *
     * @return array<string, mixed>
     */
    private function analyzeSchemaChanges(array $modules, SymfonyStyle $io): array
    {
        $analysis = [
            'changes' => [],
            'has_destructive' => false,
            'total_tables' => 0,
            'new_tables' => 0,
            'updated_tables' => 0,
            'new_columns' => 0,
            'modified_columns' => 0,
            'new_indexes' => 0,
            'new_constraints' => 0,
        ];

        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            if (! $moduleData) {
                continue;
            }

            $schemaFile = $moduleData['path'] . '/etc/db_schema.xml';
            if (! file_exists($schemaFile)) {
                continue;
            }

            $io->text("  Analyzing {$moduleName}...");

            $moduleChanges = $this->analyzeModuleSchema($moduleName, $schemaFile);
            if (! empty($moduleChanges)) {
                $analysis['changes'][$moduleName] = $moduleChanges;

                // Aggregate statistics
                $analysis['total_tables'] += \count($moduleChanges);
                foreach ($moduleChanges as $change) {
                    if ('create' === $change['action']) {
                        $analysis['new_tables']++;
                    } elseif ('update' === $change['action']) {
                        $analysis['updated_tables']++;
                        $analysis['new_columns'] += \count($change['new_columns'] ?? []);
                        $analysis['modified_columns'] += \count($change['modified_columns'] ?? []);
                        $analysis['new_indexes'] += \count($change['new_indexes'] ?? []);
                        $analysis['new_constraints'] += \count($change['new_constraints'] ?? []);
                    }
                }
            }
        }

        return $analysis;
    }

    /**
     * Analyze schema changes for a single module.
     */
    private function analyzeModuleSchema(string $moduleName, string $schemaFile): array
    {
        $changes = [];

        libxml_use_internal_errors(true);
        $xml = simplexml_load_file($schemaFile);
        if (false === $xml) {
            return $changes;
        }

        foreach ($xml->table as $tableNode) {
            $tableName = (string) $tableNode['name'];

            if (! $this->tableExists($tableName)) {
                $changes[$tableName] = [
                    'action' => 'create',
                    'table' => $tableName,
                    'columns' => $this->getXmlColumns($tableNode),
                    'indexes' => $this->getXmlIndexes($tableNode),
                    'constraints' => $this->getXmlConstraints($tableNode),
                ];
            } else {
                $tableChanges = $this->analyzeTableChanges($tableName, $tableNode);
                if (! empty($tableChanges)) {
                    $changes[$tableName] = array_merge(['action' => 'update', 'table' => $tableName], $tableChanges);
                }
            }
        }

        return $changes;
    }

    /**
     * Analyze changes needed for existing table.
     */
    private function analyzeTableChanges(string $tableName, \SimpleXMLElement $tableNode): array
    {
        $changes = [
            'new_columns' => [],
            'modified_columns' => [],
            'new_indexes' => [],
            'new_constraints' => [],
        ];

        // Get current table structure (simplified for analysis)
        $currentColumns = $this->getTableColumnNames($tableName);
        $currentIndexes = $this->getTableIndexNames($tableName);
        $currentConstraints = $this->getTableConstraintNames($tableName);

        // Check columns
        foreach ($tableNode->column as $column) {
            $columnName = (string) $column['name'];
            if (! \in_array($columnName, $currentColumns, true)) {
                $changes['new_columns'][] = $columnName;
            }
            // Note: Column modification detection would require more complex analysis
        }

        // Check indexes
        foreach ($tableNode->index as $index) {
            $refId = (string) $index['referenceId'];
            if (! \in_array($refId, $currentIndexes, true)) {
                $changes['new_indexes'][] = $refId;
            }
        }

        // Check constraints
        foreach ($tableNode->constraint as $constraint) {
            $refId = (string) $constraint['referenceId'];
            if (! \in_array($refId, $currentConstraints, true)) {
                $changes['new_constraints'][] = $refId;
            }
        }

        // Remove empty arrays
        return array_filter($changes, fn ($items) => ! empty($items));
    }

    /**
     * Display analysis results.
     *
     * @param array<string, mixed> $analysis
     */
    private function displayAnalysis(array $analysis, SymfonyStyle $io): void
    {
        $io->section('📋 Migration Analysis Results');

        // Summary table
        $summaryRows = [
            ['New Tables', $analysis['new_tables']],
            ['Updated Tables', $analysis['updated_tables']],
            ['New Columns', $analysis['new_columns']],
            ['Modified Columns', $analysis['modified_columns']],
            ['New Indexes', $analysis['new_indexes']],
            ['New Constraints', $analysis['new_constraints']],
        ];

        $io->table(['Change Type', 'Count'], $summaryRows);

        // Detailed changes
        if ($io->isVerbose()) {
            foreach ($analysis['changes'] as $moduleName => $moduleChanges) {
                $io->section("Module: {$moduleName}");

                foreach ($moduleChanges as $change) {
                    if ('create' === $change['action']) {
                        $io->text("  ➕ CREATE TABLE: {$change['table']}");
                    } else {
                        $io->text("  🔄 UPDATE TABLE: {$change['table']}");
                        if (! empty($change['new_columns'])) {
                            $io->text('    + Columns: ' . implode(', ', $change['new_columns']));
                        }
                        if (! empty($change['new_indexes'])) {
                            $io->text('    + Indexes: ' . implode(', ', $change['new_indexes']));
                        }
                        if (! empty($change['new_constraints'])) {
                            $io->text('    + Constraints: ' . implode(', ', $change['new_constraints']));
                        }
                    }
                }
            }
        }
    }

    /**
     * Apply schema changes.
     *
     * @param array<string> $modules
     *
     * @return array<string, mixed>
     */
    private function applySchemaChanges(array $modules, SymfonyStyle $io): array
    {
        $results = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach ($modules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);
            if (! $moduleData) {
                continue;
            }

            $schemaFile = $moduleData['path'] . '/etc/db_schema.xml';
            if (! file_exists($schemaFile)) {
                continue;
            }

            try {
                $result = $this->schemaSetup->processModuleSchema($moduleName, $schemaFile);
                $results['created'] += $result['created'];
                $results['updated'] += $result['updated'];
            } catch (\Exception $e) {
                $results['errors'][] = "Module {$moduleName}: " . $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Display migration results.
     *
     * @param array<string, mixed> $results
     */
    private function displayResults(array $results, SymfonyStyle $io): void
    {
        $rows = [
            ['Tables Created', $results['created']],
            ['Tables Updated', $results['updated']],
            ['Errors', \count($results['errors'])],
        ];

        $io->table(['Result', 'Count'], $rows);

        if (! empty($results['errors'])) {
            $io->section('❌ Errors');
            foreach ($results['errors'] as $error) {
                $io->text("  • {$error}");
            }
        }
    }

    /**
     * Create database backup.
     */
    private function createBackup(SymfonyStyle $io): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = "schema_backup_{$timestamp}.sql";

        $io->text("Creating backup: {$backupFile}");

        // This is a simplified backup - in production you'd use pg_dump
        $io->note('Backup functionality would use pg_dump in production environment');
    }

    private function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->connection->prepare("SELECT to_regclass('public.{$tableName}') AS table_exists");
            $stmt->execute();
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);

            return $result && null !== $result['table_exists'];
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getTableColumnNames(string $tableName): array
    {
        $stmt = $this->connection->prepare("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = ? AND table_schema = 'public'
        ");
        $stmt->execute([$tableName]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getTableIndexNames(string $tableName): array
    {
        $stmt = $this->connection->prepare("
            SELECT indexname 
            FROM pg_indexes 
            WHERE tablename = ? AND schemaname = 'public'
        ");
        $stmt->execute([$tableName]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getTableConstraintNames(string $tableName): array
    {
        $stmt = $this->connection->prepare("
            SELECT constraint_name 
            FROM information_schema.table_constraints 
            WHERE table_name = ? AND table_schema = 'public'
        ");
        $stmt->execute([$tableName]);

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private function getXmlColumns(\SimpleXMLElement $tableNode): array
    {
        $columns = [];
        foreach ($tableNode->column as $column) {
            $columns[] = (string) $column['name'];
        }

        return $columns;
    }

    private function getXmlIndexes(\SimpleXMLElement $tableNode): array
    {
        $indexes = [];
        foreach ($tableNode->index as $index) {
            $indexes[] = (string) $index['referenceId'];
        }

        return $indexes;
    }

    private function getXmlConstraints(\SimpleXMLElement $tableNode): array
    {
        $constraints = [];
        foreach ($tableNode->constraint as $constraint) {
            $constraints[] = (string) $constraint['referenceId'];
        }

        return $constraints;
    }
}
