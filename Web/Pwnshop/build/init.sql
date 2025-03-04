USE pwnshop;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS user_permissions;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    balance DECIMAL(10,2) DEFAULT 100.00
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    product_picture VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    shipping_name VARCHAR(255) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_zipcode VARCHAR(10) NOT NULL,
    shipping_city VARCHAR(255) NOT NULL,
    shipping_country VARCHAR(255) NOT NULL,
    shipping_phone VARCHAR(20),
    voucher_code VARCHAR(20),
    voucher_amount DECIMAL(10,2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL DEFAULT 'PwnShop',
    contact_email VARCHAR(255) NOT NULL DEFAULT 'contact@pwnshop.com',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings (id, site_name, contact_email)
SELECT 1, 'PwnMe Shop', 'contact@pwnshop.com'
WHERE NOT EXISTS (SELECT 1 FROM settings WHERE id = 1);

CREATE TABLE IF NOT EXISTS less_import_directories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    physical_path VARCHAR(255) NOT NULL,
    import_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_paths (physical_path, import_path)
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'admin', 
    '$2y$10$ase/tUu2vQ1MBZO2aLWFRO9i/5Nogycv8nzi.VJz467sYouegfOOe', 
    'Admin',
    'Admin',
    'admin@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);


INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user', 
    '$2y$10$wGkO8MMC/I/fT5YFTxKs5uCtlmXWewrtGmSSkQiQyYeQSia2pxNFu', 
    'user',
    'user',
    'user@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user2', 
    '$2y$10$nBbOadVA97ST.8B2IGhNV.fYBiNXuSXZWAIrSDv/mqR4.UbPVO1WK', 
    'user2',
    'user2',
    'user2@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user3', 
    '$2y$10$TgyrtHCmA7nzwkTNSsEn9u4A40P7XhwCRFffcas1oO5tWipFIof5.', 
    'user3',
    'user3',
    'user3@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user4', 
    '$2y$10$65Pf5/8.sqMad6JZt66lMeT099lmUgI0cles2gX4bQRTzvMxRUjru', 
    'user4',
    'user4',
    'user4@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user5', 
    '$2y$10$3qX/QDSOPCZNxTh/1zrtb.zJJwGzF7Z.uWCqJkiPvEQCZWM/93UJi', 
    'user5',
    'user5',
    'user5@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user6', 
    '$2y$10$nxi6VyCrr9vKUTml4SZMD.a7.XXxzYaZmVqwRQij/rdBjC0.MfU0u', 
    'user6',
    'user6',
    'user6@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user7', 
    '$2y$10$wph/e12z7aBjs0xxXmiUUOZZFk46m1v8BnJaTINyvsdcrn4Ju//Rm', 
    'user7',
    'user7',
    'user7@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user8', 
    '$2y$10$NQQT7d0.dDkae2RBjXM4rurl.Xux2psIfE.XuPOd6ovF.yVoRkKMO', 
    'user8',
    'user8',
    'user8@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user9', 
    '$2y$10$Isikw0mmYtBSW5FAvc0DNeDiBtdq5H.Do6KpIpI0arK7dKiG4bQFi', 
    'user9',
    'user9',
    'user9@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

INSERT INTO users (
    username, 
    password, 
    first_name, 
    last_name, 
    email, 
    profile_picture
) VALUES (
    'user10', 
    '$2y$10$7TjU6S/eeuAsPLF7XjZF4eOvIMheVXw/vwORuVgai8w8Arogawt7O', 
    'user10',
    'user10',
    'user10@pwnshop.com',
    '/assets/img/profiles/default-avatar.png'
);

CREATE TABLE user_permissions (
    user_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, permission)
);

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'managePlatform'
    UNION ALL SELECT 'manageAppearance'
    UNION ALL SELECT 'manageUsers'
    UNION ALL SELECT 'viewUsers'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'manageOrders'
    UNION ALL SELECT 'updateOrderStatus'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
    UNION ALL SELECT 'generateVoucher'
    UNION ALL SELECT 'getVouchers'
    UNION ALL SELECT 'deleteVoucher'
    UNION ALL SELECT 'updateVoucher'
    UNION ALL SELECT 'manageVouchers'
) AS permissions_list
WHERE u.username = 'admin';


INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user';


INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user2';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user3';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user4';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user5';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user6';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user7';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user8';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user9';

INSERT INTO user_permissions (user_id, permission)
SELECT u.id, permissions_list.permission
FROM users u
CROSS JOIN (
    SELECT 'manageProducts' AS permission
    UNION ALL SELECT 'viewProducts'
    UNION ALL SELECT 'updateProfile'
    UNION ALL SELECT 'changePassword'
    UNION ALL SELECT 'manageCart'
    UNION ALL SELECT 'checkout'
    UNION ALL SELECT 'createOrder'
    UNION ALL SELECT 'searchOrders'
    UNION ALL SELECT 'viewOrders'
    UNION ALL SELECT 'validateVoucher'
) AS permissions_list
WHERE u.username = 'user10';


INSERT INTO products (name, description, price, stock, category, product_picture) VALUES
('Flag', 'A flag that is really expensive', 1000000, 1, 'CTF', '/assets/img/products/flag.png');

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(1, 10000000, 'cancelled', NOW(), '', '', '', '', '', '');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 10, 1000000);


INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(2, 1000000, 'completed', NOW(), 'User2', '1 rue de la paix', '75000', 'Paris', 'France', '+33 7 00 00 00 00');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(2, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(3, 3000000, 'pending', NOW(), '', '', '', '', '', '');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(3, 1, 3, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(4, 1000000, 'cancelled', NOW(), '', '', '', '', '', '');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(4, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(5, 5000000, 'cancelled', NOW(), '', '', '', '', '', '');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(5, 1, 5, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(6, 1000000, 'pending', NOW(), '', '', '', '', '', '');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(6, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(7, 1000000, 'completed', NOW(), 'User7', '1 rue de la paix', '75000', 'Paris', 'France', '+33 7 00 00 00 00');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(7, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(8, 1000000, 'completed', NOW(), 'User8', '1 rue de la paix', '75000', 'Paris', 'France', '+33 7 00 00 00 00');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(8, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(9, 1000000, 'completed', NOW(), 'User9', '1 rue de la paix', '75000', 'Paris', 'France', '+33 7 00 00 00 00');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(9, 1, 1, 1000000);

INSERT INTO orders (user_id, total, status, created_at, shipping_name, shipping_address, shipping_zipcode, shipping_city, shipping_country, shipping_phone) VALUES
(10, 1000000, 'completed', NOW(), 'User10', '1 rue de la paix', '75000', 'Paris', 'France', '+33 7 00 00 00 00');
INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(10, 1, 1, 1000000);


CREATE TABLE vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) UNIQUE NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    max_uses INT NOT NULL,
    is_active BOOLEAN DEFAULT true
);

CREATE TABLE user_vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    voucher_id INT NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    order_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id),
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

INSERT INTO vouchers (code, amount, max_uses) VALUES 
('WELCOME10', 10.00, 1);
