-- QuickBite - Campus Canteen Pre-Order System
-- HOW TO IMPORT: create an empty database in phpMyAdmin (XAMPP: name it "quickbite"; on a web host
-- use the database the host created for you), click on it, open the Import tab and upload this file.

SET NAMES utf8mb4;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(80)  NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('student','admin') NOT NULL DEFAULT 'student',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    category    VARCHAR(40)  NOT NULL,
    price       DECIMAL(8,2) NOT NULL,
    emoji       VARCHAR(10)  NOT NULL DEFAULT '',
    description VARCHAR(200) DEFAULT '',
    available   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(12) NOT NULL UNIQUE,
    total       DECIMAL(9,2) NOT NULL,
    status      ENUM('Pending','Preparing','Ready','Collected','Cancelled') NOT NULL DEFAULT 'Pending',
    pickup_time VARCHAR(30) NOT NULL,
    note        VARCHAR(200) DEFAULT '',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id  INT NULL,
    name     VARCHAR(100) NOT NULL,
    price    DECIMAL(8,2) NOT NULL,
    qty      INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id)  REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default admin account:  admin@quickbite.com  /  admin123   (change the password after first login!)
INSERT INTO users (name, email, password, role) VALUES
('Canteen Admin', 'admin@quickbite.com', '$2y$10$cC/gDjGA9RefGMs9FULqqOJUoBtBXhJkOgr49q.gYfNbMLgncQ9RW', 'admin');

INSERT INTO menu_items (name, category, price, emoji, description, available) VALUES
('Veg Meals',            'Meals',     60.00, '🍛', 'Rice, sambar, rasam, two poriyals, curd and papad', 1),
('Chicken Biryani',      'Meals',     90.00, '🍗', 'Seeranga samba biryani with raita and brinjal curry', 1),
('Egg Fried Rice',       'Meals',     70.00, '🍳', 'Wok-tossed rice with egg and veggies', 1),
('Curd Rice',            'Meals',     40.00, '🥣', 'Cool curd rice with pickle', 1),
('Masala Dosa',          'Tiffin',    45.00, '🥞', 'Crispy dosa with potato masala, chutney and sambar', 1),
('Idli (3 pcs)',         'Tiffin',    30.00, '🍚', 'Soft idlis with sambar and two chutneys', 1),
('Poori Masala',         'Tiffin',    40.00, '🫓', 'Two pooris with potato masala', 1),
('Pongal',               'Tiffin',    35.00, '🥘', 'Ven pongal with vada, sambar and chutney', 1),
('Samosa (2 pcs)',       'Snacks',    20.00, '🥟', 'Hot, crispy samosas with mint chutney', 1),
('French Fries',         'Snacks',    50.00, '🍟', 'Salted fries with ketchup', 1),
('Veg Puff',             'Snacks',    18.00, '🥐', 'Flaky puff with spicy veg filling', 1),
('Veg Sandwich',         'Snacks',    35.00, '🥪', 'Grilled sandwich with cheese and veggies', 1),
('Filter Coffee',        'Beverages', 15.00, '☕', 'Strong South Indian filter coffee', 1),
('Masala Tea',           'Beverages', 12.00, '🍵', 'Ginger masala chai', 1),
('Lime Juice',           'Beverages', 20.00, '🍋', 'Fresh lime juice, sweet or salt', 1),
('Cold Coffee',          'Beverages', 40.00, '🧋', 'Chilled coffee with ice cream', 1),
('Gulab Jamun (2 pcs)',  'Desserts',  25.00, '🍮', 'Warm gulab jamun in sugar syrup', 1),
('Ice Cream Cup',        'Desserts',  30.00, '🍨', 'Vanilla, chocolate or strawberry', 0);
