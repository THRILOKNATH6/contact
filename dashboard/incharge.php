<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an IE Incharge
requireRole('ie_incharge');

$currentUser = getCurrentUser();
$forms = getForms();
$usersUnderIncharge = getUsersUnderIncharge($currentUser['id']);
$inchargeRecords = getRecords(null, null, null); // Will filter by user's under incharge

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
        header('Location: incharge.php?success=' . urlencode($success) . '&error=' . urlencode($error));
        exit();
    }
}

// Get success/error messages from URL
if (isset($_GET['success'])) $success = $_GET['success'];
if (isset($_GET['error'])) $error = $_GET['error'];

// Filter records to only show those from users under this incharge
$filteredRecords = array_filter($inchargeRecords, function($record) use ($usersUnderIncharge) {
    foreach ($usersUnderIncharge as $user) {
        if ($user['id'] == $record['user_id']) {
            return true;
        }
    }
    return false;
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IE Incharge Dashboard - File & Record Management System</title>
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
                        <i class="fas fa-user-shield fa-2x text-white mb-2"></i>
                        <h5 class="text-white">IE Incharge</h5>
                        <p class="text-white-50 small"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="incharge.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="manage_ies.php">
                            <i class="fas fa-users"></i> Manage IEs
                        </a>
                        <a class="nav-link" href="team_records.php">
                            <i class="fas fa-file-alt"></i> Team Records
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar"></i> Reports
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
                        <h2><i class="fas fa-tachometer-alt"></i> IE Incharge Dashboard</h2>
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
                                            <h4><?php echo count($usersUnderIncharge); ?></h4>
                                            <p class="mb-0">Team Members</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-users fa-2x"></i>
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
                                            <h4><?php echo count($filteredRecords); ?></h4>
                                            <p class="mb-0">Team Records</p>
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
                                            <h4><?php echo count(array_filter($filteredRecords, function($r) { return $r['status'] === 'draft'; })); ?></h4>
                                            <p class="mb-0">Pending Review</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-clock fa-2x"></i>
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
                                            <h4><?php echo count(array_filter($filteredRecords, function($r) { return $r['status'] === 'committed'; })); ?></h4>
                                            <p class="mb-0">Committed</p>
                                        </div>
                                        <div class="align-self-center">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Team Members -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users"></i> Team Members
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($usersUnderIncharge)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No team members assigned</h5>
                                    <p class="text-muted">Contact a manager to assign IEs to your team.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Email</th>
                                                <th>Records</th>
                                                <th>Last Activity</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($usersUnderIncharge as $user): ?>
                                                <?php 
                                                $userRecords = array_filter($filteredRecords, function($r) use ($user) {
                                                    return $r['user_id'] == $user['id'];
                                                });
                                                $lastRecord = end($userRecords);
                                                ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?php echo count($userRecords); ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($lastRecord): ?>
                                                            <?php echo date('M j, Y H:i', strtotime($lastRecord['created_at'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">No activity</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="user_records.php?user_id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View Records
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Team Records -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt"></i> Recent Team Records
                            </h5>
                            <a href="team_records.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($filteredRecords)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No records yet</h5>
                                    <p class="text-muted">Your team members haven't created any records yet.</p>
                                </div>
                            <?php else: ?>
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
                                            <?php foreach (array_slice($filteredRecords, 0, 10) as $record): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($record['form_name']); ?></strong>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($record['user_name']); ?></td>
                                                    <td><?php echo getStatusBadge($record['status']); ?></td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_record.php?id=<?php echo $record['id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <?php if ($record['status'] === 'draft'): ?>
                                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to commit this record?')">
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