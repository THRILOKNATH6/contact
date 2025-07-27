<?php
require_once '../config/database.php';

class Form {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createForm($name, $description, $fields, $created_by, $allowed_roles = 'ie,ie_incharge,ie_manager', $status = 'active') {
        $fields_json = json_encode($fields);
        
        $query = "INSERT INTO forms (name, description, fields_json, created_by, allowed_roles, status, created_at) 
                  VALUES (:name, :description, :fields_json, :created_by, :allowed_roles, :status, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':fields_json', $fields_json);
        $stmt->bindParam(':created_by', $created_by);
        $stmt->bindParam(':allowed_roles', $allowed_roles);
        $stmt->bindParam(':status', $status);
        
        return $stmt->execute();
    }
    
    public function updateForm($form_id, $name, $description, $fields, $allowed_roles = null, $status = null) {
        $fields_json = json_encode($fields);
        
        $query = "UPDATE forms SET name = :name, description = :description, 
                  fields_json = :fields_json, updated_at = NOW()";
        
        if ($allowed_roles !== null) {
            $query .= ", allowed_roles = :allowed_roles";
        }
        if ($status !== null) {
            $query .= ", status = :status";
        }
        
        $query .= " WHERE id = :form_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':fields_json', $fields_json);
        $stmt->bindParam(':form_id', $form_id);
        
        if ($allowed_roles !== null) {
            $stmt->bindParam(':allowed_roles', $allowed_roles);
        }
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        
        return $stmt->execute();
    }
    
    public function deleteForm($form_id) {
        // First delete all records associated with this form
        $delete_records = "DELETE FROM records WHERE form_id = :form_id";
        $stmt = $this->conn->prepare($delete_records);
        $stmt->bindParam(':form_id', $form_id);
        $stmt->execute();
        
        // Then delete the form
        $query = "DELETE FROM forms WHERE id = :form_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':form_id', $form_id);
        
        return $stmt->execute();
    }
    
    public function getAllForms($role = null) {
        $query = "SELECT f.*, u.username as created_by_name 
                  FROM forms f 
                  LEFT JOIN users u ON f.created_by = u.id";
        
        if ($role && $role !== 'ie_manager') {
            $query .= " WHERE f.status = 'active' AND FIND_IN_SET(:role, f.allowed_roles) > 0";
        }
        
        $query .= " ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        if ($role && $role !== 'ie_manager') {
            $stmt->bindParam(':role', $role);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFormsWithFilters($filters = []) {
        $query = "SELECT f.*, u.username as created_by_name, 
                  COUNT(r.id) as record_count,
                  COUNT(CASE WHEN r.status = 'draft' THEN 1 END) as draft_count,
                  COUNT(CASE WHEN r.status = 'committed' THEN 1 END) as committed_count
                  FROM forms f 
                  LEFT JOIN users u ON f.created_by = u.id 
                  LEFT JOIN records r ON f.id = r.form_id";
        
        $where_conditions = [];
        $params = [];
        
        // Filter by form name
        if (!empty($filters['form_name'])) {
            $where_conditions[] = "f.name LIKE :form_name";
            $params[':form_name'] = '%' . $filters['form_name'] . '%';
        }
        
        // Filter by status
        if (!empty($filters['status'])) {
            $where_conditions[] = "f.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        // Filter by creator
        if (!empty($filters['created_by'])) {
            $where_conditions[] = "f.created_by = :created_by";
            $params[':created_by'] = $filters['created_by'];
        }
        
        // Filter by date range
        if (!empty($filters['date_from'])) {
            $where_conditions[] = "DATE(f.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = "DATE(f.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // Filter by allowed roles
        if (!empty($filters['allowed_role'])) {
            $where_conditions[] = "FIND_IN_SET(:allowed_role, f.allowed_roles) > 0";
            $params[':allowed_role'] = $filters['allowed_role'];
        }
        
        if (!empty($where_conditions)) {
            $query .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $query .= " GROUP BY f.id ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getFormById($form_id) {
        $query = "SELECT * FROM forms WHERE id = :form_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':form_id', $form_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $form = $stmt->fetch(PDO::FETCH_ASSOC);
            $form['fields'] = json_decode($form['fields_json'], true);
            return $form;
        }
        return null;
    }
    
    public function getFormFields($form_id) {
        $query = "SELECT fields_json FROM forms WHERE id = :form_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':form_id', $form_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return json_decode($result['fields_json'], true);
        }
        return [];
    }
    
    public function renderFormField($field) {
        $html = '';
        $required = isset($field['required']) && $field['required'] ? 'required' : '';
        $value = isset($field['value']) ? htmlspecialchars($field['value']) : '';
        
        switch ($field['type']) {
            case 'text':
                $html = "<input type='text' class='form-control' name='{$field['name']}' id='{$field['name']}' value='{$value}' {$required}>";
                break;
            case 'email':
                $html = "<input type='email' class='form-control' name='{$field['name']}' id='{$field['name']}' value='{$value}' {$required}>";
                break;
            case 'number':
                $html = "<input type='number' class='form-control' name='{$field['name']}' id='{$field['name']}' value='{$value}' {$required}>";
                break;
            case 'textarea':
                $html = "<textarea class='form-control' name='{$field['name']}' id='{$field['name']}' rows='3' {$required}>{$value}</textarea>";
                break;
            case 'select':
                $html = "<select class='form-control' name='{$field['name']}' id='{$field['name']}' {$required}>";
                $html .= "<option value=''>Select an option</option>";
                if (isset($field['options'])) {
                    foreach ($field['options'] as $option) {
                        $selected = ($value === $option) ? 'selected' : '';
                        $html .= "<option value='{$option}' {$selected}>{$option}</option>";
                    }
                }
                $html .= "</select>";
                break;
            case 'date':
                $html = "<input type='date' class='form-control' name='{$field['name']}' id='{$field['name']}' value='{$value}' {$required}>";
                break;
            case 'file':
                $html = "<input type='file' class='form-control' name='{$field['name']}' id='{$field['name']}' {$required}>";
                break;
        }
        
        return $html;
    }
}
?>