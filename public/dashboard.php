<?php
require_once '../includes/auth.php';
require_once '../classes/User.php';
require_once '../classes/Form.php';
require_once '../classes/Record.php';

$auth = new Auth();
$auth->requireLogin();

$user = new User();
$form = new Form();
$record = new Record();

$current_role = $auth->getCurrentUserRole();
$current_user_id = $auth->getCurrentUserId();

// Get dashboard data based on role
$pending_users = [];
$forms = [];
$records = [];
$dashboard_stats = [];

if ($current_role === 'ie_manager') {
    $pending_users = $user->getPendingUsers();
    $forms = $form->getAllForms();
    $records = $record->getAllRecords();
    $dashboard_stats = [
        'total_users' => count($user->getAllUsers()),
        'pending_approvals' => count($pending_users),
        'total_forms' => count($forms),
        'total_records' => count($records)
    ];
} elseif ($current_role === 'ie_incharge') {
    $forms = $form->getAllForms();
    $records = $record->getRecordsByIncharge($current_user_id);
    $dashboard_stats = [
        'my_ies' => count($user->getUsersByIncharge($current_user_id)),
        'total_forms' => count($forms),
        'ie_records' => count($records)
    ];
} else { // ie
    $forms = $form->getAllForms();
    $records = $record->getRecordsByUser($current_user_id);
    $dashboard_stats = [
        'my_records' => count($records),
        'draft_records' => count(array_filter($records, function($r) { return $r['status'] === 'draft'; })),
        'committed_records' => count(array_filter($records, function($r) { return $r['status'] === 'committed'; }))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - File Management System</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card .card-body {
            padding: 25px;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        .btn-action {
            padding: 5px 10px;
            font-size: 0.8rem;
            border-radius: 5px;
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
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="forms_overview.php">
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
                        <h1 class="h2">Dashboard</h1>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <?php if ($current_role === 'ie_manager'): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-users stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['total_users']; ?></h3>
                                        <p class="mb-0">Total Users</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['pending_approvals']; ?></h3>
                                        <p class="mb-0">Pending Approvals</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-wpforms stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['total_forms']; ?></h3>
                                        <p class="mb-0">Total Forms</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['total_records']; ?></h3>
                                        <p class="mb-0">Total Records</p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_role === 'ie_incharge'): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-user-friends stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['my_ies']; ?></h3>
                                        <p class="mb-0">My IEs</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-wpforms stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['total_forms']; ?></h3>
                                        <p class="mb-0">Available Forms</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['ie_records']; ?></h3>
                                        <p class="mb-0">IE Records</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-database stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['my_records']; ?></h3>
                                        <p class="mb-0">My Records</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-edit stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['draft_records']; ?></h3>
                                        <p class="mb-0">Draft Records</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card stat-card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-check-circle stat-icon"></i>
                                        <h3 class="mt-2"><?php echo $dashboard_stats['committed_records']; ?></h3>
                                        <p class="mb-0">Committed Records</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Users (Manager only) -->
                    <?php if ($current_role === 'ie_manager' && !empty($pending_users)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending User Approvals</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th>Incharge</th>
                                                <th>Registration Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_users as $pending_user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($pending_user['username']); ?></td>
                                                    <td><?php echo ucfirst(str_replace('_', ' ', $pending_user['role'])); ?></td>
                                                    <td><?php echo htmlspecialchars($pending_user['incharge_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($pending_user['created_at'])); ?></td>
                                                    <td>
                                                        <a href="manage_users.php?approve=<?php echo $pending_user['id']; ?>" 
                                                           class="btn btn-success btn-action me-2">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="manage_users.php?reject=<?php echo $pending_user['id']; ?>" 
                                                           class="btn btn-danger btn-action">
                                                            <i class="fas fa-times"></i> Reject
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Records -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-database me-2"></i>Recent Records</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($records)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Form</th>
                                                <?php if ($current_role !== 'ie'): ?>
                                                    <th>User</th>
                                                <?php endif; ?>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($records, 0, 10) as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['form_name']); ?></td>
                                                    <?php if ($current_role !== 'ie'): ?>
                                                        <td><?php echo htmlspecialchars($record['user_name'] ?? 'Unknown'); ?></td>
                                                    <?php endif; ?>
                                                    <td>
                                                        <span class="badge <?php echo $record['status'] === 'committed' ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo ucfirst($record['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($record['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record['id']; ?>" 
                                                           class="btn btn-info btn-action">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="records.php" class="btn btn-primary">View All Records</a>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No records found. Start by creating a new record.</p>
                                    <a href="records.php" class="btn btn-primary">Create New Record</a>
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