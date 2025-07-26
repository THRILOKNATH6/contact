<?php
require_once '../../includes/session.php';
$session->requireLogin();

$database = new Database();
$db = $database->getConnection();

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$file_id) {
    header('Location: upload_files.php');
    exit();
}

// Get file information and check permissions
$query = "SELECT f.*, u.full_name as uploader_name FROM files f 
          JOIN users u ON f.uploaded_by = u.id 
          WHERE f.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    header('Location: upload_files.php?error=file_not_found');
    exit();
}

// Check if user can commit this file
if (!$session->canEdit($file['uploaded_by'], $file['is_committed']) && $file['uploaded_by'] != $_SESSION['user_id']) {
    header('Location: upload_files.php?error=access_denied');
    exit();
}

if ($file['is_committed']) {
    header('Location: upload_files.php?error=already_committed');
    exit();
}

$success_message = '';
$error_message = '';

if ($_POST && isset($_POST['confirm_commit'])) {
    try {
        $query = "UPDATE files SET is_committed = 1, committed_at = NOW() WHERE id = ? AND is_committed = 0";
        $stmt = $db->prepare($query);
        
        if ($stmt->execute([$file_id])) {
            $session->logActivity($_SESSION['user_id'], 'commit_file', 'file', $file_id, 'Committed file: ' . $file['original_filename']);
            
            // Redirect back to upload files with success message
            header('Location: upload_files.php?success=file_committed');
            exit();
        } else {
            $error_message = 'Failed to commit file. Please try again.';
        }
    } catch (Exception $e) {
        $error_message = 'Database error occurred. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commit File - File Management System</title>
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
            </ul>
            <div class="user-info">
                <span class="user-level"><?php echo strtoupper(str_replace('_', ' ', $_SESSION['user_level'])); ?></span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Commit File</h1>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Confirm File Commit</h2>
            </div>
            
            <div class="alert alert-warning">
                <h4>⚠️ Important Warning</h4>
                <p>You are about to commit this file. Once committed:</p>
                <ul>
                    <li>The file cannot be edited or deleted</li>
                    <li>The file becomes permanent in the system</li>
                    <li>Only higher-level users can modify committed files</li>
                    <li>This action cannot be undone</li>
                </ul>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- File Information -->
                <div>
                    <h3>File Information</h3>
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px; background: #f8f9fa; font-weight: bold;">File Name:</td>
                            <td style="padding: 8px;"><?php echo htmlspecialchars($file['original_filename']); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; background: #f8f9fa; font-weight: bold;">Uploaded By:</td>
                            <td style="padding: 8px;"><?php echo htmlspecialchars($file['uploader_name']); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; background: #f8f9fa; font-weight: bold;">Upload Date:</td>
                            <td style="padding: 8px;"><?php echo date('M j, Y g:i A', strtotime($file['upload_date'])); ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; background: #f8f9fa; font-weight: bold;">File Size:</td>
                            <td style="padding: 8px;"><?php echo number_format($file['file_size'] / 1024, 2); ?> KB</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px; background: #f8f9fa; font-weight: bold;">Current Status:</td>
                            <td style="padding: 8px;"><span class="badge badge-warning">Draft</span></td>
                        </tr>
                    </table>
                </div>
                
                <!-- Description -->
                <div>
                    <h3>Description</h3>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; min-height: 100px;">
                        <?php if ($file['description']): ?>
                            <?php echo nl2br(htmlspecialchars($file['description'])); ?>
                        <?php else: ?>
                            <em style="color: #666;">No description provided</em>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div style="margin-top: 2rem; text-align: center;">
                <form method="POST" action="" style="display: inline;">
                    <button type="submit" name="confirm_commit" value="1" class="btn btn-success" 
                            onclick="return confirm('Are you absolutely sure you want to commit this file? This action cannot be undone.')">
                        ✓ Yes, Commit File
                    </button>
                </form>
                <a href="upload_files.php" class="btn btn-secondary">Cancel</a>
                <a href="view_file.php?id=<?php echo $file['id']; ?>" class="btn btn-info">View File Details</a>
            </div>
        </div>

        <!-- Commit Process Explanation -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">What happens when you commit?</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div>
                    <h4>✅ File Security</h4>
                    <p>The file becomes read-only and cannot be accidentally modified or deleted.</p>
                </div>
                
                <div>
                    <h4>📁 Permanent Storage</h4>
                    <p>The file is marked as finalized and becomes part of the official record.</p>
                </div>
                
                <div>
                    <h4>👥 Access Control</h4>
                    <p>Only users with higher permissions can modify committed files if needed.</p>
                </div>
                
                <div>
                    <h4>📊 Audit Trail</h4>
                    <p>The commit action is logged and tracked in the system activity logs.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/app.js"></script>
</body>
</html>