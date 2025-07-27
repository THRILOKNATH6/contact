<?php
require_once '../includes/auth.php';
require_once '../classes/Form.php';
require_once '../classes/Record.php';
require_once '../classes/User.php';

$auth = new Auth();
$auth->requireLogin();

$form = new Form();
$record = new Record();

$current_role = $auth->getCurrentUserRole();
$current_user_id = $auth->getCurrentUserId();

if (!isset($_GET['form_id'])) {
    header("Location: forms_overview.php");
    exit();
}

$form_id = intval($_GET['form_id']);
$form_data = $form->getFormById($form_id);

if (!$form_data) {
    header("Location: forms_overview.php");
    exit();
}

// Check access permissions for the form
$allowed_roles = explode(',', $form_data['allowed_roles']);
if (!in_array($current_role, $allowed_roles) && $current_role !== 'ie_manager') {
    header("Location: forms_overview.php");
    exit();
}

// Handle record actions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'commit_record') {
            $record_id = intval($_POST['record_id']);
            if ($record->commitRecord($record_id, $current_user_id)) {
                $message = '<div class="alert alert-success">Record committed successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to commit record.</div>';
            }
        } elseif ($_POST['action'] === 'uncommit_record' && $current_role === 'ie_manager') {
            $record_id = intval($_POST['record_id']);
            if ($record->uncommitRecord($record_id)) {
                $message = '<div class="alert alert-success">Record uncommitted successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to uncommit record.</div>';
            }
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$user_filter = $_GET['user'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build the query based on filters and user role
if ($current_role === 'ie_manager') {
    $query = "SELECT r.*, u.username as user_name, c.username as committed_by_name 
              FROM records r 
              LEFT JOIN users u ON r.user_id = u.id 
              LEFT JOIN users c ON r.committed_by = c.id 
              WHERE r.form_id = :form_id";
} elseif ($current_role === 'ie_incharge') {
    $query = "SELECT r.*, u.username as user_name, c.username as committed_by_name 
              FROM records r 
              LEFT JOIN users u ON r.user_id = u.id 
              LEFT JOIN users c ON r.committed_by = c.id 
              WHERE r.form_id = :form_id AND (u.incharge_id = :user_id OR r.user_id = :user_id)";
} else {
    $query = "SELECT r.*, u.username as user_name, c.username as committed_by_name 
              FROM records r 
              LEFT JOIN users u ON r.user_id = u.id 
              LEFT JOIN users c ON r.committed_by = c.id 
              WHERE r.form_id = :form_id AND r.user_id = :user_id";
}

// Add filters
if ($status_filter) {
    $query .= " AND r.status = :status";
}
if ($user_filter && $current_role !== 'ie') {
    $query .= " AND r.user_id = :user_filter";
}
if ($date_from) {
    $query .= " AND DATE(r.created_at) >= :date_from";
}
if ($date_to) {
    $query .= " AND DATE(r.created_at) <= :date_to";
}

$query .= " ORDER BY r.created_at DESC";

$stmt = $record->conn->prepare($query);
$stmt->bindParam(':form_id', $form_id);

if ($current_role !== 'ie_manager') {
    $stmt->bindParam(':user_id', $current_user_id);
}
if ($status_filter) {
    $stmt->bindParam(':status', $status_filter);
}
if ($user_filter && $current_role !== 'ie') {
    $stmt->bindParam(':user_filter', $user_filter);
}
if ($date_from) {
    $stmt->bindParam(':date_from', $date_from);
}
if ($date_to) {
    $stmt->bindParam(':date_to', $date_to);
}

$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse JSON data for each record
foreach ($records as &$record_item) {
    $record_item['data'] = json_decode($record_item['data_json'], true);
}

// Get users for filter (if manager or incharge)
$users = [];
if ($current_role === 'ie_manager') {
    $user_obj = new User();
    $users = $user_obj->getAllUsers();
} elseif ($current_role === 'ie_incharge') {
    $user_obj = new User();
    $users = $user_obj->getUsersByIncharge($current_user_id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records for <?php echo htmlspecialchars($form_data['name']); ?> - File Management System</title>
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
        .filter-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .filter-card .form-control,
        .filter-card .form-select {
            background: rgba(255,255,255,0.9);
            border: 1px solid rgba(255,255,255,0.3);
        }
        .stats-card {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            color: white;
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
                            <a class="nav-link" href="records.php">
                                <i class="fas fa-database me-2"></i>Records
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="forms_overview.php">
                                <i class="fas fa-list-alt me-2"></i>Forms Overview
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
                        <div>
                            <h1 class="h2"><?php echo htmlspecialchars($form_data['name']); ?> Records</h1>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($form_data['description']); ?></p>
                        </div>
                        <div>
                            <a href="forms_overview.php" class="btn btn-secondary me-2">
                                <i class="fas fa-arrow-left me-2"></i>Back to Forms
                            </a>
                            <a href="records.php?form_id=<?php echo $form_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Record
                            </a>
                        </div>
                    </div>

                    <?php echo $message; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count($records); ?></h3>
                                    <p class="mb-0">Total Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count(array_filter($records, function($r) { return $r['status'] === 'draft'; })); ?></h3>
                                    <p class="mb-0">Draft Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count(array_filter($records, function($r) { return $r['status'] === 'committed'; })); ?></h3>
                                    <p class="mb-0">Committed Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count(array_unique(array_column($records, 'user_id'))); ?></h3>
                                    <p class="mb-0">Contributors</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card filter-card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
                                <div class="col-md-2">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="committed" <?php echo $status_filter === 'committed' ? 'selected' : ''; ?>>Committed</option>
                                    </select>
                                </div>
                                <?php if ($current_role !== 'ie'): ?>
                                    <div class="col-md-3">
                                        <label for="user" class="form-label">User</label>
                                        <select class="form-select" id="user" name="user">
                                            <option value="">All Users</option>
                                            <?php foreach ($users as $user_data): ?>
                                                <option value="<?php echo $user_data['id']; ?>" 
                                                        <?php echo $user_filter == $user_data['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user_data['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                <div class="col-md-2">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" class="form-control" id="date_from" name="date_from" 
                                           value="<?php echo htmlspecialchars($date_from); ?>">
                                </div>
                                <div class="col-md-2">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" class="form-control" id="date_to" name="date_to" 
                                           value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-light me-2">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                    <a href="form_records.php?form_id=<?php echo $form_id; ?>" class="btn btn-outline-light">
                                        <i class="fas fa-times me-2"></i>Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Records Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i>Records List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($records)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <?php if ($current_role !== 'ie'): ?>
                                                    <th>User</th>
                                                <?php endif; ?>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Updated</th>
                                                <?php if ($current_role !== 'ie'): ?>
                                                    <th>Committed By</th>
                                                <?php endif; ?>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($records as $record_item): ?>
                                                <tr>
                                                    <td><?php echo $record_item['id']; ?></td>
                                                    <?php if ($current_role !== 'ie'): ?>
                                                        <td><?php echo htmlspecialchars($record_item['user_name']); ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <span class="badge <?php echo $record_item['status'] === 'committed' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo ucfirst($record_item['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($record_item['created_at'])); ?></td>
                                                    <td>
                                                        <?php if ($record_item['updated_at']): ?>
                                                            <?php echo date('M j, Y g:i A', strtotime($record_item['updated_at'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <?php if ($current_role !== 'ie'): ?>
                                                        <td>
                                                            <?php if ($record_item['committed_by_name']): ?>
                                                                <?php echo htmlspecialchars($record_item['committed_by_name']); ?>
                                                                <br><small class="text-muted"><?php echo date('M j, Y', strtotime($record_item['committed_at'])); ?></small>
                                                            <?php else: ?>
                                                                <span class="text-muted">Not committed</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record_item['id']; ?>" 
                                                           class="btn btn-info btn-action me-1" title="View Record">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        <?php if ($record_item['status'] === 'draft'): ?>
                                                            <?php if ($current_role === 'ie_manager' || $record_item['user_id'] == $current_user_id): ?>
                                                                <a href="edit_record.php?id=<?php echo $record_item['id']; ?>" 
                                                                   class="btn btn-warning btn-action me-1" title="Edit Record">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="commit_record">
                                                                <input type="hidden" name="record_id" value="<?php echo $record_item['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-action me-1" title="Commit Record"
                                                                        onclick="return confirm('Are you sure you want to commit this record?')">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($current_role === 'ie_manager' && $record_item['status'] === 'committed'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <input type="hidden" name="action" value="uncommit_record">
                                                                <input type="hidden" name="record_id" value="<?php echo $record_item['id']; ?>">
                                                                <button type="submit" class="btn btn-warning btn-action me-1" title="Uncommit Record"
                                                                        onclick="return confirm('Are you sure you want to uncommit this record?')">
                                                                    <i class="fas fa-undo"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No records found</h5>
                                    <p class="text-muted">No records have been created for this form yet or match your filters.</p>
                                    <a href="records.php?form_id=<?php echo $form_id; ?>" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Create First Record
                                    </a>
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