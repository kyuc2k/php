CREATE DATABASE nso;

USE nso;

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50),
email VARCHAR(100),
password VARCHAR(255),
google_id VARCHAR(255),
name VARCHAR(100),
picture VARCHAR(255),
port INT,
display_id INT,
status VARCHAR(20) DEFAULT 'stopped'
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