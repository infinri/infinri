<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Infinri\Core\Console\Command\SchemaMigrateCommand;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Setup\SchemaSetup;
use Infinri\Core\Model\Module\ModuleList;
use PDO;
use PDOStatement;

/**
 * Test schema migration command functionality
 */
class SchemaMigrateCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private PDO $mockConnection;
    private ModuleManager $mockModuleManager;
    private SchemaSetup $mockSchemaSetup;

    protected function setUp(): void
    {
        // Mock PDO connection
        $this->mockConnection = $this->createMock(PDO::class);
        
        // Mock ModuleManager
        $this->mockModuleManager = $this->createMock(ModuleManager::class);
        
        // Mock SchemaSetup
        $this->mockSchemaSetup = $this->createMock(SchemaSetup::class);

        // Create command with mocked dependencies
        $command = new SchemaMigrateCommand(
            $this->mockModuleManager,
            $this->mockSchemaSetup,
            $this->mockConnection
        );

        // Set up command tester
        $application = new Application();
        $application->add($command);
        $this->commandTester = new CommandTester($command);
    }

    public function testCommandIsConfiguredCorrectly(): void
    {
        $command = new SchemaMigrateCommand();
        
        $this->assertEquals('schema:migrate', $command->getName());
        $this->assertStringContainsString('Advanced schema migration', $command->getDescription());
        
        // Check that command has expected options
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('module'));
        $this->assertTrue($definition->hasOption('force'));
        $this->assertTrue($definition->hasOption('backup'));
    }

    public function testCommandFailsWithoutDependencies(): void
    {
        $command = new SchemaMigrateCommand(); // No dependencies injected
        
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($command);

        $exitCode = $commandTester->execute([]);
        
        $this->assertEquals(1, $exitCode); // Command::FAILURE
        $this->assertStringContainsString('Required dependencies not available', $commandTester->getDisplay());
    }

    public function testCommandFailsWithBadDatabaseConnection(): void
    {
        // Mock connection that throws exception
        $this->mockConnection->method('query')
            ->willThrowException(new \PDOException('Connection failed'));

        $exitCode = $this->commandTester->execute([]);
        
        $this->assertEquals(1, $exitCode); // Command::FAILURE
        $this->assertStringContainsString('Database connection failed', $this->commandTester->getDisplay());
    }

    public function testDryRunModeShowsChangesWithoutApplying(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core']);

        // Mock module list
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => __DIR__ . '/../../../../fixtures']);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        // Create a temporary schema file for testing
        $fixturesDir = __DIR__ . '/../../../../fixtures/etc';
        if (!is_dir($fixturesDir)) {
            mkdir($fixturesDir, 0755, true);
        }
        
        $schemaFile = $fixturesDir . '/db_schema.xml';
        $schemaContent = '<?xml version="1.0"?>
        <schema xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <table name="test_table">
                <column name="id" type="int" identity="true" nullable="false"/>
                <column name="name" type="varchar" length="255" nullable="false"/>
            </table>
        </schema>';
        file_put_contents($schemaFile, $schemaContent);

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Dry run completed', $this->commandTester->getDisplay());

        // Clean up
        unlink($schemaFile);
        rmdir($fixturesDir);
        rmdir(dirname($fixturesDir));
    }

    public function testCommandWithSpecificModule(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core', 'Infinri_Admin']);

        // Mock module list
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => '/fake/path']);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        $exitCode = $this->commandTester->execute([
            '--module' => 'Infinri_Core',
            '--dry-run' => true
        ]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
    }

    public function testCommandWithNonExistentModule(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules (not including the requested module)
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core']);

        $exitCode = $this->commandTester->execute([
            '--module' => 'NonExistent_Module',
            '--dry-run' => true
        ]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS (no modules to process)
        $this->assertStringContainsString('not enabled or does not exist', $this->commandTester->getDisplay());
    }

    public function testCommandWithNoModulesToProcess(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock no enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn([]);

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('No modules to process', $this->commandTester->getDisplay());
    }

    public function testCommandWithUpToDateSchemas(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core']);

        // Mock module list with no schema file
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => '/nonexistent/path']);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        $exitCode = $this->commandTester->execute(['--dry-run' => true]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('All schemas are up to date', $this->commandTester->getDisplay());
    }

    public function testCommandAppliesChangesWithoutDryRun(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core']);

        // Mock module list
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => '/fake/path']);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        // Mock schema setup processing
        $this->mockSchemaSetup->method('processModuleSchema')
            ->willReturn(['created' => 1, 'updated' => 0]);

        $exitCode = $this->commandTester->execute([]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('Schema migration completed', $this->commandTester->getDisplay());
    }

    public function testCommandHandlesSchemaSetupExceptions(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn(['Infinri_Core']);

        // Mock module list
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => '/fake/path']);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        // Create a temporary schema file that will be processed
        $tempDir = sys_get_temp_dir() . '/fake/path/etc';
        mkdir($tempDir, 0755, true);
        $schemaFile = $tempDir . '/db_schema.xml';
        file_put_contents($schemaFile, '<?xml version="1.0"?><schema></schema>');

        // Mock module list to return the temp directory
        $mockModuleList = $this->createMock(ModuleList::class);
        $mockModuleList->method('getOne')
            ->willReturn(['path' => dirname($tempDir)]);
        $this->mockModuleManager->method('getModuleList')
            ->willReturn($mockModuleList);

        // Mock schema setup to throw exception
        $this->mockSchemaSetup->method('processModuleSchema')
            ->willThrowException(new \Exception('Schema processing failed'));

        $exitCode = $this->commandTester->execute([]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS (errors are handled gracefully)
        $this->assertStringContainsString('Errors', $this->commandTester->getDisplay());

        // Clean up
        unlink($schemaFile);
        rmdir($tempDir);
        rmdir(dirname($tempDir));
        rmdir(dirname(dirname($tempDir)));
    }
}
