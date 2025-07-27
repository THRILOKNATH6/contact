<?php
require_once 'config/database.php';

// Form management functions
function createForm($name, $description, $fields, $createdBy) {
    global $pdo;
    
    $fieldsJson = json_encode($fields);
    
    $sql = "INSERT INTO forms (name, description, fields_json, created_by) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$name, $description, $fieldsJson, $createdBy]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function getForms() {
    global $pdo;
    
    $sql = "SELECT f.*, u.username as created_by_name FROM forms f 
            JOIN users u ON f.created_by = u.id 
            WHERE f.is_active = TRUE 
            ORDER BY f.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getFormById($id) {
    global $pdo;
    
    $sql = "SELECT * FROM forms WHERE id = ? AND is_active = TRUE";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateForm($id, $name, $description, $fields) {
    global $pdo;
    
    $fieldsJson = json_encode($fields);
    
    $sql = "UPDATE forms SET name = ?, description = ?, fields_json = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$name, $description, $fieldsJson, $id]);
}

function deleteForm($id) {
    global $pdo;
    
    $sql = "UPDATE forms SET is_active = FALSE WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

// Record management functions
function createRecord($formId, $userId, $data) {
    global $pdo;
    
    $dataJson = json_encode($data);
    
    $sql = "INSERT INTO records (form_id, user_id, data_json) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$formId, $userId, $dataJson]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function getRecords($userId = null, $formId = null, $status = null) {
    global $pdo;
    
    $sql = "SELECT r.*, f.name as form_name, u.username as user_name, 
            c.username as committed_by_name 
            FROM records r 
            JOIN forms f ON r.form_id = f.id 
            JOIN users u ON r.user_id = u.id 
            LEFT JOIN users c ON r.committed_by = c.id";
    
    $conditions = [];
    $params = [];
    
    if ($userId) {
        $conditions[] = "r.user_id = ?";
        $params[] = $userId;
    }
    
    if ($formId) {
        $conditions[] = "r.form_id = ?";
        $params[] = $formId;
    }
    
    if ($status) {
        $conditions[] = "r.status = ?";
        $params[] = $status;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getRecordById($id) {
    global $pdo;
    
    $sql = "SELECT r.*, f.name as form_name, f.fields_json, u.username as user_name,
            c.username as committed_by_name 
            FROM records r 
            JOIN forms f ON r.form_id = f.id 
            JOIN users u ON r.user_id = u.id 
            LEFT JOIN users c ON r.committed_by = c.id 
            WHERE r.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateRecord($id, $data) {
    global $pdo;
    
    $dataJson = json_encode($data);
    
    $sql = "UPDATE records SET data_json = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$dataJson, $id]);
}

function commitRecord($id, $committedBy) {
    global $pdo;
    
    $sql = "UPDATE records SET status = 'committed', committed_by = ?, committed_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$committedBy, $id]);
}

function uncommitRecord($id) {
    global $pdo;
    
    $sql = "UPDATE records SET status = 'draft', committed_by = NULL, committed_at = NULL WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

function deleteRecord($id) {
    global $pdo;
    
    $sql = "DELETE FROM records WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

// File management functions
function uploadFile($recordId, $file, $uploadedBy) {
    global $pdo;
    
    $uploadDir = 'uploads/';
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $sql = "INSERT INTO files (record_id, filename, original_filename, filepath, file_size, mime_type, uploaded_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([
                $recordId, 
                $filename, 
                $file['name'], 
                $filepath, 
                $file['size'], 
                $file['type'], 
                $uploadedBy
            ]);
            return $pdo->lastInsertId();
        } catch (PDOException $e) {
            unlink($filepath);
            return false;
        }
    }
    
    return false;
}

function getFilesByRecord($recordId) {
    global $pdo;
    
    $sql = "SELECT f.*, u.username as uploaded_by_name 
            FROM files f 
            JOIN users u ON f.uploaded_by = u.id 
            WHERE f.record_id = ? 
            ORDER BY f.uploaded_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recordId]);
    return $stmt->fetchAll();
}

function deleteFile($id) {
    global $pdo;
    
    $sql = "SELECT filepath FROM files WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $file = $stmt->fetch();
    
    if ($file && file_exists($file['filepath'])) {
        unlink($file['filepath']);
    }
    
    $sql = "DELETE FROM files WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$id]);
}

// Utility functions
function formatFileSize($bytes) {
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

function getRoleDisplayName($role) {
    switch ($role) {
        case 'ie_manager':
            return 'IE Manager';
        case 'ie_incharge':
            return 'IE Incharge';
        case 'ie':
            return 'IE';
        default:
            return ucfirst($role);
    }
}

function getStatusBadge($status) {
    switch ($status) {
        case 'pending':
            return '<span class="badge bg-warning">Pending</span>';
        case 'approved':
            return '<span class="badge bg-success">Approved</span>';
        case 'rejected':
            return '<span class="badge bg-danger">Rejected</span>';
        case 'draft':
            return '<span class="badge bg-secondary">Draft</span>';
        case 'committed':
            return '<span class="badge bg-primary">Committed</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>