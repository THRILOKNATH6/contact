<?php
// Setup script for File & Record Management System
// This script helps with initial configuration

echo "=== File & Record Management System Setup ===\n\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    echo "❌ Error: PHP 7.4 or higher is required. Current version: " . PHP_VERSION . "\n";
    exit(1);
}

echo "✅ PHP version: " . PHP_VERSION . "\n";

// Check required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension '$ext' is loaded\n";
    } else {
        echo "❌ Error: Extension '$ext' is not loaded\n";
        exit(1);
    }
}

echo "\n=== Database Configuration ===\n";

// Check if config file exists
if (!file_exists('config/database.php')) {
    echo "❌ Error: config/database.php not found. Please create it first.\n";
    exit(1);
}

// Include database config
require_once 'config/database.php';

try {
    // Test database connection
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
    // Check if tables exist
    $tables = ['users', 'forms', 'records', 'files'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' exists\n";
        } else {
            echo "⚠️  Table '$table' does not exist (will be created on first access)\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration in config/database.php\n";
    exit(1);
}

echo "\n=== File System Check ===\n";

// Check uploads directory
if (!file_exists('uploads')) {
    if (mkdir('uploads', 0755, true)) {
        echo "✅ Created uploads directory\n";
    } else {
        echo "❌ Failed to create uploads directory\n";
        exit(1);
    }
} else {
    echo "✅ Uploads directory exists\n";
}

if (is_writable('uploads')) {
    echo "✅ Uploads directory is writable\n";
} else {
    echo "❌ Uploads directory is not writable\n";
    echo "Please run: chmod 755 uploads/\n";
}

echo "\n=== Initial Setup ===\n";

// Check if any approved managers exist
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ie_manager' AND status = 'approved'");
$managerCount = $stmt->fetchColumn();

if ($managerCount == 0) {
    echo "⚠️  No approved IE Managers found\n";
    echo "\nTo create the first manager:\n";
    echo "1. Register a new account with role 'IE Manager' at: " . (isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}" : "your-domain") . "/signup.php\n";
    echo "2. Manually approve the account in the database:\n";
    echo "   UPDATE users SET status = 'approved' WHERE role = 'ie_manager' LIMIT 1;\n";
    echo "3. Login and start using the system\n";
} else {
    echo "✅ Found $managerCount approved IE Manager(s)\n";
}

echo "\n=== Setup Complete ===\n";
echo "✅ System is ready to use!\n";
echo "\nNext steps:\n";
echo "1. Access the application: " . (isset($_SERVER['HTTP_HOST']) ? "http://{$_SERVER['HTTP_HOST']}" : "your-domain") . "\n";
echo "2. Login with an approved manager account\n";
echo "3. Create forms and start managing records\n";
echo "\nFor more information, see README.md\n";
?>