<?php
namespace App\Models;

use PDO;
use App\Models\User;
use App\Models\Product;
use App\Models\Voucher;

class Order {
    private $db;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->product = new Product($db);
        $this->user = new User($db);
    }

    public function create(int $userId, array $items, float $total, string $status = 'pending', string $voucherCode = null): ?int {
        try {
            $productIds = array_map(fn($item) => (int)$item['product_id'], $items);
            $products = $this->product->getByIds($productIds);
    
            if (empty($products)) {
                throw new \Exception('No product found for the given IDs.');
            }
            
            $productMap = [];
            foreach ($products as $product) {
                $productMap[$product['id']] = $product;
            }
    
            $calculatedTotal = 0;
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                if (!isset($productMap[$productId])) {
                    throw new \Exception('Product not found: ' . $productId);
                }
    
                $quantity = (int)$item['quantity'];
                if ($quantity <= 0) {
                    throw new \Exception('Invalid quantity for product: ' . $productId);
                }
    
                $product = $productMap[$productId];
                if ($product['stock'] < $quantity) {
                    throw new \Exception('Insufficient stock for product: ' . $productId);
                }
    
                $itemTotal = $product['price'] * $quantity;
                $calculatedTotal += $itemTotal;
            }
    
            $calculatedTotal = round($calculatedTotal, 2);
            $finalTotal = $calculatedTotal;
            if (!is_null($voucherCode)) {
                $voucher = new Voucher($this->db);
                $voucherData = $voucher->getByCode($voucherCode);
                if (!$voucherData) {
                    throw new \Exception('Invalid discount code');
                }
                if ($voucher->isUsedByUser($userId, $voucherData['id'])) {
                    throw new \Exception('This discount code has already been used');
                }
                $voucherAmount = $voucherData['amount'];
                $voucherId = $voucherData['id'];
                $finalTotal = max(0, $calculatedTotal - $voucherAmount);
            }
            else{
                $voucherId = null;
                $voucherAmount = null;
            }
            $user = new User($this->db);
            $userBalance = $user->getBalance($userId);
            if ($userBalance < $finalTotal) {
                throw new \Exception('Insufficient balance');
            }
            if (!$user->deductBalance($userId, $finalTotal)) {
                throw new \Exception('Error deducting balance');
            }
            $stmt = $this->db->prepare("
                INSERT INTO orders (
                    user_id, 
                    total, 
                    status, 
                    created_at,
                    shipping_name,
                    shipping_address,
                    shipping_zipcode,
                    shipping_city,
                    shipping_country,
                    shipping_phone,
                    voucher_code,
                    voucher_amount
                ) VALUES (?, ?, ?, NOW(), '', '', '', '', '', '', ?, ?)
            ");
            
            $stmt->execute([
                $userId, 
                $finalTotal, 
                $status,
                $voucherCode,
                $voucherAmount ?? null
            ]);
            $orderId = $this->db->lastInsertId();
            $stmt = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            
            foreach ($items as $item) {
                $stmt->execute([
                    $orderId,
                    $item['product_id'],
                    $item['quantity'],
                    $productMap[$item['product_id']]['price']
                ]);
            }
            if (!is_null($voucherId)) {
                $voucher->useVoucher($userId, $voucherId, $orderId);
            }
            
            return $orderId;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete(int $orderId): bool {
        $stmt = $this->db->prepare("
            DELETE FROM orders
            WHERE id = ?
        ");
        return $stmt->execute([$orderId]);
    }   

    public function getOrderCount() {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM orders");
            $stmt->execute();
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
        }
    }

    public function search($query = '', $page = 1, $limit = 10, $sort = 'id_asc', $status = 'all', $userId = null) {
        try {
            $params = [];
            $sql = "SELECT 
                        o.id AS order_id, 
                        o.total, 
                        o.status, 
                        o.created_at AS order_date, 
                        u.username, 
                        oi.product_id, 
                        p.name AS product_name, 
                        oi.quantity, 
                        oi.price AS item_price 
                    FROM orders o
                    JOIN users u ON o.user_id = u.id
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN products p ON oi.product_id = p.id
                    WHERE 1=1";

            if ($userId !== null) {
                $sql .= " AND o.user_id = ?";
                $params[] = $userId;
            }
            if (!empty($query)) {
                $sql .= " AND (o.id LIKE ? OR o.total LIKE ? OR p.name LIKE ?)";
                $params[] = "%$query%";
                $params[] = "%$query%";
                $params[] = "%$query%";
            }
            if ($status !== 'all') {
                $sql .= " AND o.status = ?";
                $params[] = $status;
            }
            $validSortOptions = [
                'status_asc' => 'o.status ASC',
                'status_desc' => 'o.status DESC',
                'id_asc' => 'o.id ASC',
                'id_desc' => 'o.id DESC',
                'total_asc' => 'o.total ASC',
                'total_desc' => 'o.total DESC',
                'created_at_desc' => 'o.created_at DESC',
            ];
            $sortOrder = $validSortOptions[$sort] ?? 'o.id ASC';
            $sql .= " ORDER BY $sortOrder";
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT $limit OFFSET $offset";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return [];
            }
    
            $stmt->execute($params);
    
            if ($stmt->errorCode() !== '00000') {
                return [];
            }
    
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $orders = [];
            foreach ($results as $row) {
                $orderId = $row['order_id'];
                if (!isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'order_id' => $row['order_id'],
                        'total' => $row['total'],
                        'status' => $row['status'],
                        'order_date' => $row['order_date'],
                        'username' => $row['username'],
                        'items' => []
                    ];
                }
                $orders[$orderId]['items'][] = [
                    'product_id' => $row['product_id'],
                    'product_name' => $row['product_name'],
                    'quantity' => $row['quantity'],
                    'item_price' => $row['item_price'],
                ];
            }
            return array_values($orders);
        } catch (\PDOException $e) {
            return [];
        }
    }
    

    public function getOrderWithItems(int $orderId): ?array {
        $stmt = $this->db->prepare("
            SELECT o.*, u.username
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }
    public function getAllOrders(): array {
        $stmt = $this->db->prepare("
            SELECT o.*, u.username
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserOrders(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT o.*, u.username
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.user_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function cancel(int $orderId): bool {
        try {
            $this->db->beginTransaction();
            
            $cancelStmt = $this->db->prepare("
                UPDATE orders
                SET status = 'cancelled'
                WHERE id = ?
            ");
            $result = $cancelStmt->execute([$orderId]);

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function update(int $orderId, array $data): bool {
        $stmt = $this->db->prepare("
            UPDATE orders
            SET status = ?,
                shipping_name = ?,
                shipping_address = ?,
                shipping_zipcode = ?,
                shipping_city = ?,
                shipping_country = ?,
                shipping_phone = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['status'],
            $data['shipping_name'],
            $data['shipping_address'],
            $data['shipping_zipcode'],
            $data['shipping_city'],
            $data['shipping_country'],
            $data['shipping_phone'],
            $orderId
        ]);
    }

    public function updateStatus(int $orderId, string $status): bool {
        $stmt = $this->db->prepare("
            UPDATE orders
            SET status = ?
            WHERE id = ?
        ");
        return $stmt->execute([$status, $orderId]);
    }

    public function getOrderById(int $orderId): ?array {
        $stmt = $this->db->prepare("
            SELECT o.*, 
                   u.username,
                   o.voucher_code,
                   o.voucher_amount,
                   o.shipping_name,
                   o.shipping_address,
                   o.shipping_zipcode,
                   o.shipping_city,
                   o.shipping_country,
                   o.shipping_phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT oi.*, p.name as product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    public function updateShippingInfo(int $orderId, array $shippingInfo): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE orders 
                SET 
                    shipping_name = ?,
                    shipping_address = ?,
                    shipping_zipcode = ?,
                    shipping_city = ?,
                    shipping_country = ?,
                    shipping_phone = ?
                WHERE id = ?
            ");

            return $stmt->execute([
                $shippingInfo['name'] ?? '',
                $shippingInfo['address'] ?? '',
                $shippingInfo['zipcode'] ?? '',
                $shippingInfo['city'] ?? '',
                $shippingInfo['country'] ?? '',
                $shippingInfo['phone'] ?? '',
                $orderId
            ]);
        } catch (\Exception $e) {
            return false;
        }
    }
} 