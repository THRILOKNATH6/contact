<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an IE
requireRole('ie');

$currentUser = getCurrentUser();
$forms = getForms();
$userRecords = getRecords($currentUser['id']);

$error = '';
$success = '';

// Handle record actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'commit_record') {
        $recordId = $_POST['record_id'];
        if (commitRecord($recordId, $currentUser['id'])) {
            $success = 'Record committed successfully!';
        } else {
            $error = 'Failed to commit record.';
        }
    }
    
    if ($success || $error) {
        header('Location: ie.php?success=' . urlencode($success) . '&error=' . urlencode($error));
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
    <title>IE Dashboard - File & Record Management System</title>
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
                        <h5 class="text-white">IE</h5>
                        <p class="text-white-50 small"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="ie.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="create_record.php">
                            <i class="fas fa-plus"></i> Create Record
                        </a>
                        <a class="nav-link" href="my_records.php">
                            <i class="fas fa-file-alt"></i> My Records
                        </a>
                        <a class="nav-link" href="upload_files.php">
                            <i class="fas fa-upload"></i> Upload Files
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
                        <h2><i class="fas fa-tachometer-alt"></i> IE Dashboard</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?>
                        </div>
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
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count($userRecords); ?></h4>
                                            <p class="mb-0">Total Records</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-file-alt fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($userRecords, function($r) { return $r['status'] === 'draft'; })); ?></h4>
                                            <p class="mb-0">Draft Records</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count(array_filter($userRecords, function($r) { return $r['status'] === 'committed'; })); ?></h4>
                                            <p class="mb-0">Committed Records</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h4><?php echo count($forms); ?></h4>
                                            <p class="mb-0">Available Forms</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-plus-circle fa-3x text-primary mb-3"></i>
                                    <h5>Create New Record</h5>
                                    <p class="text-muted">Fill out a form to create a new record</p>
                                    <a href="create_record.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create Record
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-upload fa-3x text-success mb-3"></i>
                                    <h5>Upload Files</h5>
                                    <p class="text-muted">Upload files to existing records</p>
                                    <a href="upload_files.php" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Upload Files
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Records -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt"></i> My Recent Records
                            </h5>
                            <a href="my_records.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($userRecords)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No records yet</h5>
                                    <p class="text-muted">Create your first record to get started.</p>
                                    <a href="create_record.php" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Create First Record
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Form</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Committed</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($userRecords, 0, 10) as $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($record['form_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo getStatusBadge($record['status']); ?></td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($record['committed_at']): ?>
                                                            <?php echo date('M j, Y H:i', strtotime($record['committed_at'])); ?>
                                                            <br><small class="text-muted">by <?php echo htmlspecialchars($record['committed_by_name']); ?></small>
                                                        <?php else: ?>
                                                            <span class="text-muted">Not committed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record['id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($record['status'] === 'draft'): ?>
                                                            <a href="edit_record.php?id=<?php echo $record['id']; ?>" 
                                                               class="btn btn-warning btn-sm">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to commit this record? You won\'t be able to edit it afterwards.')">
                                                                <input type="hidden" name="action" value="commit_record">
                                                                <input type="hidden" name="record_id" value="<?php echo $record['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check"></i> Commit
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>