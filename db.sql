CREATE DATABASE micro_saas;

USE micro_saas;

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(200),
email VARCHAR(100),
password VARCHAR(255),
google_id VARCHAR(255),
picture VARCHAR(255),
email_verified TINYINT(1) DEFAULT 0,
verification_code VARCHAR(255),
verification_expiry DATETIME,
port INT,
display_id INT,
status VARCHAR(20) DEFAULT 'stopped',
session_id VARCHAR(255),
reset_token VARCHAR(255),
reset_token_expiry DATETIME,
balance DECIMAL(10, 2) DEFAULT 0.00
);


CREATE TABLE `instances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `container_name` varchar(100) DEFAULT NULL,
  `port` int DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci

CREATE TABLE vm_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  vm_name VARCHAR(255),
  token VARCHAR(255),
  expires_at DATETIME
);

CREATE TABLE user_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(255) NOT NULL,
  description TEXT,
  ip_address VARCHAR(45),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE uploaded_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_size INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE deposits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount DECIMAL(10, 2) NOT NULL,
  status VARCHAR(20) DEFAULT 'pending',
  vnp_txn_ref VARCHAR(255),
  vnp_response_code VARCHAR(10),
  vnp_transaction_no VARCHAR(255),
  vnp_bank_code VARCHAR(50),
  vnp_pay_date VARCHAR(20),
  vnp_card_type VARCHAR(50),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rental_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  duration_months INT NOT NULL,
  price DECIMAL(10, 2) NOT NULL,
  description TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO rental_packages (name, duration_months, price, description) VALUES
('Gói 1 tháng', 1, 5000.00, 'Thuê VPS trong 1 tháng'),
('Gói 3 tháng', 3, 10000.00, 'Thuê VPS trong 3 tháng - Tiết kiệm 33%'),
('Gói 6 tháng', 6, 20000.00, 'Thuê VPS trong 6 tháng - Tiết kiệm 50%');

CREATE TABLE rentals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  package_id INT NOT NULL,
  start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  end_date DATETIME,
  status VARCHAR(20) DEFAULT 'active',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (package_id) REFERENCES rental_packages(id)
);