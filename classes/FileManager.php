<?php
require_once '../config/database.php';

class FileManager {
    private $conn;
    private $upload_dir = '../uploads/';
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0777, true);
        }
    }
    
    public function uploadFile($file, $record_id, $uploaded_by) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $original_name = basename($file['name']);
        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
        $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $this->upload_dir . $unique_name;
        
        // Check file size (limit to 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            return false;
        }
        
        // Check file type (basic security)
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            return false;
        }
        
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            // Save file info to database
            $query = "INSERT INTO files (record_id, original_name, file_path, file_size, uploaded_by, uploaded_at) 
                      VALUES (:record_id, :original_name, :file_path, :file_size, :uploaded_by, NOW())";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':record_id', $record_id);
            $stmt->bindParam(':original_name', $original_name);
            $stmt->bindParam(':file_path', $unique_name);
            $stmt->bindParam(':file_size', $file['size']);
            $stmt->bindParam(':uploaded_by', $uploaded_by);
            
            if ($stmt->execute()) {
                return $this->conn->lastInsertId();
            }
        }
        
        return false;
    }
    
    public function deleteFile($file_id, $user_id) {
        // Check permissions
        $user_role = $this->getUserRole($user_id);
        
        if ($user_role === 'ie_manager') {
            // Manager can delete any file
            $query = "SELECT file_path FROM files WHERE id = :file_id";
        } else {
            // Users can only delete their own files
            $query = "SELECT file_path FROM files WHERE id = :file_id AND uploaded_by = :user_id";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':file_id', $file_id);
        if ($user_role !== 'ie_manager') {
            $stmt->bindParam(':user_id', $user_id);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            $file_path = $this->upload_dir . $file['file_path'];
            
            // Delete from database
            $delete_query = "DELETE FROM files WHERE id = :file_id";
            if ($user_role !== 'ie_manager') {
                $delete_query .= " AND uploaded_by = :user_id";
            }
            
            $delete_stmt = $this->conn->prepare($delete_query);
            $delete_stmt->bindParam(':file_id', $file_id);
            if ($user_role !== 'ie_manager') {
                $delete_stmt->bindParam(':user_id', $user_id);
            }
            
            if ($delete_stmt->execute()) {
                // Delete physical file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                return true;
            }
        }
        
        return false;
    }
    
    public function getFilesByRecord($record_id) {
        $query = "SELECT f.*, u.username as uploaded_by_name 
                  FROM files f 
                  LEFT JOIN users u ON f.uploaded_by = u.id 
                  WHERE f.record_id = :record_id 
                  ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':record_id', $record_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFilesByUser($user_id) {
        $query = "SELECT f.*, r.id as record_id, fr.name as form_name 
                  FROM files f 
                  LEFT JOIN records r ON f.record_id = r.id 
                  LEFT JOIN forms fr ON r.form_id = fr.id 
                  WHERE f.uploaded_by = :user_id 
                  ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllFiles() {
        $query = "SELECT f.*, u.username as uploaded_by_name, r.id as record_id, 
                  fr.name as form_name 
                  FROM files f 
                  LEFT JOIN users u ON f.uploaded_by = u.id 
                  LEFT JOIN records r ON f.record_id = r.id 
                  LEFT JOIN forms fr ON r.form_id = fr.id 
                  ORDER BY f.uploaded_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function downloadFile($file_id, $user_id) {
        // Check permissions
        $user_role = $this->getUserRole($user_id);
        
        $query = "SELECT f.*, r.user_id as record_owner 
                  FROM files f 
                  LEFT JOIN records r ON f.record_id = r.id 
                  WHERE f.id = :file_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':file_id', $file_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Check access permissions
            $can_access = false;
            if ($user_role === 'ie_manager') {
                $can_access = true; // Manager can access all files
            } elseif ($user_role === 'ie_incharge') {
                // Incharge can access files from their IEs
                $incharge_query = "SELECT id FROM users WHERE incharge_id = :user_id AND id = :record_owner";
                $incharge_stmt = $this->conn->prepare($incharge_query);
                $incharge_stmt->bindParam(':user_id', $user_id);
                $incharge_stmt->bindParam(':record_owner', $file['record_owner']);
                $incharge_stmt->execute();
                $can_access = ($incharge_stmt->rowCount() > 0) || ($file['record_owner'] == $user_id);
            } else {
                $can_access = ($file['record_owner'] == $user_id); // IE can access own files
            }
            
            if ($can_access) {
                $file_path = $this->upload_dir . $file['file_path'];
                if (file_exists($file_path)) {
                    return [
                        'path' => $file_path,
                        'name' => $file['original_name'],
                        'size' => $file['file_size']
                    ];
                }
            }
        }
        
        return false;
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
    
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
?>