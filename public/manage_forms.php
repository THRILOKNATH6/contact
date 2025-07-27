<?php
require_once '../includes/auth.php';
require_once '../classes/Form.php';

$auth = new Auth();
$auth->requireRole('ie_manager');

$form = new Form();
$message = '';

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $fields = json_decode($_POST['fields'], true) ?? [];
            
            if ($form->createForm($name, $description, $fields, $auth->getCurrentUserId())) {
                $message = '<div class="alert alert-success">Form created successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to create form.</div>';
            }
        } elseif ($_POST['action'] === 'update') {
            $form_id = intval($_POST['form_id']);
            $name = $_POST['name'] ?? '';
            $description = $_POST['description'] ?? '';
            $fields = json_decode($_POST['fields'], true) ?? [];
            
            if ($form->updateForm($form_id, $name, $description, $fields)) {
                $message = '<div class="alert alert-success">Form updated successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to update form.</div>';
            }
        }
    }
} elseif (isset($_GET['delete'])) {
    $form_id = intval($_GET['delete']);
    if ($form->deleteForm($form_id)) {
        $message = '<div class="alert alert-success">Form deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete form.</div>';
    }
}

$forms = $form->getAllForms();
$edit_form = null;
if (isset($_GET['edit'])) {
    $edit_form = $form->getFormById(intval($_GET['edit']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Forms - File Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
        }
        .field-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .form-builder {
            min-height: 400px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-file-alt me-2"></i>FMS</h4>
                        <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <small class="text-muted">IE Manager</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="manage_forms.php">
                                <i class="fas fa-wpforms me-2"></i>Manage Forms
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="records.php">
                                <i class="fas fa-database me-2"></i>Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="files.php">
                                <i class="fas fa-folder me-2"></i>Files
                            </a>
                        </li>
                        <li class="nav-item mt-3">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="main-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">Manage Forms</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#formModal" onclick="resetFormBuilder()">
                            <i class="fas fa-plus me-2"></i>Create New Form
                        </button>
                    </div>

                    <?php echo $message; ?>

                    <!-- Forms List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-wpforms me-2"></i>All Forms</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($forms)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($forms as $form_data): ?>
                                                <tr>
                                                    <td><?php echo $form_data['id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($form_data['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($form_data['description']); ?></td>
                                                    <td><?php echo htmlspecialchars($form_data['created_by_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($form_data['created_at'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-info btn-action me-2" onclick="editForm(<?php echo $form_data['id']; ?>)">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <a href="?delete=<?php echo $form_data['id']; ?>" 
                                                           class="btn btn-danger btn-action"
                                                           onclick="return confirm('Are you sure you want to delete this form? All associated records will be deleted.')">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-wpforms fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No forms created yet. Create your first form to get started.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Form Builder Modal -->
    <div class="modal fade" id="formModal" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">Create New Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formBuilderForm" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create" id="formAction">
                        <input type="hidden" name="form_id" id="formId">
                        <input type="hidden" name="fields" id="fieldsJson">
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="formName" class="form-label">Form Name</label>
                                    <input type="text" class="form-control" id="formName" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="formDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="formDescription" name="description" rows="3"></textarea>
                                </div>
                                
                                <h6>Add Fields</h6>
                                <div class="mb-3">
                                    <label for="fieldName" class="form-label">Field Name</label>
                                    <input type="text" class="form-control" id="fieldName">
                                </div>
                                <div class="mb-3">
                                    <label for="fieldType" class="form-label">Field Type</label>
                                    <select class="form-select" id="fieldType" onchange="toggleFieldOptions()">
                                        <option value="text">Text</option>
                                        <option value="email">Email</option>
                                        <option value="number">Number</option>
                                        <option value="textarea">Textarea</option>
                                        <option value="select">Select</option>
                                        <option value="date">Date</option>
                                        <option value="file">File</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="fieldOptionsDiv" style="display: none;">
                                    <label for="fieldOptions" class="form-label">Options (one per line)</label>
                                    <textarea class="form-control" id="fieldOptions" rows="3" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fieldRequired">
                                        <label class="form-check-label" for="fieldRequired">Required</label>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="addField()">
                                    <i class="fas fa-plus"></i> Add Field
                                </button>
                            </div>
                            
                            <div class="col-md-8">
                                <h6>Form Preview</h6>
                                <div class="form-builder border rounded p-3" id="formPreview">
                                    <p class="text-muted">Your form fields will appear here...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Form</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let formFields = [];

        function toggleFieldOptions() {
            const fieldType = document.getElementById('fieldType').value;
            const optionsDiv = document.getElementById('fieldOptionsDiv');
            
            if (fieldType === 'select') {
                optionsDiv.style.display = 'block';
            } else {
                optionsDiv.style.display = 'none';
            }
        }

        function addField() {
            const name = document.getElementById('fieldName').value.trim();
            const type = document.getElementById('fieldType').value;
            const required = document.getElementById('fieldRequired').checked;
            const options = document.getElementById('fieldOptions').value.split('\n').filter(opt => opt.trim());

            if (!name) {
                alert('Please enter a field name');
                return;
            }

            const field = {
                name: name,
                type: type,
                required: required
            };

            if (type === 'select' && options.length > 0) {
                field.options = options;
            }

            formFields.push(field);
            updateFormPreview();
            clearFieldInputs();
        }

        function removeField(index) {
            formFields.splice(index, 1);
            updateFormPreview();
        }

        function updateFormPreview() {
            const preview = document.getElementById('formPreview');
            let html = '';

            if (formFields.length === 0) {
                html = '<p class="text-muted">Your form fields will appear here...</p>';
            } else {
                formFields.forEach((field, index) => {
                    html += `
                        <div class="field-item">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>${field.name} ${field.required ? '<span class="text-danger">*</span>' : ''}</strong>
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeField(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <div class="mb-2">
                                <span class="badge bg-secondary">${field.type}</span>
                                ${field.options ? '<span class="badge bg-info ms-1">' + field.options.length + ' options</span>' : ''}
                            </div>
                            ${renderFieldPreview(field)}
                        </div>
                    `;
                });
            }

            preview.innerHTML = html;
        }

        function renderFieldPreview(field) {
            switch (field.type) {
                case 'text':
                case 'email':
                case 'number':
                    return `<input type="${field.type}" class="form-control" placeholder="${field.name}" disabled>`;
                case 'textarea':
                    return `<textarea class="form-control" placeholder="${field.name}" disabled></textarea>`;
                case 'select':
                    let options = '<option>Select an option</option>';
                    if (field.options) {
                        field.options.forEach(opt => {
                            options += `<option>${opt}</option>`;
                        });
                    }
                    return `<select class="form-control" disabled>${options}</select>`;
                case 'date':
                    return `<input type="date" class="form-control" disabled>`;
                case 'file':
                    return `<input type="file" class="form-control" disabled>`;
                default:
                    return `<input type="text" class="form-control" placeholder="${field.name}" disabled>`;
            }
        }

        function clearFieldInputs() {
            document.getElementById('fieldName').value = '';
            document.getElementById('fieldType').value = 'text';
            document.getElementById('fieldOptions').value = '';
            document.getElementById('fieldRequired').checked = false;
            toggleFieldOptions();
        }

        function resetFormBuilder() {
            formFields = [];
            document.getElementById('formBuilderForm').reset();
            document.getElementById('formAction').value = 'create';
            document.getElementById('formModalLabel').textContent = 'Create New Form';
            updateFormPreview();
            clearFieldInputs();
        }

        function editForm(formId) {
            // This would typically fetch form data via AJAX
            // For now, redirect to edit page
            window.location.href = `?edit=${formId}`;
        }

        document.getElementById('formBuilderForm').addEventListener('submit', function(e) {
            document.getElementById('fieldsJson').value = JSON.stringify(formFields);
        });

        <?php if ($edit_form): ?>
        // Load edit form data
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('formAction').value = 'update';
            document.getElementById('formId').value = '<?php echo $edit_form['id']; ?>';
            document.getElementById('formName').value = '<?php echo addslashes($edit_form['name']); ?>';
            document.getElementById('formDescription').value = '<?php echo addslashes($edit_form['description']); ?>';
            document.getElementById('formModalLabel').textContent = 'Edit Form';
            
            formFields = <?php echo json_encode($edit_form['fields']); ?>;
            updateFormPreview();
            
            var modal = new bootstrap.Modal(document.getElementById('formModal'));
            modal.show();
        });
        <?php endif; ?>
    </script>
</body>
</html>