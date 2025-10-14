<?php
declare(strict_types=1);

namespace Infinri\Core\Model\ResourceModel;

/**
 * User Resource Model
 * 
 * Handles database operations for User model
 */
class User extends AbstractResource
{
    /**
     * @var string Main table name
     */
    protected string $mainTable = 'users';

    /**
     * @var string Primary key field
     */
    protected string $idFieldName = 'id';

    /**
     * Find user by email
     *
     * @param string $email
     * @return array<string, mixed>|false
     */
    public function findByEmail(string $email): array|false
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Check if email exists
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->mainTable . ' WHERE email = ?';
        $params = [$email];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return (int) $this->connection->fetchOne($sql, $params) > 0;
    }
}
