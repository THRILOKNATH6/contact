<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in
requireAnyRole(['ie', 'ie_incharge', 'ie_manager']);

$currentUser = getCurrentUser();
$forms = getForms();
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formId = $_POST['form_id'];
    $formData = $_POST['form_data'] ?? [];
    
    if (empty($formId)) {
        $error = 'Please select a form.';
    } else {
        if (createRecord($formId, $currentUser['id'], $formData)) {
            $success = 'Record created successfully!';
        } else {
            $error = 'Failed to create record. Please try again.';
        }
    }
    
    if ($success || $error) {
        header('Location: create_record.php?success=' . urlencode($success) . '&error=' . urlencode($error));
        exit();
    }
}

// Get success/error messages from URL
if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// Get selected form
$selectedForm = null;
if (isset($_GET['form_id'])) {
    $selectedForm = getFormById($_GET['form_id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Record - File & Record Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <i class="fas fa-user fa-2x text-white mb-2"></i>
                        <h5 class="text-white"><?php echo getRoleDisplayName($currentUser['role']); ?></h5>
                        <p class="text-white-50 small"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <?php if ($currentUser['role'] === 'ie_manager'): ?>
                            <a class="nav-link" href="manager.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php elseif ($currentUser['role'] === 'ie_incharge'): ?>
                            <a class="nav-link" href="incharge.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php else: ?>
                            <a class="nav-link" href="ie.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        <?php endif; ?>
                        
                        <a class="nav-link active" href="create_record.php">
                            <i class="fas fa-plus"></i> Create Record
                        </a>
                        <a class="nav-link" href="my_records.php">
                            <i class="fas fa-file-alt"></i> My Records
                        </a>
                        <hr class="text-white-50">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-plus"></i> Create New Record</h2>
                        <a href="my_records.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Records
                        </a>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Form Selection -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-edit"></i> Select Form
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($forms)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No forms available</h5>
                                    <p class="text-muted">Contact a manager to create forms for data entry.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($forms as $form): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card h-100 <?php echo ($selectedForm && $selectedForm['id'] == $form['id']) ? 'border-primary' : ''; ?>">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo htmlspecialchars($form['name']); ?></h6>
                                                    <p class="card-text text-muted">
                                                        <?php echo htmlspecialchars($form['description'] ?: 'No description'); ?>
                                                    </p>
                                                    <?php 
                                                    $fields = json_decode($form['fields_json'], true);
                                                    $fieldCount = count($fields);
                                                    ?>
                                                    <small class="text-muted">
                                                        <i class="fas fa-list"></i> <?php echo $fieldCount; ?> field<?php echo $fieldCount !== 1 ? 's' : ''; ?>
                                                    </small>
                                                </div>
                                                <div class="card-footer">
                                                    <a href="?form_id=<?php echo $form['id']; ?>" 
                                                       class="btn btn-primary btn-sm w-100">
                                                        <i class="fas fa-edit"></i> Use This Form
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Dynamic Form -->
                    <?php if ($selectedForm): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-edit"></i> <?php echo htmlspecialchars($selectedForm['name']); ?>
                                </h5>
                                <small class="text-muted"><?php echo htmlspecialchars($selectedForm['description'] ?: 'No description'); ?></small>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="recordForm">
                                    <input type="hidden" name="form_id" value="<?php echo $selectedForm['id']; ?>">
                                    
                                    <?php 
                                    $fields = json_decode($selectedForm['fields_json'], true);
                                    foreach ($fields as $index => $field): 
                                    ?>
                                        <div class="mb-3">
                                            <label for="field_<?php echo $index; ?>" class="form-label">
                                                <?php echo htmlspecialchars($field['label']); ?>
                                                <?php if ($field['required']): ?>
                                                    <span class="text-danger">*</span>
                                                <?php endif; ?>
                                            </label>
                                            
                                            <?php if ($field['type'] === 'text'): ?>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="field_<?php echo $index; ?>" 
                                                       name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                                                       
                                            <?php elseif ($field['type'] === 'number'): ?>
                                                <input type="number" 
                                                       class="form-control" 
                                                       id="field_<?php echo $index; ?>" 
                                                       name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                                                       
                                            <?php elseif ($field['type'] === 'email'): ?>
                                                <input type="email" 
                                                       class="form-control" 
                                                       id="field_<?php echo $index; ?>" 
                                                       name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                                                       
                                            <?php elseif ($field['type'] === 'date'): ?>
                                                <input type="date" 
                                                       class="form-control" 
                                                       id="field_<?php echo $index; ?>" 
                                                       name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                       <?php echo $field['required'] ? 'required' : ''; ?>>
                                                       
                                            <?php elseif ($field['type'] === 'textarea'): ?>
                                                <textarea class="form-control" 
                                                          id="field_<?php echo $index; ?>" 
                                                          name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                          rows="3"
                                                          <?php echo $field['required'] ? 'required' : ''; ?>></textarea>
                                                          
                                            <?php elseif ($field['type'] === 'select'): ?>
                                                <select class="form-select" 
                                                        id="field_<?php echo $index; ?>" 
                                                        name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                        <?php echo $field['required'] ? 'required' : ''; ?>>
                                                    <option value="">Select an option</option>
                                                    <?php if (isset($field['options'])): ?>
                                                        <?php foreach ($field['options'] as $option): ?>
                                                            <option value="<?php echo htmlspecialchars($option); ?>">
                                                                <?php echo htmlspecialchars($option); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                                
                                            <?php elseif ($field['type'] === 'checkbox'): ?>
                                                <div class="form-check">
                                                    <input type="checkbox" 
                                                           class="form-check-input" 
                                                           id="field_<?php echo $index; ?>" 
                                                           name="form_data[<?php echo htmlspecialchars($field['label']); ?>]"
                                                           value="Yes"
                                                           <?php echo $field['required'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label" for="field_<?php echo $index; ?>">
                                                        <?php echo htmlspecialchars($field['label']); ?>
                                                    </label>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="form-text">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> 
                                                    <?php echo ucfirst($field['type']); ?> field
                                                    <?php echo $field['required'] ? '(Required)' : '(Optional)'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="create_record.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left"></i> Back to Form Selection
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Create Record
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>