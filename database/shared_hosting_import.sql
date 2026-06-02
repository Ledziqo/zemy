SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS demo_requests;
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS restaurant_tables;
DROP TABLE IF EXISTS menu_items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS restaurants;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS cache_locks;
DROP TABLE IF EXISTS cache;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS password_reset_tokens;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  email_verified_at TIMESTAMP NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','restaurant_owner','staff') NOT NULL DEFAULT 'staff',
  restaurant_id BIGINT UNSIGNED NULL,
  remember_token VARCHAR(100) NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  INDEX users_restaurant_id_index (restaurant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
  email VARCHAR(255) NOT NULL PRIMARY KEY,
  token VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sessions (
  id VARCHAR(255) NOT NULL PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  payload LONGTEXT NOT NULL,
  last_activity INT NOT NULL,
  INDEX sessions_user_id_index (user_id),
  INDEX sessions_last_activity_index (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  value MEDIUMTEXT NOT NULL,
  expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cache_locks (
  `key` VARCHAR(255) NOT NULL PRIMARY KEY,
  owner VARCHAR(255) NOT NULL,
  expiration INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  queue VARCHAR(255) NOT NULL,
  payload LONGTEXT NOT NULL,
  attempts TINYINT UNSIGNED NOT NULL,
  reserved_at INT UNSIGNED NULL,
  available_at INT UNSIGNED NOT NULL,
  created_at INT UNSIGNED NOT NULL,
  INDEX jobs_queue_index (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE restaurants (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  phone VARCHAR(255) NULL,
  email VARCHAR(255) NULL,
  location VARCHAR(255) NULL,
  logo_path VARCHAR(255) NULL,
  cover_image_path VARCHAR(255) NULL,
  primary_color VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  settings JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT categories_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menu_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_path VARCHAR(255) NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT menu_items_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT menu_items_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE restaurant_tables (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  table_number VARCHAR(255) NOT NULL,
  table_name VARCHAR(255) NULL,
  qr_code_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY restaurant_tables_restaurant_id_table_number_unique (restaurant_id, table_number),
  CONSTRAINT restaurant_tables_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  table_id BIGINT UNSIGNED NULL,
  table_number VARCHAR(255) NOT NULL,
  customer_name VARCHAR(255) NULL,
  customer_phone VARCHAR(255) NULL,
  note TEXT NULL,
  status ENUM('new','preparing','served','paid','completed','cancelled') NOT NULL DEFAULT 'new',
  payment_method VARCHAR(255) NULL,
  payment_status ENUM('unpaid','pending','paid','failed','refunded') NOT NULL DEFAULT 'unpaid',
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  service_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
  tax DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT orders_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT orders_table_id_foreign FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  note TEXT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT order_items_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT order_items_menu_item_id_foreign FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  table_id BIGINT UNSIGNED NULL,
  table_number VARCHAR(255) NOT NULL,
  type ENUM('call_waiter','request_bill','request_water','other') NOT NULL,
  note TEXT NULL,
  status ENUM('pending','acknowledged','completed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT service_requests_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT service_requests_table_id_foreign FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE demo_requests (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  restaurant_name VARCHAR(255) NOT NULL,
  phone VARCHAR(255) NOT NULL,
  email VARCHAR(255) NULL,
  location VARCHAR(255) NULL,
  message TEXT NULL,
  status ENUM('new','contacted','converted','closed') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  plan_name VARCHAR(255) NOT NULL,
  monthly_price DECIMAL(10,2) NULL,
  status ENUM('active','unpaid','trial','cancelled') NOT NULL DEFAULT 'trial',
  starts_at DATE NULL,
  ends_at DATE NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT subscriptions_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  restaurant_id BIGINT UNSIGNED NOT NULL,
  order_id BIGINT UNSIGNED NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(255) NOT NULL,
  status ENUM('unpaid','pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  reference VARCHAR(255) NULL,
  metadata JSON NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  CONSTRAINT payments_restaurant_id_foreign FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE,
  CONSTRAINT payments_order_id_foreign FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO restaurants (id, name, slug, phone, email, location, primary_color, is_active, settings, created_at, updated_at)
VALUES (1, 'Bole Bistro Demo', 'bole-bistro', '+251 911 000 000', 'hello@bolebistro.test', 'Bole, Addis Ababa', '#D89B35', 1, '{"service_charge_percentage":0,"vat_percentage":0}', NOW(), NOW());

INSERT INTO users (name, email, password, role, restaurant_id, created_at, updated_at) VALUES
('ZemTab Admin', 'admin@zemtab.test', '$2y$12$2dry7dPnG7EbP54KQW0dwefJMvY7dOVCYOj.wcSeVt6Cq6s/wotTq', 'admin', NULL, NOW(), NOW()),
('Bole Bistro Owner', 'owner@bolebistro.test', '$2y$12$2dry7dPnG7EbP54KQW0dwefJMvY7dOVCYOj.wcSeVt6Cq6s/wotTq', 'restaurant_owner', 1, NOW(), NOW()),
('Bole Bistro Staff', 'staff@bolebistro.test', '$2y$12$2dry7dPnG7EbP54KQW0dwefJMvY7dOVCYOj.wcSeVt6Cq6s/wotTq', 'staff', 1, NOW(), NOW());

INSERT INTO categories (id, restaurant_id, name, sort_order, is_active, created_at, updated_at) VALUES
(1,1,'Breakfast',1,1,NOW(),NOW()),(2,1,'Burgers',2,1,NOW(),NOW()),(3,1,'Pizza',3,1,NOW(),NOW()),(4,1,'Local Food',4,1,NOW(),NOW()),(5,1,'Hot Drinks',5,1,NOW(),NOW()),(6,1,'Juices',6,1,NOW(),NOW()),(7,1,'Desserts',7,1,NOW(),NOW());

INSERT INTO menu_items (restaurant_id, category_id, name, description, price, is_available, is_featured, sort_order, created_at, updated_at) VALUES
(1,1,'Egg Sandwich','Toasted bread with eggs, tomato, and house sauce.',180,1,1,1,NOW(),NOW()),
(1,1,'Ful Special','Warm ful with fresh herbs, onion, tomato, and chili.',220,1,0,2,NOW(),NOW()),
(1,1,'Pancake with Honey','Soft pancakes with Ethiopian honey.',250,1,0,3,NOW(),NOW()),
(1,2,'Special Beef Burger','Beef patty, cheese, lettuce, tomato, and signature sauce.',420,1,1,1,NOW(),NOW()),
(1,2,'Chicken Burger','Grilled chicken, crisp lettuce, and creamy sauce.',390,1,0,2,NOW(),NOW()),
(1,2,'Double Cheese Burger','Two beef patties with melted cheese.',520,1,0,3,NOW(),NOW()),
(1,3,'Margherita Pizza','Tomato, mozzarella, and basil.',550,1,1,1,NOW(),NOW()),
(1,3,'Chicken Pizza','Chicken, peppers, onion, and mozzarella.',680,1,0,2,NOW(),NOW()),
(1,3,'Meat Lovers Pizza','Loaded beef, chicken, sausage, and cheese.',750,1,0,3,NOW(),NOW()),
(1,4,'Beef Tibs','Pan-seared beef with rosemary, jalapeno, and injera.',550,1,1,1,NOW(),NOW()),
(1,4,'Kitfo','Seasoned minced beef with mitmita and ayib.',650,1,0,2,NOW(),NOW()),
(1,4,'Shiro Tegabino','Rich chickpea stew served hot with injera.',300,1,0,3,NOW(),NOW()),
(1,5,'Macchiato','Classic Ethiopian macchiato.',90,1,1,1,NOW(),NOW()),
(1,5,'Ethiopian Coffee','Fresh brewed buna.',80,1,0,2,NOW(),NOW()),
(1,5,'Tea','Hot spiced tea.',60,1,0,3,NOW(),NOW()),
(1,6,'Mango Juice','Fresh mango juice.',180,1,1,1,NOW(),NOW()),
(1,6,'Avocado Juice','Creamy avocado juice.',180,1,0,2,NOW(),NOW()),
(1,6,'Mixed Juice','Layered seasonal fruit juice.',220,1,0,3,NOW(),NOW()),
(1,7,'Chocolate Cake','Moist chocolate cake slice.',260,1,1,1,NOW(),NOW()),
(1,7,'Tiramisu Cup','Coffee cream dessert cup.',320,1,0,2,NOW(),NOW());

INSERT INTO restaurant_tables (restaurant_id, table_number, table_name, is_active, created_at, updated_at) VALUES
(1,'1','Table 1',1,NOW(),NOW()),(1,'2','Table 2',1,NOW(),NOW()),(1,'3','Table 3',1,NOW(),NOW()),(1,'4','Table 4',1,NOW(),NOW()),(1,'5','Table 5',1,NOW(),NOW()),
(1,'6','Table 6',1,NOW(),NOW()),(1,'7','Table 7',1,NOW(),NOW()),(1,'8','Table 8',1,NOW(),NOW()),(1,'9','Table 9',1,NOW(),NOW()),(1,'10','Table 10',1,NOW(),NOW());

INSERT INTO subscriptions (restaurant_id, plan_name, monthly_price, status, starts_at, ends_at, created_at, updated_at)
VALUES (1, 'Pro', 5000, 'trial', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH), NOW(), NOW());

SET FOREIGN_KEY_CHECKS=1;
