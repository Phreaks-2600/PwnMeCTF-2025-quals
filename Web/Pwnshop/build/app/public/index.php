<?php
require __DIR__ . '/../bootstrap.php';

use App\Bootstrap\Imports;
use App\Auth\AuthController;
use App\Api\Rest\RestController;
use App\Api\Permissions;
use App\Models\Order;
use App\Database\Database;

$auth = new AuthController();
$db = Database::getInstance()->getConnection();
$order = new Order($db);
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api/') === 0) {
    header('Content-Type: application/json');
    
    $restController = new RestController();
    $path = substr($uri, strlen('/api/'));
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    
    try {
        $response = $restController->handleRequest($method, $path, $data);
        http_response_code($response['status'] ?? 200);
        echo json_encode($response);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server Error', 'status' => 500]);
    }
    exit;
}

switch ($uri) {
    case '/':
        if (!$auth->isAuthenticated()) {
            require __DIR__ . '/../templates/main/login.php';
        } else {
                header('Location: /products');
            exit;
        }
        break;
    case '/login':
        require __DIR__ . '/../templates/main/login.php';
        break;
    case '/register':
        require __DIR__ . '/../templates/main/register.php';
        break;
    case '/logout':
        setcookie('jwt', '', time() - 3600, '/');
        header('Location: /');
        exit;
        break;
    case '/admin':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_PLATFORM, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        header('Location: /admin/users');
        break;
    case '/admin/products':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_PRODUCTS, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        require __DIR__ . '/../templates/admin/products.php';
        break;
    case '/admin/orders':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_ORDERS, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        require __DIR__ . '/../templates/admin/orders.php';
        break;  
    case '/admin/vouchers':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_VOUCHERS, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        require __DIR__ . '/../templates/admin/vouchers.php';
        break;
    case '/admin/settings':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_PLATFORM, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        require __DIR__ . '/../templates/admin/settings.php';
        break;
    case '/admin/users':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated() || !in_array(Permissions::MANAGE_USERS, $user['permissions'])) {
            header('Location: /products');
            exit;
        }
        require __DIR__ . '/../templates/admin/users.php';
        break;
    case '/products':
        if (!$auth->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        require __DIR__ . '/../templates/main/products.php';
        break;
    case '/cart':
        if (!$auth->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        require __DIR__ . '/../templates/main/cart.php';
        break;
    case '/orders':
        if (!$auth->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        require __DIR__ . '/../templates/main/orders.php';
        break;
    
    case '/profile':
        if (!$auth->isAuthenticated()) {
            header('Location: /');
            exit;
        }
        require __DIR__ . '/../templates/main/profile.php';
        break;
    case '/order':
        $user = $auth->getCurrentUser();
        if (!$auth->isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $orderId = $_GET['id'] ?? null;
        if (!$orderId || !is_numeric($orderId)) {
            $errorCode = 400;
            $errorTitle = "Invalid Request";
            $errorMessage = "The requested order is invalid.";
            require __DIR__ . '/../templates/error.php';
            exit;
        }

        $order = $order->getOrderById($orderId);
        if (!$order) {
            $errorCode = 404;
            $errorTitle = "Order not found";
            $errorMessage = "The requested order does not exist.";
            require __DIR__ . '/../templates/error.php';
            exit;
        }

        if (intval($order['user_id']) !== intval($user['user_id'])) {
            $errorCode = 403;
            $errorTitle = "Access Denied";
            $errorMessage = "You are not authorized to access this order.";
            require __DIR__ . '/../templates/error.php';
            exit;
        }
        if ($order['status'] === 'pending') {
            require __DIR__ . '/../templates/main/order.php';
        } else {
            require __DIR__ . '/../templates/main/order_details.php';
        }
        break;
    default:
        $errorCode = 404;
        $errorTitle = "Page not found";
        $errorMessage = "The page you are looking for does not exist.";
        require __DIR__ . '/../templates/error.php';
        break;
}