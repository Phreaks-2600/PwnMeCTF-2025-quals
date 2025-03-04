<?php
namespace App\Auth;

use App\Database\Database;
use App\Models\User;
use PDOException;
use Exception;

class AuthController {
    private $user;
    private $jwtManager;
    public $error;

    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $this->user = new User($db);
        $this->jwtManager = new JWTManager();
    }

    public function login(string $username, string $password): array {
        try {
            $user = $this->user->authenticate($username, $password);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
            
            $token = $this->jwtManager->generateToken($user);

            if (!$token) {
                return [
                    'success' => false,
                    'message' => 'Error generating token'
                ];
            }

            setcookie('jwt', $token, [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => '',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            return [
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'permissions' => $user['permissions']
                ]
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Server error during authentication'
            ];
        }
    }

    public function isAuthenticated(): bool {
        $token = $_COOKIE['jwt'] ?? null;
        if (!$token) {
            return false;
        }
        return (bool) $this->jwtManager->validateToken($token);
    }

    public function getCurrentUser(): ?array {
        $token = $_COOKIE['jwt'] ?? null;
        if (!$token) {
            return null;
        }
        return $this->jwtManager->validateToken($token);
    }

    public function logout(): void {
        setcookie('jwt', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    public function register(string $username, string $password, string $email, string $firstName, string $lastName): bool {
        try {
            $userId = $this->user->create($username, $password, $email, $firstName, $lastName);
            
            if (!$userId) {
                $this->error = "Error creating account";
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }
}
