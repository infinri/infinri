<?php

declare(strict_types=1);

namespace Infinri\Admin\Model\ResourceModel;

use Infinri\Core\Model\ResourceModel\AbstractResource;

/**
 * Remember Token Resource Model.
 */
class RememberToken extends AbstractResource
{
    protected string $mainTable = 'admin_user_remember_tokens';

    protected string $primaryKey = 'token_id';

    protected string $idFieldName = 'token_id';

    /**
     * Create a new remember token.
     */
    public function createToken(int $userId, string $tokenHash, string $ipAddress, string $userAgent, int $expiresInDays = 30): int
    {
        $timestamp = strtotime("+{$expiresInDays} days");
        if (false === $timestamp) {
            throw new \RuntimeException('Failed to calculate expiration date');
        }
        $expiresAt = date('Y-m-d H:i:s', $timestamp);

        return $this->connection->insert(
            $this->mainTable,
            [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => $expiresAt,
            ]
        );
    }

    /**
     * Find token by hash.
     */
    public function findByTokenHash(string $tokenHash): array|false
    {
        return $this->findOneBy(['token_hash' => $tokenHash]);
    }

    /**
     * Update last used timestamp.
     */
    public function updateLastUsed(int $tokenId): int
    {
        return $this->connection->update(
            $this->mainTable,
            ['last_used_at' => date('Y-m-d H:i:s')],
            "{$this->idFieldName} = ?",
            [$tokenId]
        );
    }

    /**
     * Delete token by hash.
     */
    public function deleteByTokenHash(string $tokenHash): int
    {
        return $this->connection->delete(
            $this->mainTable,
            'token_hash = ?',
            [$tokenHash]
        );
    }

    /**
     * Delete all tokens for user.
     */
    public function deleteByUserId(int $userId): int
    {
        return $this->connection->delete(
            $this->mainTable,
            'user_id = ?',
            [$userId]
        );
    }

    /**
     * Delete expired tokens.
     */
    public function deleteExpired(): int
    {
        return $this->connection->delete(
            $this->mainTable,
            'expires_at < ?',
            [date('Y-m-d H:i:s')]
        );
    }
}
