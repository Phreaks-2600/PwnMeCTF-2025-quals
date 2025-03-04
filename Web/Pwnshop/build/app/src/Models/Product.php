<?php
namespace App\Models;

use App\Database\Database;
use PDO;
use PDOException;
use Exception;

class Product {
    private $db;

    public function __construct(PDO $db = null) {
        $this->db = $db ?? Database::getInstance()->getConnection();
        try {
            $stmt = $this->db->query("SELECT COUNT(*) FROM products");
            $count = $stmt->fetchColumn();
            
            $stmt = $this->db->query("SELECT * FROM products LIMIT 1");
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getAll() {
        try {
            $stmt = $this->db->query("SELECT * FROM products ORDER BY id DESC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function create($name, $description, $price, $category, $stock, $image_url = null) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO products (name, description, price, category, stock, product_picture) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $success = $stmt->execute([$name, $description, $price, $category, $stock, $image_url]);
            
            if ($success) {
                return $this->getLastInserted();
            }
            return false;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function search($query = '', $category = 'all', $sort = 'id_asc', $limit = 10, $offset = 0) {
        try {
            $params = [];
            $sql = "SELECT p.* FROM products p WHERE 1=1";
            if (!empty($query)) {
                $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }

            if ($category !== 'all') {
                $sql .= " AND p.category = ?";
                $params[] = $category;
            }
            switch ($sort) {
                case 'name_asc':
                    $sql .= " ORDER BY p.name ASC";
                    break;
                case 'name_desc':
                    $sql .= " ORDER BY p.name DESC";
                    break;
                case 'price_asc':
                    $sql .= " ORDER BY p.price ASC";
                    break;
                case 'price_desc':
                    $sql .= " ORDER BY p.price DESC";
                    break;
                case 'id_desc':
                    $sql .= " ORDER BY p.id DESC";
                    break;
                default:
                    $sql .= " ORDER BY p.id ASC";
            }

            $sql .= sprintf(" LIMIT %d OFFSET %d", (int)$limit, (int)$offset);

            $stmt = $this->db->prepare($sql);
            
            $stmt->execute($params);
            
            if ($stmt->errorCode() !== '00000') {
                return [];
            }
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            return $results;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getSearchTotal($query = '', $category = 'all') {
        try {
            $params = [];
            $sql = "SELECT COUNT(*) FROM products p WHERE 1=1";

            if (!empty($query)) {
                $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }

            if ($category !== 'all') {
                $sql .= " AND p.category = ?";
                $params[] = $category;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function getCategories() {
        try {
            $stmt = $this->db->query("SELECT DISTINCT category FROM products ORDER BY category");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    public function update($id, $name, $description, $price, $category, $stock, $image_url = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE products 
                SET name = ?, description = ?, price = ?, category = ?, stock = ?, product_picture = ?
                WHERE id = ?
            ");
            return $stmt->execute([$name, $description, $price, $category, $stock, $image_url, $id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getById($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }


    public function getByIds(array $productIds): array {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
        $stmt = $this->db->prepare("
            SELECT * FROM products WHERE id IN ($placeholders)
        ");
        $stmt->execute($productIds);
    
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!$result) {
            return [];
        }
    
        return $result;
    }
    
    public function updateStock(int $productId, int $newStock): bool {
        if ($newStock < 0) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE products
            SET stock = ?
            WHERE id = ?
        ");
        $result = $stmt->execute([$newStock, $productId]);

        if (!$result) {
            return false;
        }

        return true;
    }

    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getLastInserted() {
        try {
            $stmt = $this->db->query("SELECT * FROM products WHERE id = LAST_INSERT_ID()");
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getProductCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }
} 