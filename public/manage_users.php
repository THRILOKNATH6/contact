<?php
require_once '../includes/auth.php';
require_once '../classes/User.php';

$auth = new Auth();
$auth->requireRole('ie_manager');

$user = new User();
$message = '';

// Handle user approval/rejection
if (isset($_GET['approve'])) {
    $user_id = intval($_GET['approve']);
    if ($user->approveUser($user_id)) {
        $message = '<div class="alert alert-success">User approved successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to approve user.</div>';
    }
} elseif (isset($_GET['reject'])) {
    $user_id = intval($_GET['reject']);
    if ($user->rejectUser($user_id)) {
        $message = '<div class="alert alert-success">User rejected successfully!</div>';
    } else {
        $message = '<div class="alert alert-danger">Failed to reject user.</div>';
    }
}

$all_users = $user->getAllUsers();
$pending_users = $user->getPendingUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - File Management System</title>
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
                            <a class="nav-link active" href="manage_users.php">
                                <i class="fas fa-users me-2"></i>Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="manage_forms.php">
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
                        <h1 class="h2">Manage Users</h1>
                    </div>

                    <?php echo $message; ?>

                    <!-- Pending Approvals -->
                    <?php if (!empty($pending_users)): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Approvals (<?php echo count($pending_users); ?>)</h5>
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
                                                    <td><strong><?php echo htmlspecialchars($pending_user['username']); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?php echo ucfirst(str_replace('_', ' ', $pending_user['role'])); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($pending_user['incharge_name'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($pending_user['created_at'])); ?></td>
                                                    <td>
                                                        <a href="?approve=<?php echo $pending_user['id']; ?>" 
                                                           class="btn btn-success btn-action me-2"
                                                           onclick="return confirm('Are you sure you want to approve this user?')">
                                                            <i class="fas fa-check"></i> Approve
                                                        </a>
                                                        <a href="?reject=<?php echo $pending_user['id']; ?>" 
                                                           class="btn btn-danger btn-action"
                                                           onclick="return confirm('Are you sure you want to reject this user?')">
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

                    <!-- All Users -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Incharge</th>
                                            <th>Manager</th>
                                            <th>Created Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_users as $user_data): ?>
                                            <tr>
                                                <td><?php echo $user_data['id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($user_data['username']); ?></strong></td>
                                                <td>
                                                    <span class="badge 
                                                        <?php 
                                                        echo $user_data['role'] === 'ie_manager' ? 'bg-danger' : 
                                                            ($user_data['role'] === 'ie_incharge' ? 'bg-warning' : 'bg-info'); 
                                                        ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $user_data['role'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge 
                                                        <?php 
                                                        echo $user_data['status'] === 'approved' ? 'bg-success' : 
                                                            ($user_data['status'] === 'pending' ? 'bg-warning' : 'bg-danger'); 
                                                        ?>">
                                                        <?php echo ucfirst($user_data['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($user_data['incharge_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($user_data['manager_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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