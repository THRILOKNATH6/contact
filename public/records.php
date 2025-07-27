<?php
require_once '../includes/auth.php';
require_once '../classes/Form.php';
require_once '../classes/Record.php';
require_once '../classes/FileManager.php';

$auth = new Auth();
$auth->requireLogin();

$form = new Form();
$record = new Record();
$fileManager = new FileManager();

$current_role = $auth->getCurrentUserRole();
$current_user_id = $auth->getCurrentUserId();
$message = '';

// Handle record actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_record') {
            $form_id = intval($_POST['form_id']);
            $data = [];
            
            // Process form fields
            $form_fields = $form->getFormFields($form_id);
            foreach ($form_fields as $field) {
                if (isset($_POST[$field['name']])) {
                    $data[$field['name']] = $_POST[$field['name']];
                }
            }
            
            $record_id = $record->createRecord($form_id, $current_user_id, $data);
            if ($record_id) {
                // Handle file uploads
                foreach ($_FILES as $field_name => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $fileManager->uploadFile($file, $record_id, $current_user_id);
                    }
                }
                $message = '<div class="alert alert-success">Record created successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to create record.</div>';
            }
        } elseif ($_POST['action'] === 'commit_record') {
            $record_id = intval($_POST['record_id']);
            if ($record->commitRecord($record_id, $current_user_id)) {
                $message = '<div class="alert alert-success">Record committed successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to commit record.</div>';
            }
        }
    }
} elseif (isset($_GET['uncommit']) && $current_role === 'ie_manager') {
    $record_id = intval($_GET['uncommit']);
    if ($record->uncommitRecord($record_id)) {
        $message = '<div class="alert alert-success">Record uncommitted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to uncommit record.</div>';
    }
}

// Get records based on role
if ($current_role === 'ie_manager') {
    $records = $record->getAllRecords();
} elseif ($current_role === 'ie_incharge') {
    $records = $record->getRecordsByIncharge($current_user_id);
} else {
    $records = $record->getRecordsByUser($current_user_id);
}

$forms = $form->getAllForms();
$selected_form = null;
if (isset($_GET['form_id'])) {
    $selected_form = $form->getFormById(intval($_GET['form_id']));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records - File Management System</title>
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
        .form-builder {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
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
                        <small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $current_role)); ?></small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        
                        <?php if ($current_role === 'ie_manager'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_users.php">
                                    <i class="fas fa-users me-2"></i>Manage Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_forms.php">
                                    <i class="fas fa-wpforms me-2"></i>Manage Forms
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link active" href="records.php">
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
                        <h1 class="h2">Records Management</h1>
                        <?php if (!empty($forms)): ?>
                            <div class="dropdown">
                                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-plus me-2"></i>Create New Record
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($forms as $form_data): ?>
                                        <li><a class="dropdown-item" href="?form_id=<?php echo $form_data['id']; ?>"><?php echo htmlspecialchars($form_data['name']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php echo $message; ?>

                    <!-- Create Record Form -->
                    <?php if ($selected_form): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create New Record: <?php echo htmlspecialchars($selected_form['name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="create_record">
                                    <input type="hidden" name="form_id" value="<?php echo $selected_form['id']; ?>">
                                    
                                    <div class="form-builder">
                                        <?php if (!empty($selected_form['description'])): ?>
                                            <p class="text-muted mb-3"><?php echo htmlspecialchars($selected_form['description']); ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <?php foreach ($selected_form['fields'] as $field): ?>
                                                <div class="col-md-6 mb-3">
                                                    <label for="<?php echo $field['name']; ?>" class="form-label">
                                                        <?php echo htmlspecialchars($field['name']); ?>
                                                        <?php if (isset($field['required']) && $field['required']): ?>
                                                            <span class="text-danger">*</span>
                                                        <?php endif; ?>
                                                    </label>
                                                    <?php echo $form->renderFormField($field); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Create Record
                                        </button>
                                        <a href="records.php" class="btn btn-secondary ms-2">Cancel</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Records List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i>
                                <?php 
                                if ($current_role === 'ie_manager') {
                                    echo 'All Records';
                                } elseif ($current_role === 'ie_incharge') {
                                    echo 'IE Records Under Supervision';
                                } else {
                                    echo 'My Records';
                                }
                                ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($records)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Form</th>
                                                <?php if ($current_role !== 'ie'): ?>
                                                    <th>User</th>
                                                <?php endif; ?>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <?php if ($current_role !== 'ie'): ?>
                                                    <th>Committed By</th>
                                                <?php endif; ?>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($records as $record_data): ?>
                                                <tr>
                                                    <td><?php echo $record_data['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($record_data['form_name']); ?></td>
                                                    <?php if ($current_role !== 'ie'): ?>
                                                        <td><?php echo htmlspecialchars($record_data['user_name'] ?? 'Unknown'); ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <span class="badge <?php echo $record_data['status'] === 'committed' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo ucfirst($record_data['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($record_data['created_at'])); ?></td>
                                                    <?php if ($current_role !== 'ie'): ?>
                                                        <td><?php echo htmlspecialchars($record_data['committed_by_name'] ?? 'N/A'); ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record_data['id']; ?>" 
                                                           class="btn btn-info btn-action me-1">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        
                                                        <?php if ($record_data['status'] === 'draft'): ?>
                                                            <?php if ($current_role === 'ie_manager' || $record_data['user_id'] == $current_user_id): ?>
                                                                <a href="edit_record.php?id=<?php echo $record_data['id']; ?>" 
                                                                   class="btn btn-warning btn-action me-1">
                                                                    <i class="fas fa-edit"></i> Edit
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="commit_record">
                                                                <input type="hidden" name="record_id" value="<?php echo $record_data['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-action me-1"
                                                                        onclick="return confirm('Are you sure you want to commit this record? You won\'t be able to edit it afterwards.')">
                                                                    <i class="fas fa-check"></i> Commit
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($current_role === 'ie_manager' && $record_data['status'] === 'committed'): ?>
                                                            <a href="?uncommit=<?php echo $record_data['id']; ?>" 
                                                               class="btn btn-warning btn-action me-1"
                                                               onclick="return confirm('Are you sure you want to uncommit this record?')">
                                                                <i class="fas fa-undo"></i> Uncommit
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No records found. 
                                        <?php if (!empty($forms)): ?>
                                            Create your first record using the forms above.
                                        <?php else: ?>
                                            Ask your manager to create forms first.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>