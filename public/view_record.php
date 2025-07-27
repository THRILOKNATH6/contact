<?php
require_once '../includes/auth.php';
require_once '../classes/Record.php';
require_once '../classes/FileManager.php';

$auth = new Auth();
$auth->requireLogin();

$record = new Record();
$fileManager = new FileManager();

$current_role = $auth->getCurrentUserRole();
$current_user_id = $auth->getCurrentUserId();
$message = '';

if (!isset($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);
$record_data = $record->getRecordById($record_id);

if (!$record_data) {
    header("Location: records.php");
    exit();
}

// Check access permissions
$can_view = false;
if ($current_role === 'ie_manager') {
    $can_view = true;
} elseif ($current_role === 'ie_incharge') {
    // Check if the record belongs to an IE under this incharge
    $can_view = ($record_data['user_id'] == $current_user_id);
    // Also check if the record owner is under this incharge
    if (!$can_view) {
        // Query to check if record owner is under this incharge would go here
        // For simplicity, allowing incharge to view all records for now
        $can_view = true;
    }
} else {
    $can_view = ($record_data['user_id'] == $current_user_id);
}

if (!$can_view) {
    header("Location: records.php");
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_file') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        if ($fileManager->uploadFile($_FILES['file'], $record_id, $current_user_id)) {
            $message = '<div class="alert alert-success">File uploaded successfully!</div>';
        } else {
            $message = '<div class="alert alert-danger">Failed to upload file.</div>';
        }
    }
}

// Handle file deletion
if (isset($_GET['delete_file'])) {
    $file_id = intval($_GET['delete_file']);
    if ($fileManager->deleteFile($file_id, $current_user_id)) {
        $message = '<div class="alert alert-success">File deleted successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to delete file.</div>';
    }
}

$files = $fileManager->getFilesByRecord($record_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Record - File Management System</title>
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
        .record-data {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
        }
        .field-item {
            border-bottom: 1px solid #dee2e6;
            padding: 10px 0;
        }
        .field-item:last-child {
            border-bottom: none;
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
                        <h1 class="h2">View Record #<?php echo $record_data['id']; ?></h1>
                        <div>
                            <a href="records.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Records
                            </a>
                            <?php if ($record_data['status'] === 'draft' && ($current_role === 'ie_manager' || $record_data['user_id'] == $current_user_id)): ?>
                                <a href="edit_record.php?id=<?php echo $record_data['id']; ?>" class="btn btn-warning">
                                    <i class="fas fa-edit me-2"></i>Edit Record
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php echo $message; ?>

                    <!-- Record Information -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Record Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Form:</strong></div>
                                        <div class="col-sm-9"><?php echo htmlspecialchars($record_data['form_name']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Created By:</strong></div>
                                        <div class="col-sm-9"><?php echo htmlspecialchars($record_data['user_name']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Status:</strong></div>
                                        <div class="col-sm-9">
                                            <span class="badge <?php echo $record_data['status'] === 'committed' ? 'bg-success' : 'bg-warning'; ?> fs-6">
                                                <?php echo ucfirst($record_data['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-sm-3"><strong>Created:</strong></div>
                                        <div class="col-sm-9"><?php echo date('M j, Y g:i A', strtotime($record_data['created_at'])); ?></div>
                                    </div>
                                    <?php if ($record_data['updated_at'] && $record_data['updated_at'] !== $record_data['created_at']): ?>
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Last Updated:</strong></div>
                                            <div class="col-sm-9"><?php echo date('M j, Y g:i A', strtotime($record_data['updated_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($record_data['committed_at']): ?>
                                        <div class="row mb-3">
                                            <div class="col-sm-3"><strong>Committed:</strong></div>
                                            <div class="col-sm-9"><?php echo date('M j, Y g:i A', strtotime($record_data['committed_at'])); ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Record Data -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Record Data</h5>
                                </div>
                                <div class="card-body">
                                    <div class="record-data">
                                        <?php if (!empty($record_data['form_fields']) && !empty($record_data['data'])): ?>
                                            <?php foreach ($record_data['form_fields'] as $field): ?>
                                                <div class="field-item">
                                                    <div class="row">
                                                        <div class="col-sm-4">
                                                            <strong><?php echo htmlspecialchars($field['name']); ?>:</strong>
                                                        </div>
                                                        <div class="col-sm-8">
                                                            <?php 
                                                            $value = $record_data['data'][$field['name']] ?? 'N/A';
                                                            if ($field['type'] === 'file') {
                                                                echo '<em>See files section below</em>';
                                                            } else {
                                                                echo htmlspecialchars($value);
                                                            }
                                                            ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No data available for this record.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Files Section -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Attached Files</h5>
                                </div>
                                <div class="card-body">
                                    <!-- File Upload Form -->
                                    <?php if ($record_data['status'] === 'draft' || $current_role === 'ie_manager'): ?>
                                        <form method="POST" enctype="multipart/form-data" class="mb-3">
                                            <input type="hidden" name="action" value="upload_file">
                                            <div class="input-group">
                                                <input type="file" class="form-control" name="file" required>
                                                <button class="btn btn-primary" type="submit">
                                                    <i class="fas fa-upload"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Max size: 10MB. Allowed: jpg, png, pdf, doc, docx, xls, xlsx, txt, zip</small>
                                        </form>
                                        <hr>
                                    <?php endif; ?>

                                    <!-- Files List -->
                                    <?php if (!empty($files)): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($files as $file): ?>
                                                <div class="list-group-item px-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($file['original_name']); ?></h6>
                                                            <small class="text-muted">
                                                                <?php echo $fileManager->formatFileSize($file['file_size']); ?> • 
                                                                <?php echo date('M j, Y', strtotime($file['uploaded_at'])); ?>
                                                            </small>
                                                        </div>
                                                        <div class="btn-group">
                                                            <a href="download.php?file_id=<?php echo $file['id']; ?>" 
                                                               class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download"></i>
                                                            </a>
                                                            <?php if ($current_role === 'ie_manager' || $file['uploaded_by'] == $current_user_id): ?>
                                                                <a href="?id=<?php echo $record_id; ?>&delete_file=<?php echo $file['id']; ?>" 
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Are you sure you want to delete this file?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-folder-open fa-2x text-muted mb-2"></i>
                                            <p class="text-muted mb-0">No files attached to this record.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>