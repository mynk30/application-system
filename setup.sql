-- Create database
CREATE DATABASE IF NOT EXISTS application_system;
USE application_system;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires DATETIME DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

create table admin(
    id int primary key auto_increment,
    name varchar(100) not null,
    email varchar(100) not null unique,
    password varchar(255) not null,
    role enum('admin','staff','user') not null default 'user',
    status enum('active','inactive') not null default 'active',
    reset_token varchar(255) default null,
    reset_token_expires datetime default null,
    last_login datetime default null,
    created_at timestamp default current_timestamp,
    updated_at timestamp default current_timestamp on update current_timestamp,
    index idx_email (email)
)engine=innodb default charset=utf8mb4 collate=utf8mb4_unicode_ci;

-- insert into admin with email "mayanksoni920@gmail.com" and password "Abcd@12345", name "Mayank Soni", role "admin", status "active"  
INSERT INTO admin (name, email, password, role, status) VALUES 
('Mayank Soni', 'mayanksoni920@gmail.com', 'Abcd@12345', 'admin', 'active');

-- insert another user with another email and password and name "John Doe", role "user", status "active"  
INSERT INTO admin (name, email, password, role, status) VALUES 
('John Doe', 'johndoe@gmail.com', 'Abcd@12345', 'staff', 'active');

-- files table 
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_name VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    model_type VARCHAR(50) NOT NULL,
    model_id INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_model (model_type, model_id)
);

-- applications table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    service_type VARCHAR(50) NOT NULL,
    status ENUM('pending', 'approved', 'missing_document', 'rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES admin(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_reviewed_by (reviewed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample applications
INSERT INTO applications (user_id, name, email, phone, address, service_type, status) VALUES
(1, 'Anjali Kushwaha', 'analikushwaha@gmail.com', '1234567890', '123 Main St, City', 'Passport', 'pending'),
(2, 'John Doe', 'johndoe@gmail.com', '0987654321', '456 Other St, City', 'Visa', 'pending');