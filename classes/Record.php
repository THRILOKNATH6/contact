<?php
require_once '../config/database.php';

class Record {
    public $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createRecord($form_id, $user_id, $data) {
        $data_json = json_encode($data);
        
        $query = "INSERT INTO records (form_id, user_id, data_json, status, created_at) 
                  VALUES (:form_id, :user_id, :data_json, 'draft', NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':form_id', $form_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':data_json', $data_json);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }
    
    public function updateRecord($record_id, $data, $user_id) {
        // Check if user owns the record and it's not committed
        $check_query = "SELECT status, user_id FROM records WHERE id = :record_id";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':record_id', $record_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $record = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Only allow updates if it's a draft and user owns it, or if user is manager
            $user_role = $this->getUserRole($user_id);
            if (($record['status'] === 'draft' && $record['user_id'] == $user_id) || $user_role === 'ie_manager') {
                $data_json = json_encode($data);
                
                $query = "UPDATE records SET data_json = :data_json, updated_at = NOW() 
                          WHERE id = :record_id";
                
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':data_json', $data_json);
                $stmt->bindParam(':record_id', $record_id);
                
                return $stmt->execute();
            }
        }
        return false;
    }
    
    public function commitRecord($record_id, $committed_by) {
        $query = "UPDATE records SET status = 'committed', committed_by = :committed_by, 
                  committed_at = NOW() WHERE id = :record_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->bindParam(':committed_by', $committed_by);
        
        return $stmt->execute();
    }
    
    public function uncommitRecord($record_id) {
        $query = "UPDATE records SET status = 'draft', committed_by = NULL, 
                  committed_at = NULL WHERE id = :record_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        
        return $stmt->execute();
    }
    
    public function deleteRecord($record_id, $user_id) {
        // Check permissions
        $user_role = $this->getUserRole($user_id);
        
        if ($user_role === 'ie_manager') {
            // Manager can delete any record
            $query = "DELETE FROM records WHERE id = :record_id";
        } else {
            // Users can only delete their own draft records
            $query = "DELETE FROM records WHERE id = :record_id AND user_id = :user_id AND status = 'draft'";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        if ($user_role !== 'ie_manager') {
            $stmt->bindParam(':user_id', $user_id);
        }
        
        return $stmt->execute();
    }
    
    public function getRecordsByUser($user_id) {
        $query = "SELECT r.*, f.name as form_name, u.username as committed_by_name 
                  FROM records r 
                  LEFT JOIN forms f ON r.form_id = f.id 
                  LEFT JOIN users u ON r.committed_by = u.id 
                  WHERE r.user_id = :user_id 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as &$record) {
            $record['data'] = json_decode($record['data_json'], true);
        }
        
        return $records;
    }
    
    public function getAllRecords() {
        $query = "SELECT r.*, f.name as form_name, u.username as user_name, 
                  c.username as committed_by_name 
                  FROM records r 
                  LEFT JOIN forms f ON r.form_id = f.id 
                  LEFT JOIN users u ON r.user_id = u.id 
                  LEFT JOIN users c ON r.committed_by = c.id 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as &$record) {
            $record['data'] = json_decode($record['data_json'], true);
        }
        
        return $records;
    }
    
    public function getRecordsByIncharge($incharge_id) {
        $query = "SELECT r.*, f.name as form_name, u.username as user_name, 
                  c.username as committed_by_name 
                  FROM records r 
                  LEFT JOIN forms f ON r.form_id = f.id 
                  LEFT JOIN users u ON r.user_id = u.id 
                  LEFT JOIN users c ON r.committed_by = c.id 
                  WHERE u.incharge_id = :incharge_id 
                  ORDER BY r.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':incharge_id', $incharge_id);
        $stmt->execute();
        
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($records as &$record) {
            $record['data'] = json_decode($record['data_json'], true);
        }
        
        return $records;
    }
    
    public function getRecordById($record_id) {
        $query = "SELECT r.*, f.name as form_name, f.fields_json, u.username as user_name 
                  FROM records r 
                  LEFT JOIN forms f ON r.form_id = f.id 
                  LEFT JOIN users u ON r.user_id = u.id 
                  WHERE r.id = :record_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            $record['data'] = json_decode($record['data_json'], true);
            $record['form_fields'] = json_decode($record['fields_json'], true);
            return $record;
        }
        return null;
    }
    
    private function getUserRole($user_id) {
        $query = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user['role'];
        }
        return null;
    }
}
?>