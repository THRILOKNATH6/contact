<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php' && basename($_SERVER['PHP_SELF']) !== 'signup.php') {
    header('Location: login.php');
    exit();
}

// Route to appropriate dashboard based on user role
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'index.php') {
    $user = getUserById($_SESSION['user_id']);
    switch ($user['role']) {
        case 'ie_manager':
            header('Location: dashboard/manager.php');
            break;
        case 'ie_incharge':
            header('Location: dashboard/incharge.php');
            break;
        case 'ie':
            header('Location: dashboard/ie.php');
            break;
        default:
            header('Location: login.php');
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File & Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">File & Record Management System</h3>
                    </div>
                    <div class="card-body text-center">
                        <p class="lead">Welcome to the File & Record Management System</p>
                        <div class="row">
                            <div class="col-md-6">
                                <a href="login.php" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="signup.php" class="btn btn-success btn-lg w-100 mb-3">
                                    <i class="fas fa-user-plus"></i> Sign Up
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>