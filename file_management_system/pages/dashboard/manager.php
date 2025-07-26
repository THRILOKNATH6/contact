<?php
require_once '../../includes/session.php';
$session->requireLevel('ie_manager');

$database = new Database();
$db = $database->getConnection();

// Handle user approval/rejection
if (isset($_POST['approve_user'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $query = "UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        $session->logActivity($_SESSION['user_id'], 'approve_user', 'user', $user_id, 'User approved');
    } elseif ($action === 'reject') {
        $query = "UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $user_id]);
        $session->logActivity($_SESSION['user_id'], 'reject_user', 'user', $user_id, 'User rejected');
    }
}

// Get statistics
$stats = [];

// Users count
$query = "SELECT COUNT(*) as total, status FROM users GROUP BY status";
$stmt = $db->prepare($query);
$stmt->execute();
$user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($user_stats as $stat) {
    $stats['users_' . $stat['status']] = $stat['total'];
}

// Files count
$query = "SELECT COUNT(*) as total FROM files";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_files'] = $stmt->fetchColumn();

// Records count
$query = "SELECT COUNT(*) as total FROM records";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_records'] = $stmt->fetchColumn();

// Forms count
$query = "SELECT COUNT(*) as total FROM custom_forms WHERE is_active = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_forms'] = $stmt->fetchColumn();

// Get pending users
$query = "SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pending_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity
$query = "SELECT al.*, u.full_name FROM activity_logs al 
          JOIN users u ON al.user_id = u.id 
          ORDER BY al.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all users for management
$query = "SELECT * FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="manager.php" class="logo">File Management System</a>
            <ul class="nav-links">
                <li><a href="manager.php">Dashboard</a></li>
                <li><a href="../forms/create_form.php">Create Forms</a></li>
                <li><a href="../forms/manage_forms.php">Manage Forms</a></li>
                <li><a href="users.php">Manage Users</a></li>
                <li><a href="reports.php">Reports</a></li>
            </ul>
            <div class="user-info">
                <span class="user-level">IE Manager</span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Manager Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_files'] ?? 0; ?></div>
                <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_records'] ?? 0; ?></div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['active_forms'] ?? 0; ?></div>
                <div class="stat-label">Active Forms</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($pending_users); ?></div>
                <div class="stat-label">Pending Approvals</div>
            </div>
        </div>

        <!-- Pending User Approvals -->
        <?php if (!empty($pending_users)): ?>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Pending User Approvals</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Requested</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo strtoupper(str_replace('_', ' ', $user['user_level'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y g:i A', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="approve_user" value="approve_user" 
                                        onclick="this.form.action.value='approve'" class="btn btn-success">Approve</button>
                                <button type="submit" name="approve_user" value="approve_user" 
                                        onclick="this.form.action.value='reject'" class="btn btn-danger">Reject</button>
                                <input type="hidden" name="action" value="">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="../forms/create_form.php" class="btn btn-primary">Create New Form</a>
                    <a href="../forms/upload_files.php" class="btn btn-success">Upload Files</a>
                    <a href="users.php" class="btn btn-warning">Manage Users</a>
                    <a href="reports.php" class="btn btn-secondary">View Reports</a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Recent Activity</h2>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_activity as $activity): ?>
                    <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                        <span style="color: #666;"><?php echo htmlspecialchars($activity['action']); ?></span>
                        <br>
                        <small style="color: #999;"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></small>
                        <?php if ($activity['description']): ?>
                        <br>
                        <small><?php echo htmlspecialchars($activity['description']); ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- All Users Overview -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">All Users</h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo strtoupper(str_replace('_', ' ', $user['user_level'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $status_class = '';
                            switch($user['status']) {
                                case 'approved': $status_class = 'badge-success'; break;
                                case 'pending': $status_class = 'badge-warning'; break;
                                case 'rejected': $status_class = 'badge-danger'; break;
                            }
                            ?>
                            <span class="badge <?php echo $status_class; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-warning">Edit</a>
                            <?php else: ?>
                                <span class="badge badge-info">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../../assets/js/app.js"></script>
    <script>
        // Add click handlers for approve/reject buttons
        document.querySelectorAll('button[name="approve_user"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const action = this.textContent.toLowerCase();
                const form = this.closest('form');
                form.querySelector('input[name="action"]').value = action;
                
                if (confirm(`Are you sure you want to ${action} this user?`)) {
                    form.submit();
                }
            });
        });
    </script>
</body>
</html>