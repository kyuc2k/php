CREATE DATABASE nso;

USE nso;

CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(50),
password VARCHAR(255),
port INT,
display_id INT,
status VARCHAR(20) DEFAULT 'stopped'
);