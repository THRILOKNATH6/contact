<?php
require_once '../../includes/session.php';
$session->requireLogin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

if ($_POST && isset($_FILES['file'])) {
    $description = trim($_POST['description']);
    $file = $_FILES['file'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_message = 'File upload failed. Please try again.';
    } else {
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            $error_message = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
        } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            $error_message = 'File size must be less than 10MB.';
        } else {
            // Create upload directory if it doesn't exist
            $upload_dir = '../../assets/uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                try {
                    $query = "INSERT INTO files (filename, original_filename, file_path, file_size, mime_type, uploaded_by, description) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $db->prepare($query);
                    
                    if ($stmt->execute([$unique_filename, $file['name'], $file_path, $file['size'], $file['type'], $_SESSION['user_id'], $description])) {
                        $session->logActivity($_SESSION['user_id'], 'upload_file', 'file', $db->lastInsertId(), 'Uploaded file: ' . $file['name']);
                        $success_message = 'File uploaded successfully!';
                    } else {
                        $error_message = 'Failed to save file information. Please try again.';
                        unlink($file_path); // Remove uploaded file
                    }
                } catch (Exception $e) {
                    $error_message = 'Database error occurred. Please try again.';
                    unlink($file_path); // Remove uploaded file
                }
            } else {
                $error_message = 'Failed to upload file. Please try again.';
            }
        }
    }
}

// Get user's uploaded files
$query = "SELECT * FROM files WHERE uploaded_by = ? ORDER BY upload_date DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Files - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <?php 
            $dashboard_link = '';
            switch($_SESSION['user_level']) {
                case 'ie_manager': $dashboard_link = '../dashboard/manager.php'; break;
                case 'ie_incharge': $dashboard_link = '../dashboard/incharge.php'; break;
                case 'ie': $dashboard_link = '../dashboard/ie.php'; break;
            }
            ?>
            <a href="<?php echo $dashboard_link; ?>" class="logo">File Management System</a>
            <ul class="nav-links">
                <li><a href="<?php echo $dashboard_link; ?>">Dashboard</a></li>
                <li><a href="upload_files.php">Upload Files</a></li>
                <li><a href="fill_forms.php">Fill Forms</a></li>
                <li><a href="my_data.php">My Data</a></li>
                <?php if ($_SESSION['user_level'] === 'ie_manager'): ?>
                    <li><a href="create_form.php">Create Forms</a></li>
                    <li><a href="manage_forms.php">Manage Forms</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-info">
                <span class="user-level"><?php echo strtoupper(str_replace('_', ' ', $_SESSION['user_level'])); ?></span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Upload Files</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Upload Form -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upload New File</h2>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="file" class="form-label">Select File *</label>
                        <div class="upload-area">
                            <input type="file" id="file" name="file" required accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.gif">
                            <p>Click to select file or drag and drop</p>
                            <small>Allowed: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, GIF (Max: 10MB)</small>
                            <div class="file-list"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Optional description for this file..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Upload File</button>
                        <button type="reset" class="btn btn-secondary">Clear</button>
                    </div>
                </form>
            </div>

            <!-- Upload Guidelines -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Upload Guidelines</h2>
                </div>
                
                <div>
                    <h4>Allowed File Types:</h4>
                    <ul>
                        <li>Documents: PDF, DOC, DOCX</li>
                        <li>Spreadsheets: XLS, XLSX</li>
                        <li>Text Files: TXT</li>
                        <li>Images: JPG, JPEG, PNG, GIF</li>
                    </ul>
                    
                    <h4>File Size Limits:</h4>
                    <ul>
                        <li>Maximum file size: 10MB</li>
                        <li>For larger files, consider compressing or splitting them</li>
                    </ul>
                    
                    <h4>Best Practices:</h4>
                    <ul>
                        <li>Use descriptive filenames</li>
                        <li>Add meaningful descriptions</li>
                        <li>Organize files logically</li>
                        <li>Review before committing</li>
                    </ul>
                    
                    <h4>Important Notes:</h4>
                    <ul>
                        <li>Files are saved as drafts initially</li>
                        <li>Use the "Commit" button when ready to finalize</li>
                        <li>Committed files cannot be edited</li>
                        <?php if ($_SESSION['user_level'] !== 'ie_manager'): ?>
                        <li>Higher-level users can edit your files</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- My Files -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">My Files</h2>
            </div>
            
            <?php if (empty($user_files)): ?>
                <p style="text-align: center; padding: 20px; color: #666;">No files uploaded yet.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Description</th>
                            <th>Upload Date</th>
                            <th>Size</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_files as $file): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['original_filename']); ?></td>
                            <td><?php echo htmlspecialchars($file['description'] ?: 'No description'); ?></td>
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
                                <a href="view_file.php?id=<?php echo $file['id']; ?>" class="btn btn-info">View</a>
                                <a href="download_file.php?id=<?php echo $file['id']; ?>" class="btn btn-secondary">Download</a>
                                <?php if (!$file['is_committed']): ?>
                                    <a href="edit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-warning">Edit</a>
                                    <a href="commit_file.php?id=<?php echo $file['id']; ?>" class="btn btn-success commit-btn">Commit</a>
                                    <a href="delete_file.php?id=<?php echo $file['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?')">Delete</a>
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