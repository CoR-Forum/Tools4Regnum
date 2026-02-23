<?php
namespace App;

/**
 * Authentication via the cor-forum.de API.
 * Manages session state and local user cache.
 */
class Auth
{
    /**
     * Attempt login against the forum API.
     * On success, creates/updates local user and stores session data.
     *
     * @return array{success: bool, error?: string}
     */
    public static function login(string $username, string $password): array
    {
        $apiUrl = env('FORUM_API_URL', 'https://cor-forum.de/api.php');
        $apiKey = env('FORUM_API_KEY', '');

        $ch = curl_init($apiUrl . '/login');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'username' => $username,
                'password' => $password,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'X-API-KEY: ' . $apiKey,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['success' => false, 'error' => 'Connection failed: ' . $curlError];
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['success'])) {
            return ['success' => false, 'error' => 'Invalid API response.'];
        }

        if (!$data['success']) {
            return ['success' => false, 'error' => $data['error'] ?? 'Login failed.'];
        }

        // Determine admin status
        $adminIds = envArray('ADMIN_USER_IDS');
        $isAdmin = in_array((string)$data['userID'], $adminIds, true);

        // Upsert local user record
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('INSERT INTO users (forum_user_id, username, email, is_admin, last_login)
            VALUES (:uid, :uname, :email, :admin, datetime(\'now\'))
            ON CONFLICT(forum_user_id) DO UPDATE SET
                username = :uname,
                email = :email,
                is_admin = :admin,
                last_login = datetime(\'now\')');
        $stmt->execute([
            ':uid'   => $data['userID'],
            ':uname' => $data['username'],
            ':email' => $data['email'] ?? '',
            ':admin' => $isAdmin ? 1 : 0,
        ]);

        // Store in session
        $_SESSION['user'] = [
            'forum_user_id' => (int)$data['userID'],
            'username'      => $data['username'],
            'email'         => $data['email'] ?? '',
            'is_admin'      => $isAdmin,
        ];

        return ['success' => true];
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user']['forum_user_id']);
    }

    public static function isAdmin(): bool
    {
        return self::isLoggedIn() && !empty($_SESSION['user']['is_admin']);
    }

    public static function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function userId(): int
    {
        return $_SESSION['user']['forum_user_id'] ?? 0;
    }
}
