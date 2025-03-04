<?php
namespace App\Models;

use App\Database\Database;
use PDO;
use PDOException;
use Exception;

class Cart {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCartItems($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    c.id,
                    c.product_id,
                    c.quantity,
                    p.name,
                    p.price,
                    p.product_picture
                FROM cart_items c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function addItem($userId, $productId, $quantity) {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("
                SELECT stock 
                FROM products 
                WHERE id = ? 
                FOR UPDATE
            ");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product || $product['stock'] < $quantity) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT id, quantity 
                FROM cart_items 
                WHERE user_id = ? AND product_id = ?
            ");
            $stmt->execute([$userId, $productId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $newQuantity = $existing['quantity'] + $quantity;
                if ($product['stock'] < $newQuantity) {
                    $this->db->rollBack();
                    return false;
                }

                $stmt = $this->db->prepare("
                    UPDATE cart_items 
                    SET quantity = quantity + ? 
                    WHERE user_id = ? AND product_id = ?
                ");
                $result = $stmt->execute([$quantity, $userId, $productId]);
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO cart_items (user_id, product_id, quantity) 
                    VALUES (?, ?, ?)
                ");
                $result = $stmt->execute([$userId, $productId, $quantity]);
            }
            if ($result) {
                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;

        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateQuantity($userId, $itemId, $quantity) {
        try {
            $this->db->beginTransaction();
            
            $stmt = $this->db->prepare("
                SELECT ci.*, p.stock, p.id as product_id
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id 
                WHERE ci.id = ? AND ci.user_id = ?
            ");
            $stmt->execute([$itemId, $userId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                $this->db->rollBack();
                return false;
            }

            $stockChange = $quantity - $item['quantity'];
            
            if ($item['stock'] < $stockChange) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                UPDATE cart_items 
                SET quantity = ? 
                WHERE id = ? AND user_id = ?
            ");
            $result = $stmt->execute([$quantity, $itemId, $userId]);
            if ($result) {
                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;

        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function clearCart($userId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart_items WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getItems($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT cart_items.*, products.name, products.price 
                FROM cart_items 
                JOIN products ON cart_items.product_id = products.id 
                WHERE cart_items.user_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function removeItem($userId, $itemId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM cart_items 
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$itemId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getCartItemCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM cart_items WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
} 