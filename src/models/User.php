<?php
namespace App\models;

use App\Database;

class User
{
    /**
     * Find user by forum user ID.
     */
    public static function find(int $forumUserId): ?array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE forum_user_id = :uid');
        $stmt->execute([':uid' => $forumUserId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Count all users.
     */
    public static function count(): int
    {
        $pdo = Database::getConnection();
        return (int)$pdo->query('SELECT COUNT(*) as cnt FROM users')->fetch()['cnt'];
    }
}
