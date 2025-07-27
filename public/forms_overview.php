<?php
require_once '../includes/auth.php';
require_once '../classes/Form.php';
require_once '../classes/User.php';

$auth = new Auth();
$auth->requireLogin();

$form = new Form();
$user = new User();

$current_role = $auth->getCurrentUserRole();
$current_user_id = $auth->getCurrentUserId();

// Get filter parameters
$filters = [
    'form_name' => $_GET['form_name'] ?? '',
    'status' => $_GET['status'] ?? '',
    'created_by' => $_GET['created_by'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
    'allowed_role' => $_GET['allowed_role'] ?? '',
    'record_status' => $_GET['record_status'] ?? ''
];

// Get forms with filters
$forms = $form->getFormsWithFilters($filters);

// Get all users for filter dropdown
$all_users = [];
if ($current_role === 'ie_manager') {
    $all_users = $user->getAllUsers();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms Overview - File Management System</title>
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
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
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
                        <h1 class="h2">Forms Overview</h1>
                        <div>
                            <button class="btn btn-outline-primary me-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                                <i class="fas fa-filter me-2"></i>Filters
                            </button>
                            <?php if ($current_role === 'ie_manager'): ?>
                                <a href="manage_forms.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Create Form
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count($forms); ?></h3>
                                    <p class="mb-0">Total Forms</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo count(array_filter($forms, function($f) { return $f['status'] === 'active'; })); ?></h3>
                                    <p class="mb-0">Active Forms</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo array_sum(array_column($forms, 'record_count')); ?></h3>
                                    <p class="mb-0">Total Records</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3><?php echo array_sum(array_column($forms, 'draft_count')); ?></h3>
                                    <p class="mb-0">Draft Records</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="collapse mb-4" id="filterCollapse">
                        <div class="card filter-card">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <div class="col-md-3">
                                        <label for="form_name" class="form-label">Form Name</label>
                                        <input type="text" class="form-control" id="form_name" name="form_name" 
                                               value="<?php echo htmlspecialchars($filters['form_name']); ?>" placeholder="Search form name...">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="status" class="form-label">Status</label>
                                        <select class="form-select" id="status" name="status">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </div>
                                    <?php if ($current_role === 'ie_manager'): ?>
                                        <div class="col-md-2">
                                            <label for="created_by" class="form-label">Created By</label>
                                            <select class="form-select" id="created_by" name="created_by">
                                                <option value="">All Users</option>
                                                <?php foreach ($all_users as $user_data): ?>
                                                    <option value="<?php echo $user_data['id']; ?>" 
                                                            <?php echo $filters['created_by'] == $user_data['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($user_data['username']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-md-2">
                                        <label for="allowed_role" class="form-label">Allowed Role</label>
                                        <select class="form-select" id="allowed_role" name="allowed_role">
                                            <option value="">All Roles</option>
                                            <option value="ie" <?php echo $filters['allowed_role'] === 'ie' ? 'selected' : ''; ?>>IE</option>
                                            <option value="ie_incharge" <?php echo $filters['allowed_role'] === 'ie_incharge' ? 'selected' : ''; ?>>IE Incharge</option>
                                            <option value="ie_manager" <?php echo $filters['allowed_role'] === 'ie_manager' ? 'selected' : ''; ?>>IE Manager</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_from" class="form-label">Date From</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label for="date_to" class="form-label">Date To</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-light me-2">
                                            <i class="fas fa-search me-2"></i>Apply Filters
                                        </button>
                                        <a href="forms_overview.php" class="btn btn-outline-light">
                                            <i class="fas fa-times me-2"></i>Clear Filters
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Forms Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Forms List</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($forms)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Form Name</th>
                                                <th>Status</th>
                                                <th>Allowed Roles</th>
                                                <th>Total Records</th>
                                                <th>Draft Records</th>
                                                <th>Committed Records</th>
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
                                                    <td>
                                                        <span class="badge <?php echo $form_data['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                            <?php echo ucfirst($form_data['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $roles = explode(',', $form_data['allowed_roles']);
                                                        foreach ($roles as $role) {
                                                            $role_name = str_replace('_', ' ', $role);
                                                            echo '<span class="badge bg-info me-1">' . ucfirst($role_name) . '</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo $form_data['record_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning"><?php echo $form_data['draft_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $form_data['committed_count']; ?></span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($form_data['created_by_name']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($form_data['created_at'])); ?></td>
                                                    <td>
                                                        <a href="form_records.php?form_id=<?php echo $form_data['id']; ?>" 
                                                           class="btn btn-info btn-action me-1" title="View Records">
                                                            <i class="fas fa-database"></i>
                                                        </a>
                                                        <?php if ($current_role === 'ie_manager'): ?>
                                                            <a href="manage_forms.php?edit=<?php echo $form_data['id']; ?>" 
                                                               class="btn btn-warning btn-action me-1" title="Edit Form">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <a href="records.php?form_id=<?php echo $form_data['id']; ?>" 
                                                           class="btn btn-success btn-action" title="Create Record">
                                                            <i class="fas fa-plus"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No forms found</h5>
                                    <p class="text-muted">Try adjusting your filters or create a new form.</p>
                                    <?php if ($current_role === 'ie_manager'): ?>
                                        <a href="manage_forms.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Create First Form
                                        </a>
                                    <?php endif; ?>
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