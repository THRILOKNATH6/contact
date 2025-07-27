<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a manager
requireRole('ie_manager');

$currentUser = getCurrentUser();
$pendingUsers = getPendingUsers();
$forms = getForms();
$allRecords = getRecords();

// Handle user approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId = $_POST['user_id'];
    
    if ($_POST['action'] === 'approve') {
        approveUser($userId);
        header('Location: manager.php?success=user_approved');
        exit();
    } elseif ($_POST['action'] === 'reject') {
        rejectUser($userId);
        header('Location: manager.php?success=user_rejected');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IE Manager Dashboard - File & Record Management System</title>
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
                        <i class="fas fa-user-tie fa-2x text-white mb-2"></i>
                        <h5 class="text-white">IE Manager</h5>
                        <p class="text-white-50 small"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="manager.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="user_approval.php">
                            <i class="fas fa-user-check"></i> User Approval
                            <?php if (count($pendingUsers) > 0): ?>
                                <span class="badge bg-danger ms-2"><?php echo count($pendingUsers); ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="form_builder.php">
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
                        <h2><i class="fas fa-tachometer-alt"></i> Manager Dashboard</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar"></i> <?php echo date('F j, Y'); ?>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php if ($_GET['success'] === 'user_approved'): ?>
                                <i class="fas fa-check-circle"></i> User approved successfully!
                            <?php elseif ($_GET['success'] === 'user_rejected'): ?>
                                <i class="fas fa-check-circle"></i> User rejected successfully!
                            <?php endif; ?>
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
                                            <h4><?php echo count($pendingUsers); ?></h4>
                                            <p class="mb-0">Pending Approvals</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-user-clock fa-2x"></i>
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
                                            <h4><?php echo count($forms); ?></h4>
                                            <p class="mb-0">Active Forms</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
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
                                            <h4><?php echo count($allRecords); ?></h4>
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
                                            <h4><?php echo count(array_filter($allRecords, function($r) { return $r['status'] === 'draft'; })); ?></h4>
                                            <p class="mb-0">Draft Records</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-edit fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pending Approvals -->
                    <?php if (count($pendingUsers) > 0): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-user-clock"></i> Pending User Approvals
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Registered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pendingUsers as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo getRoleDisplayName($user['role']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                                    <td>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="action" value="approve" 
                                                                    class="btn btn-success btn-sm">
                                                                <i class="fas fa-check"></i> Approve
                                                            </button>
                                                            <button type="submit" name="action" value="reject" 
                                                                    class="btn btn-danger btn-sm">
                                                                <i class="fas fa-times"></i> Reject
                                                            </button>
                                                        </form>
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
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt"></i> Recent Records
                            </h5>
                            <a href="records.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Form</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($allRecords, 0, 10) as $record): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($record['form_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                                                <td><?php echo getStatusBadge($record['status']); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?></td>
                                                <td>
                                                    <a href="view_record.php?id=<?php echo $record['id']; ?>" 
                                                       class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>