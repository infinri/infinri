<?php
declare(strict_types=1);

namespace Infinri\Admin\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;
use PDO;
// TODO: Remove this patch, setup:install command should provide a questionare that will ask for admin credentials, and create the admin user
/**
 * Creates default admin account for initial system access
 */
class InstallDefaultAdminUser implements DataPatchInterface
{
    private PDO $connection;
    
    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
    }
    
    /**
     * @inheritDoc
     */
    public function apply(): void
    {
        $stmt = $this->connection->prepare(
            "SELECT user_id FROM admin_users WHERE username = ? LIMIT 1"
        );
        $stmt->execute(['admin']);
        
        if ($stmt->fetchColumn()) {
            return;
        }
        $password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 13]);

        $stmt = $this->connection->prepare(
            "INSERT INTO admin_users (username, email, firstname, lastname, password, roles, is_active, created_at, updated_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
        );
        
        $stmt->execute([
            'admin',
            'admin@infinri.local',
            'Admin',
            'User',
            $password,
            json_encode(['ROLE_ADMIN', 'ROLE_USER']),
            true
        ]);
        
        echo "✓ Default admin user created (username: admin, password: admin123)\n";
        echo "⚠ WARNING: Change the default password immediately after first login!\n";
    }
    
    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }
    
    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
