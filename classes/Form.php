<?php
require_once '../config/database.php';

class Form {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function createForm($name, $description, $fields, $created_by) {
        $fields_json = json_encode($fields);
        
        $query = "INSERT INTO forms (name, description, fields_json, created_by, created_at) 
                  VALUES (:name, :description, :fields_json, :created_by, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':fields_json', $fields_json);
        $stmt->bindParam(':created_by', $created_by);
        
        return $stmt->execute();
    }
    
    public function updateForm($form_id, $name, $description, $fields) {
        $fields_json = json_encode($fields);
        
        $query = "UPDATE forms SET name = :name, description = :description, 
                  fields_json = :fields_json, updated_at = NOW() 
                  WHERE id = :form_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':fields_json', $fields_json);
        $stmt->bindParam(':form_id', $form_id);
        
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
    
    public function getAllForms() {
        $query = "SELECT f.*, u.username as created_by_name 
                  FROM forms f 
                  LEFT JOIN users u ON f.created_by = u.id 
                  ORDER BY f.created_at DESC";
        $stmt = $this->conn->prepare($query);
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