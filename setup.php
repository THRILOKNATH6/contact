<?php
/**
 * Database Setup Script for File Management System
 * Run this file once to set up the database and create the default admin user
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'file_management';

try {
    // Connect to MySQL server (without database)
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.<br>";
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $database");
    echo "Database '$database' created successfully.<br>";
    
    // Use the database
    $pdo->exec("USE $database");
    
    // Read and execute schema file
    $schema = file_get_contents('database/schema.sql');
    
    // Split the schema into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
        }
    }
    
    echo "Database schema created successfully.<br>";
    
    // Create uploads directory
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "Uploads directory created successfully.<br>";
    }
    
    echo "<br><strong>Setup completed successfully!</strong><br>";
    echo "Default admin user created:<br>";
    echo "Username: admin<br>";
    echo "Password: password<br><br>";
    echo "You can now access the system at: <a href='public/login.php'>Login Page</a><br>";
    echo "<br><strong>Important:</strong> Please change the default admin password after first login.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    die();
}
?>