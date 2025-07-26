<?php
require_once '../../config/database.php';

$success_message = '';
$error_message = '';

if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $user_level = $_POST['user_level'];
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error_message = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username or email already exists
            $check_query = "SELECT COUNT(*) FROM users WHERE username = ? OR email = ?";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$username, $email]);
            
            if ($check_stmt->fetchColumn() > 0) {
                $error_message = 'Username or email already exists.';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $insert_query = "INSERT INTO users (username, email, full_name, password, user_level, status) VALUES (?, ?, ?, ?, ?, 'pending')";
                $insert_stmt = $db->prepare($insert_query);
                
                if ($insert_stmt->execute([$username, $email, $full_name, $hashed_password, $user_level])) {
                    $success_message = 'Account created successfully! Please wait for manager approval before you can login.';
                } else {
                    $error_message = 'Failed to create account. Please try again.';
                }
            }
        } catch (Exception $e) {
            $error_message = 'Database error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div style="max-width: 500px; margin: 50px auto;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title" style="text-align: center;">Create Account</h2>
                    <p style="text-align: center; color: #666;">Join the File Management System</p>
                </div>
                
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
                
                <?php if (!$success_message): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-control" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="user_level" class="form-label">Position Level *</label>
                        <select id="user_level" name="user_level" class="form-control form-select" required>
                            <option value="">Select your level</option>
                            <option value="ie" <?php echo (isset($_POST['user_level']) && $_POST['user_level'] === 'ie') ? 'selected' : ''; ?>>IE (Industrial Engineer)</option>
                            <option value="ie_incharge" <?php echo (isset($_POST['user_level']) && $_POST['user_level'] === 'ie_incharge') ? 'selected' : ''; ?>>IE Incharge</option>
                        </select>
                        <small style="color: #666;">Note: IE Manager accounts can only be created by existing managers</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="6">
                        <small style="color: #666;">Minimum 6 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Create Account</button>
                    </div>
                </form>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 20px;">
                    <p>Already have an account? <a href="login.php" style="color: #667eea;">Sign in here</a></p>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9rem; color: #666;">
                    <h4>Important Note:</h4>
                    <p>All new accounts require approval from an IE Manager before you can access the system. You will receive an email notification once your account is approved.</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/app.js"></script>
</body>
</html>