<?php
require_once 'config/database.php';

// User authentication functions
function registerUser($username, $email, $password, $role) {
    global $pdo;
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$username, $email, $password_hash, $role]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function loginUser($username, $password) {
    global $pdo;
    
    $sql = "SELECT * FROM users WHERE username = ? AND status = 'approved'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    
    return false;
}

function logoutUser() {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

function getUserById($id) {
    global $pdo;
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPendingUsers() {
    global $pdo;
    
    $sql = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function approveUser($userId) {
    global $pdo;
    
    $sql = "UPDATE users SET status = 'approved' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId]);
}

function rejectUser($userId) {
    global $pdo;
    
    $sql = "UPDATE users SET status = 'rejected' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId]);
}

function getUsersByRole($role) {
    global $pdo;
    
    $sql = "SELECT * FROM users WHERE role = ? AND status = 'approved' ORDER BY username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$role]);
    return $stmt->fetchAll();
}

function getUsersUnderIncharge($inchargeId) {
    global $pdo;
    
    $sql = "SELECT * FROM users WHERE incharge_id = ? AND status = 'approved' ORDER BY username";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$inchargeId]);
    return $stmt->fetchAll();
}

function assignUserToIncharge($userId, $inchargeId) {
    global $pdo;
    
    $sql = "UPDATE users SET incharge_id = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$inchargeId, $userId]);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireRole($role) {
    if (!isLoggedIn() || $_SESSION['role'] !== $role) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAnyRole($roles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $roles)) {
        header('Location: ../login.php');
        exit();
    }
}

function getCurrentUser() {
    if (isLoggedIn()) {
        return getUserById($_SESSION['user_id']);
    }
    return null;
}
?>