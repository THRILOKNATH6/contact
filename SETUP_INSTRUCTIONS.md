# Database Setup Instructions

If you're encountering database schema errors, follow these steps to set up the database manually:

## Option 1: Automatic Setup (Recommended)
1. Navigate to your project directory in a web browser
2. Go to `http://your-domain/setup.php`
3. The script will automatically handle errors and continue setup

## Option 2: Manual Database Setup

### Step 1: Create Database
```sql
CREATE DATABASE IF NOT EXISTS file_management;
USE file_management;
```

### Step 2: Create Tables (Simple Version)
Copy and paste the following SQL commands into your MySQL/phpMyAdmin:

```sql
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Forms table
CREATE TABLE forms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    fields_json TEXT NOT NULL,
    created_by INT NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    allowed_roles TEXT DEFAULT 'ie,ie_incharge,ie_manager',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Records table
CREATE TABLE records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    form_id INT NOT NULL,
    user_id INT NOT NULL,
    data_json TEXT NOT NULL,
    status ENUM('draft', 'committed') DEFAULT 'draft',
    committed_by INT NULL,
    committed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Files table
CREATE TABLE files (
    id INT PRIMARY KEY AUTO_INCREMENT,
    record_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Step 3: Insert Default Admin User
```sql
INSERT INTO users (username, password_hash, role, status) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ie_manager', 'approved');
```

### Step 4: Create Indexes (Optional but Recommended)
```sql
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_incharge ON users(incharge_id);
CREATE INDEX idx_users_manager ON users(manager_id);
CREATE INDEX idx_forms_created_by ON forms(created_by);
CREATE INDEX idx_forms_status ON forms(status);
CREATE INDEX idx_records_user_status ON records(user_id, status);
CREATE INDEX idx_records_form ON records(form_id);
CREATE INDEX idx_records_committed_by ON records(committed_by);
CREATE INDEX idx_files_record ON files(record_id);
CREATE INDEX idx_files_user ON files(uploaded_by);
```

### Step 5: Create Uploads Directory
Create a directory called `uploads` in your project root and set permissions to 777:
```bash
mkdir uploads
chmod 777 uploads
```

## Default Login Credentials
- **Username**: admin
- **Password**: password

**⚠️ Important**: Change the default password after first login!

## Common Issues and Solutions

### Issue 1: Foreign Key Constraint Errors
**Solution**: Use the simple schema version (`database/schema_simple.sql`) which doesn't include foreign key constraints.

### Issue 2: Permission Denied for Uploads Directory
**Solution**: 
```bash
chmod 777 uploads
```
Or set proper ownership:
```bash
chown -R www-data:www-data uploads
```

### Issue 3: PHP Upload Settings
Add to your `.htaccess` or `php.ini`:
```
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
```

### Issue 4: Database Connection Errors
1. Check your database credentials in `config/database.php`
2. Ensure MySQL/MariaDB is running
3. Verify the database user has CREATE privileges

## Migration for Existing Installations
If you already have the database set up but need the new columns:

```sql
ALTER TABLE forms 
ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER created_by,
ADD COLUMN allowed_roles TEXT DEFAULT 'ie,ie_incharge,ie_manager' AFTER status;

UPDATE forms SET status = 'active', allowed_roles = 'ie,ie_incharge,ie_manager' WHERE status IS NULL;
```

## File Structure Check
Ensure your project has this structure:
```
project-root/
├── config/database.php
├── classes/
├── includes/
├── public/
├── database/
├── uploads/ (create this)
└── setup.php
```

## Support
If you continue to have issues:
1. Check your web server error logs
2. Enable PHP error reporting
3. Ensure all file permissions are correct
4. Verify MySQL version compatibility (5.7+ recommended)