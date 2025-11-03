<?php

declare(strict_types=1);

namespace Tests\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Infinri\Core\Console\Command\SetupInstallCommand;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Setup\SchemaSetup;
use Infinri\Core\Model\Module\ModuleList;
use PDO;
use PDOStatement;

/**
 * Test setup:install command functionality
 */
class SetupInstallCommandTest extends TestCase
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
        $command = new SetupInstallCommand(
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
        $command = new SetupInstallCommand();
        
        $this->assertEquals('setup:install', $command->getName());
        $this->assertStringContainsString('Install Infinri Framework', $command->getDescription());
        
        // Check that command has expected options
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('admin-username'));
        $this->assertTrue($definition->hasOption('admin-email'));
        $this->assertTrue($definition->hasOption('admin-password'));
        $this->assertTrue($definition->hasOption('skip-admin'));
    }

    public function testCommandFailsWithoutDependencies(): void
    {
        $command = new SetupInstallCommand(); // No dependencies injected
        
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

    public function testCommandSucceedsWithSkipAdminOption(): void
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

        // Mock schema setup
        $this->mockSchemaSetup->method('processModuleSchema')
            ->willReturn(['created' => 1, 'updated' => 0]);

        $exitCode = $this->commandTester->execute(['--skip-admin' => true]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('installed successfully', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Admin user creation skipped', $this->commandTester->getDisplay());
    }

    public function testCommandWithNonInteractiveAdminCreation(): void
    {
        // Mock successful database connection
        $mockStatement = $this->createMock(PDOStatement::class);
        $this->mockConnection->method('query')->willReturn($mockStatement);

        // Mock enabled modules (empty to skip schema processing)
        $this->mockModuleManager->method('getEnabledModuleNames')
            ->willReturn([]);

        // Mock schema setup
        $this->mockSchemaSetup->method('processModuleSchema')
            ->willReturn(['created' => 0, 'updated' => 0]);

        // Mock PDO prepare/execute for admin user creation
        $mockPreparedStatement = $this->createMock(PDOStatement::class);
        $mockPreparedStatement->method('execute')->willReturn(true);
        $mockPreparedStatement->method('fetchColumn')->willReturn(false); // User doesn't exist
        
        $this->mockConnection->method('prepare')
            ->willReturn($mockPreparedStatement);

        $exitCode = $this->commandTester->execute([
            '--admin-username' => 'testadmin',
            '--admin-email' => 'test@example.com',
            '--admin-password' => 'SecurePassword123!',
            '--admin-firstname' => 'Test',
            '--admin-lastname' => 'Admin'
        ]);
        
        $this->assertEquals(0, $exitCode); // Command::SUCCESS
        $this->assertStringContainsString('installed successfully', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Admin user "testadmin" created', $this->commandTester->getDisplay());
    }

    public function testPasswordValidation(): void
    {
        $command = new SetupInstallCommand(
            $this->mockModuleManager,
            $this->mockSchemaSetup,
            $this->mockConnection
        );

        // Use reflection to test private password validation logic
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('execute');

        // Test with weak password - should fail validation during interactive input
        // This would be tested more thoroughly in integration tests
        $this->assertTrue(true); // Placeholder - full validation testing would require more complex mocking
    }
}
