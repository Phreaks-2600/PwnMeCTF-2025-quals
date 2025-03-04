<?php
namespace App\Models;

use App\Database\Database;
use PDO;
use PDOException;
use Exception;

class User {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }


    public function updateProfile($userId, $data) {
        try {
            $allowedFields = ['first_name', 'last_name', 'email', 'profile_picture'];
            $updates = [];
            $values = [];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields) && $value !== null && $value !== '') {
                    if (is_array($value)) {
                        continue;
                    }
                    $updates[] = "$field = ?";
                    $values[] = $value;
                }
            }

            if (empty($updates)) {
                return true;
            }

            $values[] = $userId;
            $stmt = $this->db->prepare("
                UPDATE users 
                SET " . implode(', ', $updates) . "
                WHERE id = ?
            ");
            
            $result = $stmt->execute($values);
            if (!$result) {
                return false;
            }
            return $result;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getProfile($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, first_name, last_name, profile_picture, password
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function emailExist($email) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            return $stmt->fetchColumn() > 0; 
        } catch (PDOException $e) {
            return false;
        }
    }

    public function usernameExist($username) {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$username]);
            return $stmt->fetchColumn() > 0; 
        } catch (PDOException $e) {
            return false;
        }
    }

    public function authenticate($username, $password) {
        try {
            if (!$this->db) {
                return false;
            }
            $stmt = $this->db->prepare("
                SELECT u.*, GROUP_CONCAT(up.permission) as user_permissions
                FROM users u
                LEFT JOIN user_permissions up ON u.id = up.user_id
                WHERE u.username = ?
                GROUP BY u.id
            ");

            if (!$stmt->execute([$username])) {
                return false;
            }

            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false;
            }

            if (!password_verify($password, $user['password'])) {
                return false;
            }
            return [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'profile_picture' => $user['profile_picture'],
                'permissions' => $user['user_permissions'] ? explode(',', $user['user_permissions']) : []
            ];

        } catch (\PDOException $e) {
            return false;
        }
    }
    

    public function create($username, $password, $email, $firstName, $lastName) {
        try {
            $this->db->beginTransaction();
            
            if ($this->emailExist($email)) {
                return false;
            }
    
            if ($this->usernameExist($username)) {
                return false;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    username,
                    password,
                    profile_picture,
                    email,
                    first_name,
                    last_name
                )
                VALUES (?, ?, '/assets/img/profiles/default-avatar.png', ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                $hashedPassword,
                $email,
                $firstName,
                $lastName
            ]);
            
            $userId = $this->db->lastInsertId();
            
            $stmt = $this->db->prepare("
                INSERT INTO user_permissions (user_id, permission)
                SELECT ?, permission
                FROM (
                    SELECT 'viewProducts' as permission UNION
                    SELECT 'updateProfile' UNION
                    SELECT 'changePassword' UNION
                    SELECT 'manageCart' UNION
                    SELECT 'checkout' UNION
                    SELECT 'manageOrders' UNION
                    SELECT 'viewOrders' UNION
                    SELECT 'searchOrders'
                ) AS permissions_list
            ");
            
            $stmt->execute([$userId]);
            
            $this->db->commit();
            return $userId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    

    public function getUserPermissions($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT permission 
                FROM user_permissions 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAllUsers() {
        $query = "SELECT u.id, u.username, u.email, u.first_name, u.last_name
                 FROM users u";
        
        try {
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($users as &$user) {
                $permQuery = "SELECT permission 
                             FROM user_permissions
                             WHERE user_id = :user_id";
                             
                $permStmt = $this->db->prepare($permQuery);
                $permStmt->execute(['user_id' => $user['id']]);
                
                $permissions = $permStmt->fetchAll(PDO::FETCH_COLUMN);
                $user['permissions'] = $permissions ?: [];
                $user['first_name'] = $user['first_name'] ?? '';
                $user['last_name'] = $user['last_name'] ?? '';
            }
            
            return $users;
            
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addUser($username, $password, $email, $firstName, $lastName, $permissions = [], $image = null) {
        try {
            $this->db->beginTransaction();
            if ($this->emailExist($email) === true) {
                return false;
            }
            if ($this->usernameExist($username) === true) {
                return false;
            }
            
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            if ($image) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $image));
                $filename = uniqid() . '.jpg';
                $path = 'uploads/profiles/' . $filename;
                file_put_contents($path, $imageData);
                $image = '/uploads/profiles/' . $filename;
            }
            else {
                $image = '/assets/img/profiles/default-avatar.png';
            }
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    username,
                    password,
                    profile_picture,
                    email,
                    first_name,
                    last_name
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $username,
                $hashedPassword,
                $image,
                $email,
                $firstName,
                $lastName
            ]);
            
            $userId = $this->db->lastInsertId();
            
            if (!empty($permissions)) {
                $placeholders = str_repeat('SELECT ? as permission UNION ', count($permissions) - 1) . 'SELECT ?';
                $stmt = $this->db->prepare("
                    INSERT INTO user_permissions (user_id, permission)
                    SELECT ?, permission
                    FROM ($placeholders) p
                ");
                
                $params = array_merge([$userId], $permissions);
                $stmt->execute($params);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO user_permissions (user_id, permission)
                    SELECT ?, permission
                    FROM (
                        SELECT 'viewProducts' as permission UNION
                        SELECT 'updateProfile' UNION
                        SELECT 'changePassword' UNION
                        SELECT 'manageCart' UNION
                        SELECT 'checkout' UNION
                        SELECT 'addReview'
                    ) p
                ");
                $stmt->execute([$userId]);
            }
            
            $this->db->commit();
            return $userId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateUser($id, $data) {
        try {
            $this->db->beginTransaction();
    
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$existingUser) {
                return false;
            }
    
            if (isset($data['email']) && $data['email'] !== $existingUser['email']) {
                if ($this->emailExist($data['email']) === true) {
                    return false;
                }
            }
    
            if (isset($data['username']) && $data['username'] !== $existingUser['username']) {
                if ($this->usernameExist($data['username']) === true) {
                    return false;
                }
            }

            $fields = [];
            $params = [];
            foreach (['username', 'email', 'first_name', 'last_name'] as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
    
            if (!empty($data['password'])) {
                $fields[] = "password = ?";
                $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
            }
    
            if (!empty($fields)) {
                $params[] = $id;
                $updateQuery = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
                $stmt = $this->db->prepare($updateQuery);
                $stmt->execute($params);
            }
    
            if (isset($data['permissions'])) {
                $permissions = $data['permissions'];
    
                $stmt = $this->db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
                $stmt->execute([$id]);
    
                if (!empty($permissions)) {
                    $placeholders = str_repeat('SELECT ? as permission UNION ', count($permissions) - 1) . 'SELECT ?';
                    $stmt = $this->db->prepare("
                        INSERT INTO user_permissions (user_id, permission)
                        SELECT ?, permission
                        FROM ($placeholders) p
                    ");
    
                    $params = array_merge([$id], $permissions);
                    $stmt->execute($params);
                }
            }
    
            $this->db->commit();
            return true;
    
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("DELETE FROM user_permissions WHERE user_id = ?");
            $stmt->execute([$id]);
            
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getByUsername(string $username): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password, email, first_name, last_name, profile_picture
                FROM users 
                WHERE username = ?
            ");
            $stmt->execute([$username]);
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getUserCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, first_name, last_name, created_at, profile_picture 
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user['profile_picture']) {
                $user['profile_picture'] = '/assets/images/default-profile.jpg';
            }
            
            return $user;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update($id, $email, $firstName, $lastName, $image_url = null) {
        try {
            if ($image_url) {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET email = ?, first_name = ?, last_name = ?, profile_picture = ?
                    WHERE id = ?
                ");
                return $stmt->execute([$email, $firstName, $lastName, $image_url, $id]);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE users 
                    SET email = ?, first_name = ?, last_name = ?
                    WHERE id = ?
                ");
                return $stmt->execute([$email, $firstName, $lastName, $id]);
            }
        } catch (PDOException $e) {
            return false;
        }
    }

    public function updatePassword($userId, $currentPassword, $newPassword) {
        try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashedPassword, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getBalance(int $userId): float {
        try {
            $stmt = $this->db->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return (float)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    public function updateBalance(int $userId, float $newBalance): bool {
        try {
            if ($newBalance < 0) {
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE users 
                SET balance = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$newBalance, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function deductBalance(int $userId, float $amount): bool {
        try {
            $currentBalance = $this->getBalance($userId);
            if ($currentBalance < $amount) {
                return false;
            }

            $newBalance = $currentBalance - $amount;
            return $this->updateBalance($userId, $newBalance);

        } catch (\Exception $e) {
            return false;
        }
    }

    public function addBalance(int $userId, float $amount): bool {
        try {
            $currentBalance = $this->getBalance($userId);
            $newBalance = $currentBalance + $amount;
            $success = $this->updateBalance($userId, $newBalance);

            if ($success) {
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
}
