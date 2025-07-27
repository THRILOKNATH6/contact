<?php
require_once '../config/database.php';

class User {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function register($username, $password, $role, $incharge_id = null) {
        // Check if username exists
        $check_query = "SELECT id FROM users WHERE username = :username";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':username', $username);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            return false; // Username already exists
        }
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $status = 'pending'; // All registrations need manager approval
        
        // Get manager_id based on role and incharge
        $manager_id = null;
        if ($role === 'ie' && $incharge_id) {
            $manager_query = "SELECT manager_id FROM users WHERE id = :incharge_id";
            $manager_stmt = $this->conn->prepare($manager_query);
            $manager_stmt->bindParam(':incharge_id', $incharge_id);
            $manager_stmt->execute();
            if ($manager_stmt->rowCount() > 0) {
                $manager_data = $manager_stmt->fetch(PDO::FETCH_ASSOC);
                $manager_id = $manager_data['manager_id'];
            }
        } elseif ($role === 'ie_incharge') {
            // For IE Incharge, we need to select a manager
            $manager_query = "SELECT id FROM users WHERE role = 'ie_manager' LIMIT 1";
            $manager_stmt = $this->conn->prepare($manager_query);
            $manager_stmt->execute();
            if ($manager_stmt->rowCount() > 0) {
                $manager_data = $manager_stmt->fetch(PDO::FETCH_ASSOC);
                $manager_id = $manager_data['id'];
            }
        }
        
        $query = "INSERT INTO users (username, password_hash, role, status, incharge_id, manager_id, created_at) 
                  VALUES (:username, :password_hash, :role, :status, :incharge_id, :manager_id, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':incharge_id', $incharge_id);
        $stmt->bindParam(':manager_id', $manager_id);
        
        return $stmt->execute();
    }
    
    public function getPendingUsers() {
        $query = "SELECT u.*, i.username as incharge_name 
                  FROM users u 
                  LEFT JOIN users i ON u.incharge_id = i.id 
                  WHERE u.status = 'pending' 
                  ORDER BY u.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function approveUser($user_id) {
        $query = "UPDATE users SET status = 'approved' WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    public function rejectUser($user_id) {
        $query = "UPDATE users SET status = 'rejected' WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        return $stmt->execute();
    }
    
    public function getIncharges() {
        $query = "SELECT id, username FROM users WHERE role = 'ie_incharge' AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllUsers() {
        $query = "SELECT u.*, i.username as incharge_name, m.username as manager_name 
                  FROM users u 
                  LEFT JOIN users i ON u.incharge_id = i.id 
                  LEFT JOIN users m ON u.manager_id = m.id 
                  ORDER BY u.role, u.username";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUsersByIncharge($incharge_id) {
        $query = "SELECT * FROM users WHERE incharge_id = :incharge_id AND status = 'approved'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':incharge_id', $incharge_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>