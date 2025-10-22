<?php
declare(strict_types=1);

namespace Infinri\Admin\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Admin User Resource Model
 * 
 * Database operations for admin_users table
 */
class AdminUser extends AbstractResource
{
    /**
     * @var string Main table name
     */
    protected string $mainTable = 'admin_users';
    
    /**
     * @var string Primary key field
     */
    protected string $primaryKey = 'user_id';
    
    /**
     * @var string ID field name
     */
    protected string $idFieldName = 'user_id';

    /**
     * Load admin user by username
     *
     * @param string $username
     * @return array|false
     */
    public function loadByUsername(string $username): array|false
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Load admin user by email
     *
     * @param string $email
     * @return array|false
     */
    public function loadByEmail(string $email): array|false
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Update last login timestamp
     *
     * @param int $userId
     * @return int Affected rows
     */
    public function updateLastLogin(int $userId): int
    {
        return $this->connection->update(
            $this->mainTable,
            ['last_login_at' => date('Y-m-d H:i:s')],
            "{$this->idFieldName} = ?",
            [$userId]
        );
    }

    /**
     * Get all admin users
     *
     * @return array
     */
    public function findAll(): array
    {
        $stmt = $this->connection->query("SELECT * FROM {$this->mainTable} ORDER BY created_at DESC");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
