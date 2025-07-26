<?php
require_once '../../includes/session.php';
$session->requireLevel('ie');

$database = new Database();
$db = $database->getConnection();

// Get statistics for IE user
$stats = [];

// My files
$query = "SELECT COUNT(*) as total FROM files WHERE uploaded_by = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['my_files'] = $stmt->fetchColumn();

// My records
$query = "SELECT COUNT(*) as total FROM records WHERE created_by = ?";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['my_records'] = $stmt->fetchColumn();

// Committed files
$query = "SELECT COUNT(*) as total FROM files WHERE uploaded_by = ? AND is_committed = 1";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['committed_files'] = $stmt->fetchColumn();

// Committed records
$query = "SELECT COUNT(*) as total FROM records WHERE created_by = ? AND is_committed = 1";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats['committed_records'] = $stmt->fetchColumn();

// Get available forms for IE level
$query = "SELECT * FROM custom_forms WHERE target_user_level IN ('ie', 'ie_incharge', 'ie_manager') AND is_active = 1 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$available_forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my files
$query = "SELECT * FROM files WHERE uploaded_by = ? ORDER BY upload_date DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$my_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get my records
$query = "SELECT r.*, cf.form_name FROM records r 
          JOIN custom_forms cf ON r.form_id = cf.id
          WHERE r.created_by = ? 
          ORDER BY r.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$my_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IE Dashboard - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="ie.php" class="logo">File Management System</a>
            <ul class="nav-links">
                <li><a href="ie.php">Dashboard</a></li>
                <li><a href="../forms/upload_files.php">Upload Files</a></li>
                <li><a href="../forms/fill_forms.php">Fill Forms</a></li>
                <li><a href="../forms/my_data.php">My Data</a></li>
            </ul>
            <div class="user-info">
                <span class="user-level">IE</span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>IE Dashboard</h1>
        
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['my_files']; ?></div>
                <div class="stat-label">My Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['my_records']; ?></div>
                <div class="stat-label">My Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['committed_files']; ?></div>
                <div class="stat-label">Committed Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['committed_records']; ?></div>
                <div class="stat-label">Committed Records</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Quick Actions</h2>
                </div>
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <a href="../forms/upload_files.php" class="btn btn-primary">Upload Files</a>
                    <a href="../forms/fill_forms.php" class="btn btn-success">Fill Forms</a>
                    <a href="../forms/my_data.php" class="btn btn-warning">View My Data</a>
                </div>
            </div>

            <!-- Available Forms -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Available Forms</h2>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($available_forms)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">No forms available.</p>
                    <?php else: ?>
                        <?php foreach ($available_forms as $form): ?>
                        <div style="border-bottom: 1px solid #eee; padding: 10px 0;">
                            <strong><?php echo htmlspecialchars($form['form_name']); ?></strong>
                            <br>
                            <small style="color: #666;"><?php echo htmlspecialchars($form['form_description']); ?></small>
                            <br>
                            <a href="../forms/fill_form.php?id=<?php echo $form['id']; ?>" class="btn btn-primary" style="margin-top: 5px;">Fill Form</a>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Files -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Recent Files</h2>
            </div>
            <?php if (empty($my_files)): ?>
                <p style="text-align: center; padding: 20px; color: #666;">No files uploaded yet. <a href="../forms/upload_files.php">Upload your first file</a></p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($file['upload_date'])); ?></td>
                            <td><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                            <td>
                                <?php if ($file['is_committed']): ?>
                                    <span class="badge badge-success">Committed</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../forms/view_file.php?id=<?php echo $file['id']; ?>" class="btn btn-info">View</a>
                                <?php if (!$file['is_committed']): ?>
                                    <a href="../forms/edit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-warning">Edit</a>
                                    <a href="../forms/commit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-success commit-btn">Commit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- My Records -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Recent Records</h2>
            </div>
            <?php if (empty($my_records)): ?>
                <p style="text-align: center; padding: 20px; color: #666;">No records created yet. <a href="../forms/fill_forms.php">Fill your first form</a></p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Form Name</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['form_name']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($record['created_at'])); ?></td>
                            <td>
                                <?php if ($record['is_committed']): ?>
                                    <span class="badge badge-success">Committed</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../forms/view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-info">View</a>
                                <?php if (!$record['is_committed']): ?>
                                    <a href="../forms/edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-warning">Edit</a>
                                    <a href="../forms/commit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-success commit-btn">Commit</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Notice for IE Users -->
        <div class="alert alert-info">
            <h4>Important Notice:</h4>
            <p>As an IE (Industrial Engineer), you can upload files and fill forms. Once you commit your data, it cannot be edited. Only your supervisors (IE Incharge and IE Manager) can modify your committed data.</p>
        </div>
    </div>

    <script src="../../assets/js/app.js"></script>
</body>
</html>