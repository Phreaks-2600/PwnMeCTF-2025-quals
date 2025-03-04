<?php
namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Voucher {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getByCode(string $code) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM vouchers 
                WHERE code = ? AND is_active = true
            ");
            $stmt->execute([$code]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getUserVouchers(int $userId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT v.* 
                FROM vouchers v
                LEFT JOIN user_vouchers uv ON v.id = uv.voucher_id AND uv.user_id = ?
                WHERE v.is_active = true AND uv.id IS NULL
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function useVoucher(int $userId, int $voucherId, int $orderId): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_vouchers (user_id, voucher_id, order_id)
                VALUES (?, ?, ?)
            ");
            return $stmt->execute([$userId, $voucherId, $orderId]);
        } catch (PDOException $e) {
            return array(
                'success' => true,
                'message' => $e->getMessage()
            );
        }
    }

    public function isUsedByUser(int $voucherId, int $userId): bool {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM user_vouchers
                WHERE user_id = ? AND voucher_id = ?
            ");
            $stmt->execute([$userId, $voucherId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function getById(int $id): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM vouchers WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function create(string $code, float $amount, ?int $maxUses = null): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO vouchers (code, amount, max_uses, is_active)
                VALUES (?, ?, ?, true)
            ");
            return $stmt->execute([$code, $amount, $maxUses]);
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function update(int $id, array $data): array {
        try {
            $updateFields = [];
            $params = [];
    
            foreach ($data as $key => $value) {
                if (in_array($key, ['code', 'amount', 'max_uses', 'is_active'])) {
                    $updateFields[] = "$key=?";
                    $params[] = $value;
                }
            }
    
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => "No valid fields to update"
                ];
            }
            $params[] = $id;
            $sql = "UPDATE vouchers SET " . implode(', ', $updateFields) . " WHERE id=?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            return [
                'success' => $result,
                'message' => $result ? "Update successful" : "Update failed"
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    

    public function delete(int $id): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM vouchers WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    public function validateVoucher(string $code, float $cartTotal): array {
        try {
            $voucher = $this->getByCode($code);
            
            if (!$voucher) {
                return [
                    'success' => false,
                    'message' => "Invalid voucher code"
                ];
            }

            if (!$voucher['is_active']) {
                return [
                    'success' => false,
                    'message' => "This voucher is no longer active"
                ];
            }

            if (strtotime($voucher['expiry_date']) < time()) {
                return [
                    'success' => false,
                    'message' => "This voucher has expired"
                ];
            }

            if ($voucher['min_purchase'] && $cartTotal < $voucher['min_purchase']) {
                return [
                    'success' => false,
                    'message' => "The minimum purchase amount is not reached"
                ];
            }

            if ($voucher['max_uses'] !== null && $voucher['uses'] >= $voucher['max_uses']) {
                return [
                    'success' => false,
                    'message' => "This voucher has reached its maximum usage limit"
                ];
            }

            return [
                'success' => true,
                'voucher' => $voucher
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getAll(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM vouchers ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
}