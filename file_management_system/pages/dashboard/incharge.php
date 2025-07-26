<?php
require_once '../../includes/session.php';
$session->requireLevel('ie_incharge');

$database = new Database();
$db = $database->getConnection();

// Get statistics for IE Incharge
$stats = [];

// Files uploaded by lower level users
$query = "SELECT COUNT(*) as total FROM files f 
          JOIN users u ON f.uploaded_by = u.id 
          WHERE u.user_level = 'ie'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['ie_files'] = $stmt->fetchColumn();

// Records created by lower level users
$query = "SELECT COUNT(*) as total FROM records r 
          JOIN users u ON r.created_by = u.id 
          WHERE u.user_level = 'ie'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['ie_records'] = $stmt->fetchColumn();

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

// Get available forms for this user level
$query = "SELECT * FROM custom_forms WHERE target_user_level IN ('ie_incharge', 'ie_manager') AND is_active = 1 ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$available_forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get files that can be managed (uploaded by IE level users)
$query = "SELECT f.*, u.full_name as uploader_name FROM files f 
          JOIN users u ON f.uploaded_by = u.id 
          WHERE u.user_level = 'ie' OR f.uploaded_by = ?
          ORDER BY f.upload_date DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$manageable_files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get records that can be managed
$query = "SELECT r.*, u.full_name as creator_name, cf.form_name FROM records r 
          JOIN users u ON r.created_by = u.id 
          JOIN custom_forms cf ON r.form_id = cf.id
          WHERE u.user_level = 'ie' OR r.created_by = ?
          ORDER BY r.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$manageable_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IE Incharge Dashboard - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="incharge.php" class="logo">File Management System</a>
            <ul class="nav-links">
                <li><a href="incharge.php">Dashboard</a></li>
                <li><a href="../forms/upload_files.php">Upload Files</a></li>
                <li><a href="../forms/fill_forms.php">Fill Forms</a></li>
                <li><a href="../forms/my_data.php">My Data</a></li>
                <li><a href="../forms/manage_ie_data.php">Manage IE Data</a></li>
            </ul>
            <div class="user-info">
                <span class="user-level">IE Incharge</span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>IE Incharge Dashboard</h1>
        
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
                <div class="stat-number"><?php echo $stats['ie_files']; ?></div>
                <div class="stat-label">IE Files to Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['ie_records']; ?></div>
                <div class="stat-label">IE Records to Review</div>
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
                    <a href="../forms/manage_ie_data.php" class="btn btn-info">Manage IE Data</a>
                </div>
            </div>

            <!-- Available Forms -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Available Forms</h2>
                </div>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (empty($available_forms)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">No forms available for your level.</p>
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

        <!-- Files Management -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Files (IE and Mine)</h2>
            </div>
            <?php if (empty($manageable_files)): ?>
                <p style="text-align: center; padding: 20px; color: #666;">No files to display.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Uploaded By</th>
                            <th>Upload Date</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manageable_files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                            <td><?php echo htmlspecialchars($file['uploader_name']); ?></td>
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
                                <?php if ($session->canEdit($file['uploaded_by'], $file['is_committed'])): ?>
                                    <a href="../forms/edit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-warning">Edit</a>
                                    <?php if (!$file['is_committed']): ?>
                                        <a href="../forms/commit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-success commit-btn">Commit</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Records Management -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Records (IE and Mine)</h2>
            </div>
            <?php if (empty($manageable_records)): ?>
                <p style="text-align: center; padding: 20px; color: #666;">No records to display.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Form Name</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manageable_records as $record): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['form_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['creator_name']); ?></td>
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
                                <?php if ($session->canEdit($record['created_by'], $record['is_committed'])): ?>
                                    <a href="../forms/edit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-warning">Edit</a>
                                    <?php if (!$record['is_committed']): ?>
                                        <a href="../forms/commit_record.php?id=<?php echo $record['id']; ?>" class="btn btn-success commit-btn">Commit</a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="../../assets/js/app.js"></script>
</body>
</html>