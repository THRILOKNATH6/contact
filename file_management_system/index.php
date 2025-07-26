<?php
require_once 'includes/session.php';

// If user is already logged in, redirect to dashboard
if ($session->isLoggedIn()) {
    switch ($_SESSION['user_level']) {
        case 'ie_manager':
            header('Location: pages/dashboard/manager.php');
            break;
        case 'ie_incharge':
            header('Location: pages/dashboard/incharge.php');
            break;
        case 'ie':
            header('Location: pages/dashboard/ie.php');
            break;
        default:
            header('Location: pages/auth/login.php');
    }
    exit();
}

// Redirect to login page
header('Location: pages/auth/login.php');
exit();
?>