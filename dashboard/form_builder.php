<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a manager
requireRole('ie_manager');

$currentUser = getCurrentUser();
$forms = getForms();
$error = '';
$success = '';

// Handle form creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_form') {
        $name = trim($_POST['form_name']);
        $description = trim($_POST['description']);
        $fields = json_decode($_POST['fields_json'], true);
        
        if (empty($name) || empty($fields)) {
            $error = 'Form name and fields are required.';
        } else {
            if (createForm($name, $description, $fields, $currentUser['id'])) {
                $success = 'Form created successfully!';
            } else {
                $error = 'Failed to create form. Please try again.';
            }
        }
    } elseif ($_POST['action'] === 'delete_form') {
        $formId = $_POST['form_id'];
        if (deleteForm($formId)) {
            $success = 'Form deleted successfully!';
        } else {
            $error = 'Failed to delete form.';
        }
    }
    
    if ($success || $error) {
        header('Location: form_builder.php?success=' . urlencode($success) . '&error=' . urlencode($error));
        exit();
    }
}

// Get success/error messages from URL
if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Builder - File & Record Management System</title>
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
        .field-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .field-item:hover {
            background: #e9ecef;
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
                        <i class="fas fa-user-tie fa-2x text-white mb-2"></i>
                        <h5 class="text-white">IE Manager</h5>
                        <p class="text-white-50 small"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="manager.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="user_approval.php">
                            <i class="fas fa-user-check"></i> User Approval
                        </a>
                        <a class="nav-link active" href="form_builder.php">
                            <i class="fas fa-edit"></i> Form Builder
                        </a>
                        <a class="nav-link" href="records.php">
                            <i class="fas fa-file-alt"></i> All Records
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users"></i> Manage Users
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
                        <h2><i class="fas fa-edit"></i> Form Builder</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFormModal">
                            <i class="fas fa-plus"></i> Create New Form
                        </button>
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
                    
                    <!-- Forms List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Available Forms
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($forms)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-edit fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No forms created yet</h5>
                                    <p class="text-muted">Create your first form to get started.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Form Name</th>
                                                <th>Description</th>
                                                <th>Fields</th>
                                                <th>Created By</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($forms as $form): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($form['name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($form['description'] ?: 'No description'); ?></td>
                                                    <td>
                                                        <?php 
                                                        $fields = json_decode($form['fields_json'], true);
                                                        echo count($fields) . ' field' . (count($fields) !== 1 ? 's' : '');
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($form['created_by_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($form['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-sm" onclick="viewForm(<?php echo htmlspecialchars(json_encode($form)); ?>)">
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                        <button class="btn btn-warning btn-sm" onclick="editForm(<?php echo htmlspecialchars(json_encode($form)); ?>)">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this form?')">
                                                            <input type="hidden" name="action" value="delete_form">
                                                            <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Create Form Modal -->
    <div class="modal fade" id="createFormModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Create New Form
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="createFormForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_form">
                        <input type="hidden" name="fields_json" id="fieldsJson">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="form_name" class="form-label">Form Name</label>
                                    <input type="text" class="form-control" id="form_name" name="form_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="description" name="description">
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6>Form Fields</h6>
                            <button type="button" class="btn btn-success btn-sm" onclick="addField()">
                                <i class="fas fa-plus"></i> Add Field
                            </button>
                        </div>
                        
                        <div id="fieldsContainer">
                            <!-- Fields will be added here dynamically -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Form Modal -->
    <div class="modal fade" id="viewFormModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye"></i> View Form
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="formDetails">
                        <!-- Form details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let fieldCounter = 0;
        
        function addField() {
            fieldCounter++;
            const fieldHtml = `
                <div class="field-item" id="field_${fieldCounter}">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Field Label</label>
                            <input type="text" class="form-control field-label" placeholder="Enter field label" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Field Type</label>
                            <select class="form-select field-type">
                                <option value="text">Text</option>
                                <option value="number">Number</option>
                                <option value="email">Email</option>
                                <option value="date">Date</option>
                                <option value="textarea">Text Area</option>
                                <option value="select">Dropdown</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Required</label>
                            <select class="form-select field-required">
                                <option value="true">Yes</option>
                                <option value="false">No</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeField(${fieldCounter})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row mt-2 field-options" style="display: none;">
                        <div class="col-12">
                            <label class="form-label">Options (one per line)</label>
                            <textarea class="form-control field-options-text" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('fieldsContainer').insertAdjacentHTML('beforeend', fieldHtml);
        }
        
        function removeField(fieldId) {
            document.getElementById(`field_${fieldId}`).remove();
        }
        
        // Show/hide options for select fields
        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('field-type')) {
                const fieldItem = e.target.closest('.field-item');
                const optionsDiv = fieldItem.querySelector('.field-options');
                if (e.target.value === 'select') {
                    optionsDiv.style.display = 'block';
                } else {
                    optionsDiv.style.display = 'none';
                }
            }
        });
        
        // Collect form data before submission
        document.getElementById('createFormForm').addEventListener('submit', function(e) {
            const fields = [];
            const fieldItems = document.querySelectorAll('.field-item');
            
            fieldItems.forEach(item => {
                const label = item.querySelector('.field-label').value;
                const type = item.querySelector('.field-type').value;
                const required = item.querySelector('.field-required').value === 'true';
                const options = item.querySelector('.field-options-text')?.value || '';
                
                if (label) {
                    const field = {
                        label: label,
                        type: type,
                        required: required
                    };
                    
                    if (type === 'select' && options) {
                        field.options = options.split('\n').filter(opt => opt.trim());
                    }
                    
                    fields.push(field);
                }
            });
            
            document.getElementById('fieldsJson').value = JSON.stringify(fields);
        });
        
        function viewForm(form) {
            const fields = JSON.parse(form.fields_json);
            let fieldsHtml = '';
            
            fields.forEach(field => {
                fieldsHtml += `
                    <div class="field-item">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>${field.label}</strong>
                            </div>
                            <div class="col-md-3">
                                <span class="badge bg-info">${field.type}</span>
                            </div>
                            <div class="col-md-3">
                                <span class="badge ${field.required ? 'bg-danger' : 'bg-secondary'}">
                                    ${field.required ? 'Required' : 'Optional'}
                                </span>
                            </div>
                            <div class="col-md-2">
                                ${field.options ? `<small class="text-muted">${field.options.length} options</small>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            document.getElementById('formDetails').innerHTML = `
                <h6>${form.name}</h6>
                <p class="text-muted">${form.description || 'No description'}</p>
                <hr>
                <h6>Fields (${fields.length})</h6>
                ${fieldsHtml}
            `;
            
            new bootstrap.Modal(document.getElementById('viewFormModal')).show();
        }
        
        function editForm(form) {
            // TODO: Implement edit functionality
            alert('Edit functionality will be implemented in the next version.');
        }
        
        // Add initial field when modal opens
        document.getElementById('createFormModal').addEventListener('show.bs.modal', function() {
            document.getElementById('fieldsContainer').innerHTML = '';
            fieldCounter = 0;
            addField();
        });
    </script>
</body>
</html>