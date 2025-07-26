<?php
require_once '../../includes/session.php';

$error_message = '';

if ($_POST && isset($_POST['username']) && isset($_POST['password'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if ($session->login($username, $password)) {
        // Redirect based on user level
        switch ($_SESSION['user_level']) {
            case 'ie_manager':
                header('Location: ../dashboard/manager.php');
                break;
            case 'ie_incharge':
                header('Location: ../dashboard/incharge.php');
                break;
            case 'ie':
                header('Location: ../dashboard/ie.php');
                break;
        }
        exit();
    } else {
        $error_message = 'Invalid username or password, or account not approved.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div style="max-width: 400px; margin: 100px auto;">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title" style="text-align: center;">File Management System</h2>
                    <p style="text-align: center; color: #666;">Please sign in to your account</p>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
                    </div>
                </form>
                
                <div style="text-align: center; margin-top: 20px;">
                    <p>Don't have an account? <a href="signup.php" style="color: #667eea;">Sign up here</a></p>
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 0.9rem; color: #666;">
                    <h4>Demo Accounts:</h4>
                    <p><strong>Manager:</strong> admin / password</p>
                    <p><strong>Note:</strong> New signups require manager approval</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/app.js"></script>
</body>
</html>