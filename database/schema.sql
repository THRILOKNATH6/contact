-- File Management System Database Schema

CREATE DATABASE IF NOT EXISTS file_management;
USE file_management;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('ie', 'ie_incharge', 'ie_manager') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    incharge_id INT NULL,
    manager_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (incharge_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Forms table (for dynamic forms created by managers)
CREATE TABLE forms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    fields_json TEXT NOT NULL,
    created_by INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    allowed_roles TEXT DEFAULT 'ie,ie_incharge,ie_manager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Records table (form submissions)
CREATE TABLE records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    form_id INT NOT NULL,
    user_id INT NOT NULL,
    data_json TEXT NOT NULL,
    status ENUM('draft', 'committed') DEFAULT 'draft',
    committed_by INT NULL,
    committed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (committed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Files table
CREATE TABLE files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (record_id) REFERENCES records(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default manager user
INSERT INTO users (username, password_hash, role, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ie_manager', 'approved');
-- Password: password

-- Create indexes for better performance
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_records_user_status ON records(user_id, status);
CREATE INDEX idx_records_form ON records(form_id);
CREATE INDEX idx_files_record ON files(record_id);
CREATE INDEX idx_files_user ON files(uploaded_by);