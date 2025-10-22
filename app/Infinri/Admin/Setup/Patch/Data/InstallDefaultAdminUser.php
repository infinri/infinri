<?php
declare(strict_types=1);

namespace Infinri\Admin\Setup\Patch\Data;

use Infinri\Core\Setup\Patch\DataPatchInterface;
use PDO;

/**
 * Install Default Admin User
 * 
 * Creates default admin account for initial system access
 * 
 * SECURITY NOTE: Change the default password immediately after first login!
 * Default credentials: admin / admin123
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
        // Check if admin user already exists
        $stmt = $this->connection->prepare(
            "SELECT user_id FROM admin_users WHERE username = ? LIMIT 1"
        );
        $stmt->execute(['admin']);
        
        if ($stmt->fetchColumn()) {
            // Admin user already exists, skip
            return;
        }
        
        // Hash default password: admin123
        // Using bcrypt with cost 13 for security
        $password = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 13]);
        
        // Insert default admin user
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
        // No dependencies - this can run as soon as admin_users table exists
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
