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
reset_token_expiry DATETIME
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