<?php
session_start();

require_once '../config/database.php';

class SessionManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function login($username, $password) {
        $query = "SELECT id, username, email, full_name, user_level, status, password FROM users WHERE username = ? AND status = 'approved'";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$username]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_level'] = $user['user_level'];
                $_SESSION['email'] = $user['email'];
                
                $this->logActivity($user['id'], 'login', 'user', $user['id'], 'User logged in');
                return true;
            }
        }
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'user', $_SESSION['user_id'], 'User logged out');
        }
        session_destroy();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ../auth/login.php');
            exit();
        }
    }
    
    public function requireLevel($required_level) {
        $this->requireLogin();
        
        $levels = ['ie' => 1, 'ie_incharge' => 2, 'ie_manager' => 3];
        $user_level = $levels[$_SESSION['user_level']] ?? 0;
        $required = $levels[$required_level] ?? 0;
        
        if ($user_level < $required) {
            header('Location: ../dashboard/unauthorized.php');
            exit();
        }
    }
    
    public function canEdit($creator_id, $is_committed = false) {
        if ($is_committed) {
            return false; // No one can edit committed data
        }
        
        $levels = ['ie' => 1, 'ie_incharge' => 2, 'ie_manager' => 3];
        $user_level = $levels[$_SESSION['user_level']] ?? 0;
        
        // Manager can edit everything
        if ($_SESSION['user_level'] === 'ie_manager') {
            return true;
        }
        
        // Get creator's level
        $query = "SELECT user_level FROM users WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$creator_id]);
        $creator = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($creator) {
            $creator_level = $levels[$creator['user_level']] ?? 0;
            // Can edit if user level is higher than creator's level
            return $user_level > $creator_level;
        }
        
        return false;
    }
    
    public function logActivity($user_id, $action, $target_type, $target_id, $description = '') {
        $query = "INSERT INTO activity_logs (user_id, action, target_type, target_id, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$user_id, $action, $target_type, $target_id, $description, $_SERVER['REMOTE_ADDR'] ?? '']);
    }
}

// Global session manager instance
$session = new SessionManager();
?>