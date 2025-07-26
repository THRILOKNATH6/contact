<?php
/**
 * File Management System Setup Script
 * This script helps with initial setup and testing
 */

// Check if already set up
if (file_exists('config/database.php')) {
    $db_content = file_get_contents('config/database.php');
    if (strpos($db_content, 'your_mysql_username') === false) {
        echo "<h2>✅ System appears to be already configured!</h2>";
        echo "<p><a href='index.php'>Go to Login Page</a></p>";
        echo "<hr>";
    }
}

echo "<h1>File Management System - Setup</h1>";

// Test PHP version
if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
    echo "<p>✅ PHP Version: " . PHP_VERSION . " (Compatible)</p>";
} else {
    echo "<p>❌ PHP Version: " . PHP_VERSION . " (Requires PHP 7.4+)</p>";
}

// Test required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'json', 'fileinfo'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p>✅ Extension '{$ext}': Loaded</p>";
    } else {
        echo "<p>❌ Extension '{$ext}': Not loaded</p>";
    }
}

// Test directory permissions
$upload_dir = 'assets/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (is_writable($upload_dir)) {
    echo "<p>✅ Upload directory: Writable</p>";
} else {
    echo "<p>❌ Upload directory: Not writable (chmod 755 {$upload_dir})</p>";
}

// Test database connection
if ($_POST && isset($_POST['test_db'])) {
    $host = $_POST['db_host'];
    $dbname = $_POST['db_name'];
    $username = $_POST['db_username'];
    $password = $_POST['db_password'];
    
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>✅ Database connection successful!</p>";
        echo "</div>";
        
        // Check if tables exist
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Database tables found</p>";
            
            // Check for admin user
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                echo "<p>✅ Admin user exists</p>";
                echo "<p><strong>Ready to use!</strong> <a href='index.php'>Go to Login</a></p>";
            } else {
                echo "<p>⚠️ Admin user not found. Please run the database schema.</p>";
            }
        } else {
            echo "<p>⚠️ Database tables not found. Please import database/schema.sql</p>";
        }
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - File Management System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .card { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        code { background: #f1f1f1; padding: 2px 4px; border-radius: 3px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Database Configuration Test</h2>
    <p>Test your database connection settings:</p>
    
    <form method="POST">
        <div class="form-group">
            <label for="db_host">Database Host:</label>
            <input type="text" id="db_host" name="db_host" value="localhost" required>
        </div>
        
        <div class="form-group">
            <label for="db_name">Database Name:</label>
            <input type="text" id="db_name" name="db_name" value="file_management_db" required>
        </div>
        
        <div class="form-group">
            <label for="db_username">Database Username:</label>
            <input type="text" id="db_username" name="db_username" value="root" required>
        </div>
        
        <div class="form-group">
            <label for="db_password">Database Password:</label>
            <input type="password" id="db_password" name="db_password" value="">
        </div>
        
        <button type="submit" name="test_db">Test Database Connection</button>
    </form>
</div>

<div class="card">
    <h2>Setup Instructions</h2>
    <ol>
        <li><strong>Create Database:</strong> Create a MySQL database named <code>file_management_db</code></li>
        <li><strong>Import Schema:</strong> Run the SQL from <code>database/schema.sql</code></li>
        <li><strong>Update Config:</strong> Edit <code>config/database.php</code> with your database credentials</li>
        <li><strong>Set Permissions:</strong> Ensure <code>assets/uploads/</code> is writable</li>
        <li><strong>Test Connection:</strong> Use the form above to test your database connection</li>
    </ol>
</div>

<div class="card">
    <h2>Default Login Credentials</h2>
    <p>After setup, use these credentials to log in:</p>
    <ul>
        <li><strong>Username:</strong> admin</li>
        <li><strong>Password:</strong> password</li>
        <li><strong>Level:</strong> IE Manager</li>
    </ul>
    <p><em>Change the default password immediately after first login!</em></p>
</div>

<div class="card">
    <h2>Manual Database Setup (if needed)</h2>
    <p>If you need to set up the database manually, run these commands:</p>
    <pre><code>mysql -u root -p
CREATE DATABASE file_management_db;
USE file_management_db;
SOURCE database/schema.sql;
QUIT;</code></pre>
</div>

<div class="card">
    <h2>Quick Test</h2>
    <p>After setup is complete:</p>
    <ol>
        <li><a href="index.php" target="_blank">Try logging in</a> with admin/password</li>
        <li>Create a new user account from the signup page</li>
        <li>Test file upload functionality</li>
        <li>Create a simple form as manager</li>
        <li>Test the commit functionality</li>
    </ol>
</div>

<div class="card">
    <h2>Need Help?</h2>
    <p>If you encounter issues:</p>
    <ul>
        <li>Check PHP error logs</li>
        <li>Verify file permissions</li>
        <li>Confirm MySQL service is running</li>
        <li>Review the README.md file</li>
    </ul>
</div>

<p style="text-align: center; margin-top: 40px;">
    <a href="index.php">Go to Application</a> | 
    <a href="README.md">View Documentation</a>
</p>

</body>
</html>