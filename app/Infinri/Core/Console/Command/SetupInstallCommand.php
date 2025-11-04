<?php

declare(strict_types=1);

namespace Infinri\Core\Console\Command;

use Infinri\Admin\Model\Repository\AdminUserRepository;
use Infinri\Admin\Model\ResourceModel\AdminUser as AdminUserResource;
use Infinri\Core\Model\Module\ModuleManager;
use Infinri\Core\Model\Setup\SchemaSetup;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Interactive setup command that creates database schema and admin user
 * Replaces hardcoded admin credentials with secure interactive setup.
 */
class SetupInstallCommand extends Command
{
    protected static string $defaultName = 'setup:install';

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
        $this->setName('setup:install')
            ->setDescription('Install Infinri Framework - setup database and create admin user')
            ->setHelp('Interactive installation wizard that sets up the database schema and creates the initial admin user.')
            ->addOption(
                'admin-username',
                null,
                InputOption::VALUE_OPTIONAL,
                'Admin username (will prompt if not provided)'
            )
            ->addOption(
                'admin-email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Admin email (will prompt if not provided)'
            )
            ->addOption(
                'admin-password',
                null,
                InputOption::VALUE_OPTIONAL,
                'Admin password (will prompt if not provided)'
            )
            ->addOption(
                'admin-firstname',
                null,
                InputOption::VALUE_OPTIONAL,
                'Admin first name (will prompt if not provided)'
            )
            ->addOption(
                'admin-lastname',
                null,
                InputOption::VALUE_OPTIONAL,
                'Admin last name (will prompt if not provided)'
            )
            ->addOption(
                'skip-admin',
                null,
                InputOption::VALUE_NONE,
                'Skip admin user creation (database setup only)'
            );
    }

    /**
     * Execute command.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🚀 Infinri Framework Installation');
        $io->text('This wizard will set up your Infinri installation.');
        $io->newLine();

        try {
            // Validate dependencies
            if (! $this->validateDependencies($io)) {
                return Command::FAILURE;
            }

            // Step 1: Database Schema Setup
            $io->section('📊 Setting Up Database Schema');
            if (! $this->setupDatabase($io)) {
                return Command::FAILURE;
            }

            // Step 2: Admin User Creation (unless skipped)
            if (! $input->getOption('skip-admin')) {
                $io->section('👤 Creating Admin User');
                if (! $this->createAdminUser($input, $io)) {
                    return Command::FAILURE;
                }
            } else {
                $io->note('Admin user creation skipped as requested.');
            }

            // Success
            $io->success('🎉 Infinri Framework installed successfully!');

            if (! $input->getOption('skip-admin')) {
                $io->note([
                    '🔐 Security Reminder:',
                    '• Change your admin password after first login',
                    '• Enable two-factor authentication if available',
                    '• Review user permissions regularly',
                ]);
            }

            $io->text('Next steps:');
            $io->listing([
                'Start your web server: php -S localhost:8000 -t pub/',
                'Visit your site: http://localhost:8000/',
                'Access admin panel: http://localhost:8000/admin/',
                'Check the documentation for more configuration options',
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Installation failed: ' . $e->getMessage());
            if ($output->isVerbose()) {
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Validate that all dependencies are available.
     */
    private function validateDependencies(SymfonyStyle $io): bool
    {
        $io->text('Checking dependencies...');

        if (null === $this->schemaSetup || null === $this->moduleManager || null === $this->connection) {
            $io->error('Required dependencies not available. Run from application context.');

            return false;
        }

        // Test database connection
        try {
            $this->connection->query('SELECT 1');
            $io->text('✅ Database connection: OK');
        } catch (\Exception $e) {
            $io->error('❌ Database connection failed: ' . $e->getMessage());

            return false;
        }

        // Check enabled modules
        $enabledModules = $this->moduleManager->getEnabledModuleNames();
        if (empty($enabledModules)) {
            $io->error('❌ No enabled modules found.');

            return false;
        }

        $io->text(\sprintf('✅ Found %d enabled modules', \count($enabledModules)));

        return true;
    }

    /**
     * Set up database schema.
     */
    private function setupDatabase(SymfonyStyle $io): bool
    {
        $io->text('Processing database schema from enabled modules...');

        $enabledModules = $this->moduleManager->getEnabledModuleNames();
        $tablesCreated = 0;
        $tablesUpdated = 0;

        foreach ($enabledModules as $moduleName) {
            $moduleData = $this->moduleManager->getModuleList()->getOne($moduleName);

            if (! $moduleData) {
                continue;
            }

            $schemaFile = $moduleData['path'] . '/etc/db_schema.xml';

            if (! file_exists($schemaFile)) {
                if ($io->isVerbose()) {
                    $io->text("  - Skipping {$moduleName} (no db_schema.xml)");
                }
                continue;
            }

            $io->text("  - Processing {$moduleName}...");

            try {
                $result = $this->schemaSetup->processModuleSchema($moduleName, $schemaFile);
                $created = $result['created'] ?? 0;
                $updated = $result['updated'] ?? 0;
                $tablesCreated += $created;
                $tablesUpdated += $updated;
                $io->text("    ✅ Created: {$created}, Updated: {$updated}");
            } catch (\Exception $e) {
                $io->error("    ❌ Error processing {$moduleName}: " . $e->getMessage());

                throw $e;
            }
        }

        $io->success(\sprintf('Database schema setup complete: %d tables created, %d tables updated', $tablesCreated, $tablesUpdated));

        return true;
    }

    /**
     * Create admin user interactively.
     */
    private function createAdminUser(InputInterface $input, SymfonyStyle $io): bool
    {
        $io->text('Setting up your admin account...');

        // Create repository
        if (null === $this->connection) {
            $io->error('Database connection not available');

            return false;
        }

        // Wrap PDO in Connection object
        $connectionWrapper = new \Infinri\Core\Model\ResourceModel\Connection(['pdo' => $this->connection]);
        $adminUserResource = new AdminUserResource($connectionWrapper);
        $adminUserRepository = new AdminUserRepository($adminUserResource);

        // Collect admin user data
        $adminData = $this->collectAdminData($input, $io, $adminUserRepository);

        if (! $adminData) {
            $io->warning('Admin user creation cancelled.');

            return true; // Not a failure, user chose to skip
        }

        // Create admin user
        try {
            $adminUser = $adminUserRepository->create();
            $adminUser->setUsername($adminData['username'])
                     ->setEmail($adminData['email'])
                     ->setFirstname($adminData['firstname'])
                     ->setLastname($adminData['lastname'])
                     ->setPassword(password_hash($adminData['password'], \PASSWORD_BCRYPT, ['cost' => 13]))
                     ->setRoles(['ROLE_ADMIN', 'ROLE_USER'])
                     ->setIsActive(true);

            $adminUserRepository->save($adminUser);

            $io->success(\sprintf('✅ Admin user "%s" created successfully!', $adminData['username']));
            $io->text([
                'Admin Details:',
                "  Username: {$adminData['username']}",
                "  Email: {$adminData['email']}",
                "  Name: {$adminData['firstname']} {$adminData['lastname']}",
            ]);

            return true;
        } catch (\Exception $e) {
            $io->error('Failed to create admin user: ' . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Collect admin user data from input options or interactive prompts.
     */
    private function collectAdminData(InputInterface $input, SymfonyStyle $io, AdminUserRepository $repository): ?array
    {
        $helper = $this->getHelper('question');

        // Username
        $username = $input->getOption('admin-username');
        if (! $username) {
            $question = new Question('Admin username: ');
            $question->setValidator(function ($value) use ($repository) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('Username cannot be empty.');
                }
                if (\strlen($value) < 3) {
                    throw new \InvalidArgumentException('Username must be at least 3 characters.');
                }
                if ($repository->usernameExists($value)) {
                    throw new \InvalidArgumentException('Username already exists.');
                }

                return $value;
            });
            $username = $helper->ask($input, $io, $question);
        }

        // Email
        $email = $input->getOption('admin-email');
        if (! $email) {
            $question = new Question('Admin email: ');
            $question->setValidator(function ($value) use ($repository) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('Email cannot be empty.');
                }
                if (! filter_var($value, \FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException('Invalid email format.');
                }
                if ($repository->emailExists($value)) {
                    throw new \InvalidArgumentException('Email already exists.');
                }

                return $value;
            });
            $email = $helper->ask($input, $io, $question);
        }

        // Password
        $password = $input->getOption('admin-password');
        if (! $password) {
            $question = new Question('Admin password (min 12 characters): ');
            $question->setHidden(true);
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('Password cannot be empty.');
                }
                if (\strlen($value) < 12) {
                    throw new \InvalidArgumentException('Password must be at least 12 characters.');
                }
                // Check password strength
                if (! preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $value)) {
                    throw new \InvalidArgumentException('Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.');
                }

                return $value;
            });
            $password = $helper->ask($input, $io, $question);

            // Confirm password
            $confirmQuestion = new Question('Confirm password: ');
            $confirmQuestion->setHidden(true);
            $confirmPassword = $helper->ask($input, $io, $confirmQuestion);

            if ($password !== $confirmPassword) {
                $io->error('Passwords do not match. Please try again.');

                return $this->collectAdminData($input, $io, $repository);
            }
        }

        // First name
        $firstname = $input->getOption('admin-firstname');
        if (! $firstname) {
            $question = new Question('Admin first name: ');
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('First name cannot be empty.');
                }

                return $value;
            });
            $firstname = $helper->ask($input, $io, $question);
        }

        // Last name
        $lastname = $input->getOption('admin-lastname');
        if (! $lastname) {
            $question = new Question('Admin last name: ');
            $question->setValidator(function ($value) {
                if (empty($value)) {
                    throw new \InvalidArgumentException('Last name cannot be empty.');
                }

                return $value;
            });
            $lastname = $helper->ask($input, $io, $question);
        }

        // Confirmation
        $io->table(
            ['Field', 'Value'],
            [
                ['Username', $username],
                ['Email', $email],
                ['First Name', $firstname],
                ['Last Name', $lastname],
                ['Password', str_repeat('*', \strlen($password))],
            ]
        );

        $confirmQuestion = new ConfirmationQuestion('Create admin user with these details? (y/N) ', false);
        if (! $helper->ask($input, $io, $confirmQuestion)) {
            return null;
        }

        return [
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }
}
