<?php
namespace App\Api\Rest;

use App\Auth\JWTManager;
use App\Models\{Product, Order, User, Cart, Settings, Voucher};
use App\Database\Database;
use App\Api\{Permissions, Privilege};
use ReflectionClass;
use Exception;
use App\Security\XmlScanner;

class RestController {
    private $user;
    private $product;
    private $cart;
    private $order;
    private $jwtManager;
    private $settings;
    
    public function __construct() {
        $db = Database::getInstance()->getConnection();
        $this->user = new User($db);
        $this->product = new Product($db);
        $this->cart = new Cart($db);
        $this->order = new Order($db);
        $this->jwtManager = new JWTManager();
        $this->settings = new Settings();
        $this->voucher = new Voucher($db);
        $this->xmlScanner = new XmlScanner();
    }
    
    private function checkPrivileges($methodName, $currentUser): ?array {
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod($methodName);
        $attributes = $method->getAttributes(Privilege::class);

        if (empty($attributes)) {
            return null;
        }

        $privilege = $attributes[0]->newInstance();
        
        if (empty($privilege->permissions)) {
            return null;
        }
        if (!array_intersect($privilege->permissions, $currentUser['permissions'])) {
            return ['error' => 'Insufficient permissions', 'status' => 403];
        }

        return null;
    }

    

    private function getPrivilege($methodName): ?Privilege {
        $reflection = new ReflectionClass($this);
        $method = $reflection->getMethod($methodName);
        $attributes = $method->getAttributes(Privilege::class);
        return $attributes[0]->newInstance();
    }

    private function getRouteInfo($method, $path) {
        $path = trim($path, '/');
        $key = strtoupper($method) . '|' . $path;
        
        $routes = [
            'POST|login' => ['method' => 'login', 'params' => []],
            'POST|register' => ['method' => 'register', 'params' => []],
            'POST|logout' => ['method' => 'logout', 'params' => []],
            'GET|users' => ['method' => 'getUsers', 'params' => []],
            'POST|users' => ['method' => 'addUser', 'params' => []],
            'GET|users/count' => ['method' => 'getUsersCount', 'params' => []],
            'GET|users/(\d+)' => ['method' => 'getUser', 'params' => ['id']],
            'PUT|users/(\d+)' => ['method' => 'updateUser', 'params' => ['id']],
            'DELETE|users/(\d+)' => ['method' => 'deleteUser', 'params' => ['id']],
            'PUT|users/(\d+)/password' => ['method' => 'updateUserPassword', 'params' => ['id']],
            'PUT|users/password' => ['method' => 'updatePassword', 'params' => []],
            'GET|products' => ['method' => 'getProducts', 'params' => []],
            'POST|products' => ['method' => 'createProduct', 'params' => []],
            'GET|products/count' => ['method' => 'getProductsCount', 'params' => []],
            'POST|products/search' => ['method' => 'searchProducts', 'params' => []],
            'GET|products/(\d+)' => ['method' => 'getProduct', 'params' => ['id']],
            'PUT|products/(\d+)' => ['method' => 'updateProduct', 'params' => ['id']],
            'DELETE|products/(\d+)' => ['method' => 'deleteProduct', 'params' => ['id']],
            'GET|cart' => ['method' => 'getCart', 'params' => []],
            'POST|cart' => ['method' => 'addToCart', 'params' => []],
            'PUT|cart/(\d+)' => ['method' => 'updateCartItem', 'params' => ['id']],
            'DELETE|cart/(\d+)' => ['method' => 'removeFromCart', 'params' => ['id']],
            'POST|cart/clear' => ['method' => 'clearCart', 'params' => []],
            'GET|orders' => ['method' => 'getOrders', 'params' => []],
            'POST|orders/search' => ['method' => 'searchOrders', 'params' => []],
            'GET|orders/count' => ['method' => 'getOrdersCount', 'params' => []],
            'DELETE|orders/(\d+)' => ['method' => 'deleteOrder', 'params' => ['id']],
            'GET|orders/(\d+)' => ['method' => 'getOrder', 'params' => ['id']],
            'PUT|orders/(\d+)' => ['method' => 'updateOrder', 'params' => ['id']],
            'POST|orders/submit' => ['method' => 'submitOrder', 'params' => []],
            'POST|orders/create' => ['method' => 'createOrder', 'params' => []],
            'POST|orders/(\d+)/cancel' => ['method' => 'cancelOrder', 'params' => ['id']],
            'GET|categories' => ['method' => 'getCategories', 'params' => []],
            'POST|categories' => ['method' => 'createCategory', 'params' => []],
            'PUT|categories/(\d+)' => ['method' => 'updateCategory', 'params' => ['id']],
            'DELETE|categories/(\d+)' => ['method' => 'deleteCategory', 'params' => ['id']],
            'GET|settings/global' => ['method' => 'getGlobalSettings', 'params' => []],
            'PUT|settings/global' => ['method' => 'updateGlobalSettings', 'params' => []],
            'PUT|settings/css' => ['method' => 'updateCustomCss', 'params' => []],
            'POST|settings/less/upload' => ['method' => 'uploadLessFile', 'params' => []],
            'GET|settings/less/files' => ['method' => 'getLessFiles', 'params' => []],
            'POST|settings/less/delete' => ['method' => 'deleteLessFile', 'params' => []],
            'GET|settings/less/imports' => ['method' => 'getLessImports', 'params' => []],
            'POST|settings/less/imports' => ['method' => 'addLessImport', 'params' => []],
            'DELETE|settings/less/imports' => ['method' => 'removeLessImport', 'params' => []],
            'GET|profile' => ['method' => 'getProfile', 'params' => []],
            'PUT|profile' => ['method' => 'updateProfile', 'params' => []],
            'PUT|profile/password' => ['method' => 'updatePassword', 'params' => []],
            'POST|vouchers/validate' => ['method' => 'checkVoucher', 'params' => []],
            'GET|vouchers' => ['method' => 'getVouchers', 'params' => []],
            'GET|vouchers/(\d+)' => ['method' => 'getVoucher', 'params' => ['id']],
            'DELETE|vouchers/(\d+)' => ['method' => 'deleteVoucher', 'params' => ['id']],
            'PUT|vouchers/(\d+)' => ['method' => 'updateVoucher', 'params' => ['id']],
            'POST|vouchers' => ['method' => 'generateVoucher', 'params' => []]
        ];

        if (isset($routes[$key])) {
            return $routes[$key];
        }

        foreach ($routes as $pattern => $route) {
            $pattern = str_replace('|', '\|', $pattern);
            if (preg_match('#^' . $pattern . '$#', $key, $matches)) {
                array_shift($matches);
                return [
                    'method' => $route['method'],
                    'params' => $matches
                ];
            }
        }

        return null;
    }

    public function handleRequest($method, $path, $token = null, $data = null) {
        try {
            $route = $this->getRouteInfo($method, $path);
            if (!$route) {
                return [
                    'success' => false,
                    'message' => 'Route not found',
                    'status' => 404
                ];
            }
            if ($data === null) {
                $rawData = file_get_contents('php://input');
                $data = json_decode($rawData, true) ?? [];
            }
            $token = $this->extractToken();
            $privilege = $this->getPrivilege($route['method']);
            $currentUser = null;
            if (!empty($privilege->permissions)) {
                $currentUser = $this->jwtManager->validateToken($token);
                if (!$currentUser) {
                    return [
                        'success' => false,
                        'message' => 'You are not logged in',
                        'status' => 401
                    ];
                }
                $check = $this->checkPrivileges($route['method'], $currentUser);
                if ($check !== null) {
                    return $check;
                }
                $params = [$currentUser];
                if (!empty($route['params'])) {
                    $params = array_merge($params, array_values($route['params']));
                }
                $params[] = $data;
            $result = call_user_func_array(
                [$this, $route['method']], 
                    $params
            );
            } else {
                $params = [$data];
                if (!empty($route['params'])) {
                    $params = array_merge($params, array_values($route['params']));
            }
                
                $result = call_user_func_array(
                    [$this, $route['method']], 
                    $params
                );
            }
            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    private function extractToken() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            return $token;
        }
        
        $token = $_COOKIE['jwt'] ?? null;
        return $token;
    }

    #[Privilege(permissions: [Permissions::MANAGE_VOUCHERS])]
    private function getVouchers($currentUser) {
        $vouchers = $this->voucher->getAll();
        return [
            'success' => true,
            'vouchers' => $vouchers
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_VOUCHERS])]
    private function getVoucher($currentUser, $id) {
        $voucher = $this->voucher->getById($id);
        return [
            'success' => true,
            'voucher' => $voucher
        ];
    }

    #[Privilege(permissions: [Permissions::GENERATE_VOUCHER])]
    private function generateVoucher($currentUser, $data) {
        if (empty($data['code']) || empty($data['amount'])) {
            return [
                'error' => 'The code and amount are required',
                'status' => 400
            ];
        }

        try {
            $result = $this->voucher->create(
                $data['code'],
                $data['amount'],
                $data['max_uses'] ?? 1
            );

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Error creating voucher',
                    'status' => 500
                ];
            }

            return [
                'success' => true,
                'message' => 'Voucher created successfully',
                'voucher' => $this->voucher->getByCode($data['code'])
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::UPDATE_VOUCHER])]
    private function updateVoucher($currentUser, $id, $data) {
        if (empty($data['amount']) || !isset($data['max_uses'])) {
            return [
                'error' => 'The amount and max uses are required',
                'status' => 400
            ];
        }
        try {
            $result = $this->voucher->update(
                $id,
                $data
            );

            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Error updating voucher',
                    'status' => 500
                ];
            }

            return [
                'success' => true,
                'message' => 'Voucher updated successfully',
                'voucher' => $this->voucher->getById($id)
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::DELETE_VOUCHER])]
    private function deleteVoucher($currentUser, $id) {
        try {
            $result = $this->voucher->delete($id);
            
            if (!$result) {
                return [
                    'success' => false,
                    'message' => 'Error deleting voucher',
                    'status' => 500
                ];
            }

            return [
                'success' => true,
                'message' => 'Voucher deleted successfully'
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::VALIDATE_VOUCHER])]
    private function checkVoucher($currentUser, $data) {

        try {
            $voucher = $this->voucher->getByCode($data['code']);
            if (!$voucher) {
                return [
                    'success' => false,
                    'message' => 'Invalid voucher code',
                    'status' => 400
                ];
            }

            if (!$voucher['is_active']) {
                return [
                    'success' => false,
                    'message' => 'This voucher is no longer active',
                    'status' => 400
                ];
            }
            $alreadyUsed = $this->voucher->isUsedByUser($voucher['id'], $currentUser['user_id']);
            if ($alreadyUsed) {
                return [
                    'success' => false,
                    'message' => 'You have already used this voucher',
                    'status' => 400
                ];
            }

            return [
                'success' => true,
                'message' => 'Voucher valid',
                'voucher' => $voucher
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::GET_VOUCHERS])]
    private function listVouchers($currentUser) {

        try {
            $vouchers = $this->voucher->getAll();

            return [
                'success' => true,
                'vouchers' => $vouchers
            ];

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 500
            ];
        }
    }
    #[Privilege(permissions: [Permissions::MANAGE_PRODUCTS])]
    private function createProduct($currentUser, $data) {
        if (empty($data['name']) || empty($data['description']) || 
            empty($data['price']) || empty($data['category']) || 
            !isset($data['stock'])) {
            return ['error' => 'All fields are required', 'status' => 400];
        }
        $image_url = null;
        if (!empty($data['image'])) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
            $filename = uniqid() . '.jpg';
            $path = __DIR__ . '/../../../public/uploads/products/' . $filename;
            file_put_contents($path, $imageData);
            $image_url = '/uploads/products/' . $filename;
        }
        $result = $this->product->create(
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['stock'],
            $image_url
        );
        
        if (!$result) {
            return [
                'success' => false,
                'message' => 'Error creating product'
            ];
        }
        $newProduct = $this->product->getLastInserted();
        
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $newProduct
        ];
    }

    
    #[Privilege(permissions: [Permissions::MANAGE_PRODUCTS])]
    private function updateProduct($currentUser, $id, $data) {
        if (empty($data['name']) || empty($data['description']) || 
            empty($data['price']) || empty($data['category']) || 
            !isset($data['stock'])) {
            return ['error' => 'All fields are required', 'status' => 400];
        }
        $image_url = null;
        if (!empty($data['image'])) {
            $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
            $filename = uniqid() . '.jpg';
            $path = __DIR__ . '/../../../public/uploads/products/' . $filename;
            file_put_contents($path, $imageData);
            $image_url = '/uploads/products/' . $filename;
        }
        else {
            $image_url = $data['image_url'];
        }
        $result = $this->product->update(
            $id,
            $data['name'],
            $data['description'],
            $data['price'],
            $data['category'],
            $data['stock'],
            $image_url
        );
        
        return [
            'success' => $result,
            'message' => $result ? 'Product updated successfully' : 'Error updating product'
        ];
    }
    
    #[Privilege(permissions: [Permissions::MANAGE_PRODUCTS])]
    private function deleteProduct($currentUser, $id) {
        $result = $this->product->delete($id);
        return [
            'success' => $result,
            'message' => $result ? 'Product deleted successfully' : 'Error deleting product'
        ];
    }

    #[Privilege(permissions: [Permissions::VIEW_PRODUCTS])]
    private function getProduct($currentUser, $id) {
        $product = $this->product->getById($id);
        
        if (!$product) {
            return [
                'success' => false,
                'message' => 'Product not found',
                'status' => 404
            ];
        }
        
        return [
            'success' => true,
            'product' => $product
        ];
    }

    #[Privilege(permissions: [Permissions::VIEW_PRODUCTS])]
    private function searchProducts($currentUser, $data) {
        try {
            
            $page = $data['page'] ?? 1;
            $limit = $data['limit'] ?? 10;
            $offset = ($page - 1) * $limit;
            
            $query = $data['query'] ?? '';
            $category = $data['category'] ?? 'all';
            $sort = $data['sort'] ?? 'id_asc';
    
            $total = $this->product->getSearchTotal($query, $category);
            $products = $this->product->search(
                $query,
                $category,
                $sort,
                $limit,
                $offset
            );
            
            return [
                'success' => true,
                'products' => $products,
                'total' => $total,
                'page' => $page,
                'totalPages' => ceil($total / $limit)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    

    #[Privilege(permissions: [Permissions::UPDATE_PROFILE])]
    private function updateProfile($currentUser, $data) {
        try {
            
            if (empty($data['email']) || empty($data['firstName']) || empty($data['lastName'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            if (!empty($data['image'])) {
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $data['image']));
                $filename = uniqid() . '.jpg';
                $path = __DIR__ . '/../../../public/uploads/profiles/' . $filename;
                file_put_contents($path, $imageData);
                $image_url = '/uploads/profiles/' . $filename;
            }
            $success = $this->user->update(
                $currentUser['user_id'],
                $data['email'],
                $data['firstName'],
                $data['lastName'],
                $image_url ?? null
            );

            return [
                'success' => $success,
                'message' => $success ? 'Profile updated' : 'Error updating profile'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    #[Privilege(permissions: [])]
    private function login($data) {
        if (empty($data['username']) || empty($data['password'])) {
            return ['error' => 'Username and password required', 'status' => 400];
        }
        
        $auth = new \App\Auth\AuthController();
        $result = $auth->login($data['username'], $data['password']);
        
        if (!$result['success']) {
            return [
                'error' => $result['message'],
                'status' => 401
            ];
        }
        
        return [
            'success' => true,
            'token' => $result['token']
        ];
    }
    
    #[Privilege(permissions: [])]
    private function register($data) {
        if (empty($data['username']) || empty($data['password']) || 
            empty($data['email']) || empty($data['firstName']) || 
            empty($data['lastName'])) {
            return ['error' => 'All fields are required', 'status' => 400];
        }
        
        try {
            $auth = new \App\Auth\AuthController();
            $result = $auth->register(
                $data['username'],
                $data['password'],
                $data['email'],
                $data['firstName'],
                $data['lastName']
            );
            
            if (!$result) {
                return [
                    'error' => $auth->error,
                    'status' => 400
                ];
            }

            return [
                'success' => true,
                'message' => 'Account created successfully'
            ];
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 400
            ];
        }
    }
    
    #[Privilege(permissions: [Permissions::CHANGE_PASSWORD])]
    private function updatePassword($currentUser, $data) {
        try {
            if (empty($data['currentPassword']) || empty($data['newPassword'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required'
                ];
            }
            
            $success = $this->user->updatePassword(
                $currentUser['user_id'],
                $data['currentPassword'],
                $data['newPassword']
            );

            return [
                'success' => $success,
                'message' => $success ? 'Password updated' : 'Error updating password'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    #[Privilege(permissions: [Permissions::MANAGE_CART])]
    private function clearCart($currentUser) {
        $this->cart->clearCart($currentUser['user_id']);
        return [
            'success' => true,
            'message' => 'Cart cleared successfully'
        ];
    }
    
    #[Privilege(permissions: [Permissions::UPDATE_PROFILE])]
    private function getProfile($currentUser) {
        try {
            $user = $this->user->getById($currentUser['user_id']);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found'
                ];
            }
            unset($user['password']);
            
            return [
                'success' => true,
                'user' => $user
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_CART])]
    private function getCart($currentUser) {
        try {
            $cartItems = $this->cart->getCartItems($currentUser['user_id']);
            $total = array_reduce($cartItems, function($sum, $item) {
                return $sum + ($item['price'] * $item['quantity']);
            }, 0);

            return [
                'success' => true,
                'items' => array_map(function($item) {
                    return [
                        'id' => $item['id'],
                        'product_id' => $item['product_id'],
                        'name' => $item['name'],
                        'price' => (float)$item['price'],
                        'quantity' => (int)$item['quantity'],
                        'product_picture' => $item['product_picture'] ?? '/assets/images/default-product.jpg',
                        'subtotal' => (float)$item['price'] * (int)$item['quantity']
                    ];
                }, $cartItems),
                'total' => $total,
                'message' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error retrieving cart',
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_CART])]
    private function addToCart($currentUser, $data) {
        try {
            if (!isset($data['product_id']) || !isset($data['quantity'])) {
                return [
                    'success' => false,
                    'message' => 'Product ID and quantity required',
                    'status' => 400
                ];
            }
            $product = $this->product->getById($data['product_id']);
            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Product not found',
                    'status' => 404
                ];
            }
            $result = $this->cart->addItem(
                $currentUser['user_id'],
                (int)$data['product_id'],
                (int)$data['quantity']
            );
            

            if ($result) {
                $cartItems = $this->cart->getCartItems($currentUser['user_id']);
                $total = array_reduce($cartItems, function($sum, $item) {
                    return $sum + ($item['price'] * $item['quantity']);
                }, 0);

                return [
                    'success' => true,
                    'message' => 'Product added to cart',
                    'items' => $cartItems,
                    'total' => $total
                ];
            }

            return [
                'success' => false,
                'message' => 'Error adding product to cart',
                'status' => 500
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Server error',
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_CART])]
    private function removeFromCart($currentUser, $productId) {
        $result = $this->cart->removeItem($currentUser['user_id'], $productId);
        
        return [
            'success' => $result,
            'message' => $result ? 'Product removed from cart' : 'Error removing product from cart'
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function getUsers($currentUser) {
        try {
            $users = $this->user->getAllUsers();
            return [
                'success' => true,
                'users' => $users
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function getUsersCount($currentUser) {
        try {
            $count = $this->user->getUserCount();
            return [
                'success' => true,
                'count' => $count,
                'message' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::VIEW_PRODUCTS])]
    private function getProducts($currentUser) {
        $products = $this->product->getAll();
        
        return [
            'success' => true,
            'products' => $products
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_PRODUCTS])]
    private function getProductsCount($currentUser) {
        try {
            $count = $this->product->getProductCount();
            return [
                'success' => true,
                'count' => $count,
                'message' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function createOrder($currentUser, $data) {
        $orderid = $this->order->create($currentUser['user_id'], $data['items'], $data['total'], 'pending', $data['voucher_code']);
        $this->cart->clearCart($currentUser['user_id']);
        return [
            'success' => true,
            'order_id' => $orderid,
            'message' => 'Order created successfully'
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function deleteOrder($currentUser, $id) {
        $result = $this->order->delete($id);
        return [
            'success' => $result,
            'message' => $result ? 'Order deleted successfully' : 'Error deleting order'
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function updateOrder($currentUser, $id, $data) {
        if (empty($data['status']) || empty($data['shipping_name']) || 
            empty($data['shipping_address']) || empty($data['shipping_city']) || 
            empty($data['shipping_zipcode']) || empty($data['shipping_country']) || 
            empty($data['shipping_phone'])) {
            return ['error' => 'All fields are required', 'status' => 400];
        }
        
        $result = $this->order->update($id, $data);
        
        return [
            'success' => $result,
            'message' => $result ? 'Order updated successfully' : 'Error updating order'
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function cancelOrder($currentUser, $id) {
        $result = $this->order->cancel($id);
        return [
            'success' => $result,
            'message' => $result ? 'Order cancelled successfully' : 'Error cancelling order'
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function getOrder($currentUser, $id) {
        $order = $this->order->getOrderById($id);
        return [
            'success' => true,
            'order' => $order
        ];
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function getOrders($currentUser) {
        try {
            $orders = $this->order->getAllOrders();
            return [
                'success' => true,
                'orders' => $orders
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_ORDERS])]
    private function getOrdersCount($currentUser) {
        try {
            $count = $this->order->getOrderCount();
            return [
                'success' => true,
                'count' => $count,
                'message' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function addUser($currentUser, $data) {
        try {
            if (empty($data['username']) || empty($data['password']) || 
                empty($data['email']) || empty($data['firstName']) || 
                empty($data['lastName'])) {
                return [
                    'success' => false,
                    'message' => 'All fields are required',
                    'status' => 400
                ];
            }
            $userId = $this->user->addUser(
                $data['username'],
                $data['password'],
                $data['email'],
                $data['firstName'],
                $data['lastName'],
                $data['permissions'] ?? [],
                $data['image'] ?? null
            );

            return [
                'success' => true,
                'message' => 'User created successfully',
                'userId' => $userId
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function updateUser($currentUser, $id, $data) {
        try {
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'User ID is missing',
                    'status' => 400
                ];
            }
            
            $result = $this->user->updateUser($id, $data);
            return [
                'success' => $result,
                'message' => $result ? 'User edited successfully' : 'Error editing user'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function deleteUser($currentUser, $params) {
        try {
            $userId = $params[0] ?? $currentUser['user_id'];
            
            if (!$userId) {
                return [
                    'success' => false,
                    'message' => 'User ID is missing',
                    'status' => 400
                ];
            }
            
            $result = $this->user->delete($userId);
            return [
                'success' => $result,
                'message' => $result ? 'User deleted successfully' : 'Error deleting user'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }

    #[Privilege(permissions: [Permissions::MANAGE_USERS])]
    private function getUser($currentUser, $id) {
        try {
            
            if (!$id) {
                return [
                    'success' => false,
                    'message' => 'User ID is missing',
                    'status' => 400
                ];
            }
            
            $user = $this->user->getProfile($id);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'status' => 404
                ];
            }
            
            $user['permissions'] = $this->user->getUserPermissions($id);
            
            return [
                'success' => true,
                'user' => $user
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'status' => 500
            ];
        }
    }


#[Privilege(permissions: [])]
private function logout($currentUser) {
    setcookie('jwt', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    return [
        'success' => true,
        'message' => 'Logout successful'
    ];
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function getGlobalSettings($currentUser) {
    $settings = $this->settings->getSettings();
    
    if (!$settings) {
        return [
            'success' => false,
            'message' => 'Settings not found',
            'status' => 404
        ];
    }
    
    return [
        'success' => true,
        'settings' => $settings
    ];
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function updateGlobalSettings($currentUser, $data) {
    try {
        if (!isset($data['siteName']) || !isset($data['contactEmail'])) {
            return [
                'success' => false,
                'message' => 'All required fields are not filled',
                'status' => 400
            ];
        }

        $result = $this->settings->updateSettings($data);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Settings updated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error updating settings',
            'status' => 500
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Server error',
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::CHECKOUT])]
private function submitOrder($currentUser, $data) {
    try {
        $order = $this->order->getOrderById($data["id"]);
        if (!$order) {
            return ['error' => 'Order not found', 'status' => 404];
        }
        if ($order["status"] !== "pending") {
            return ['error' => 'Order is not pending', 'status' => 400];
        }
        $xmlSanitized = $this->xmlScanner->scan($data["xml"]);
        $xml = simplexml_load_string($xmlSanitized, 'SimpleXMLElement', LIBXML_NOENT);
        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return ['error' => 'Invalid XML: ' . $errors[0]->message, 'status' => 400];
        }
        try {
            if (isset($xml->shipping_address->name) && isset($xml->shipping_address->address) && isset($xml->shipping_address->zipcode) && isset($xml->shipping_address->city) && isset($xml->shipping_address->country) && isset($xml->shipping_address->phone)) {
            $shippingInfo = [
                'name' => (string)$xml->shipping_address->name,
                'address' => (string)$xml->shipping_address->address,
                'zipcode' => (string)$xml->shipping_address->zipcode,
                'city' => (string)$xml->shipping_address->city,
                'country' => (string)$xml->shipping_address->country,
                'phone' => (string)$xml->shipping_address->phone
            ];
                if (!$this->order->updateShippingInfo($order["id"], $shippingInfo)) {
                    throw new Exception('Error updating shipping information');
                }

                if (!$this->order->updateStatus($order["id"], 'completed')) {
                    throw new Exception('Error validating order');
                }
                

                return [
                    'success' => true,
                    'message' => 'Order validated successfully',
                    'order_id' => $order["id"],
                    'status' => 200
                ];
            }else{
                return [
                    'success' => false,
                    'message' => 'Invalid XML',
                    'status' => 400
                ];
            }

        } catch (Exception $e) {
            return [
                'error' => $e->getMessage(),
                'status' => 400
            ];
        }
    } catch (Exception $e) {
        return [
            'error' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_CART])]
private function updateCartItem($currentUser, $id, $data) {
    try {
        
        if (!isset($data['quantity'])) {
            return [
                'success' => false,
                'message' => 'Quantity is required',
                'status' => 400
            ];
        }

        $quantity = (int)$data['quantity'];
        if ($quantity < 1) {
            return [
                'success' => false,
                'message' => 'Quantity must be greater than 0',
                'status' => 400
            ];
        }
        $result = $this->cart->updateQuantity(
            $currentUser['user_id'],
            $id,
            $quantity
        );

        if ($result) {
            $cartItems = $this->cart->getCartItems($currentUser['user_id']);
            $total = array_reduce($cartItems, function($sum, $item) {
                return $sum + ($item['price'] * $item['quantity']);
            }, 0);

            return [
                'success' => true,
                'message' => 'Quantity updated',
                'items' => $cartItems,
                'total' => $total
            ];
        }

        return [
            'success' => false,
            'message' => 'Error updating quantity',
            'status' => 500
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Server error',
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function updateCustomCss($currentUser, $data) {
    try {
        
        if (!isset($data['css'])) {
            return [
                'success' => false,
                'message' => 'CSS is required',
                'status' => 400
            ];
        }

        $result = $this->settings->updateCustomCss($data['css']);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Custom CSS updated successfully'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error updating custom CSS',
            'status' => 500
        ];

    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Server error',
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function uploadLessFile($currentUser) {
    try {
        
        if (!isset($_FILES['lessFile'])) {
            return [
                'success' => false,
                'message' => 'No file provided',
                'status' => 400
            ];
        }

        $path = $_POST['lessPath'] ?? '';
        $result = $this->settings->uploadLessFile($_FILES['lessFile'], $path);
        if (!$result['success']) {
            return [
                'success' => false,
                'message' => $result['message'],
                'status' => 500
            ];
        }
        return [
            'success' => true,
            'message' => 'LESS file uploaded successfully'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function getLessFiles($currentUser) {
    try {
        $files = $this->settings->getLessFiles();
        
        return [
            'success' => true,
            'files' => $files
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::SEARCH_ORDERS])]
private function searchOrders($currentUser, $data) {
    $orders = $this->order->search($data['query'], $data['page'], $data['limit'], $data['sort'], $data['status'], $currentUser['user_id']);
    return [
        'success' => true,
        'orders' => $orders
    ];
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function deleteLessFile($currentUser, $data) {
    try {
        
        if (!isset($data['path'])) {
            return [
                'success' => false,
                'message' => 'File path required',
                'status' => 400
            ];
        }

        $this->settings->deleteLessFile($data['path']);
        
        return [
            'success' => true,
            'message' => 'File deleted successfully'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function getLessImports($currentUser) {
    try {
        $imports = $this->settings->listImportDirectories();
        
        return [
            'success' => true,
            'imports' => $imports
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function addLessImport($currentUser, $data) {
    try {
        
        if (!isset($data['physicalPath']) || !isset($data['importPath'])) {
            return [
                'success' => false,
                'message' => 'Paths are required',
                'status' => 400
            ];
        }

        $result = $this->settings->addImportDirectory($data['physicalPath'], $data['importPath']);
        
        return [
            'success' => $result,
            'message' => $result ? 'Import directory added' : 'Error adding import directory'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}

#[Privilege(permissions: [Permissions::MANAGE_APPEARANCE])]
private function removeLessImport($currentUser, $data) {
    try {
        
        if (!isset($data['physicalPath'])) {
            return [
                'success' => false,
                'message' => 'Physical path is required',
                'status' => 400
            ];
        }

        $result = $this->settings->removeImportDirectory($data['physicalPath']);
        
        return [
            'success' => $result,
            'message' => $result ? 'Import directory removed' : 'Error removing import directory'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 500
        ];
    }
}
}

