<?php
require_once '../includes/auth.php';
require_once '../classes/User.php';

$auth = new Auth();
$user = new User();

if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';
    $incharge_id = $_POST['incharge_id'] ?? null;
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif ($role === 'ie' && empty($incharge_id)) {
        $error = 'Please select an IE Incharge.';
    } else {
        if ($user->register($username, $password, $role, $incharge_id)) {
            $success = 'Registration successful! Please wait for manager approval.';
        } else {
            $error = 'Username already exists or registration failed.';
        }
    }
}

$incharges = $user->getIncharges();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - File Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e1e5e9;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        #incharge_field {
            display: none;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h2 class="h4 text-dark mb-0">Create Account</h2>
            <p class="text-muted">Join the File Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <div class="mb-3">
                <label for="username" class="form-label">
                    <i class="fas fa-user me-2"></i>Username
                </label>
                <input type="text" class="form-control" id="username" name="username" required 
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">
                    <i class="fas fa-user-tag me-2"></i>Role
                </label>
                <select class="form-select" id="role" name="role" required onchange="toggleInchargeField()">
                    <option value="">Select Role</option>
                    <option value="ie" <?php echo ($_POST['role'] ?? '') === 'ie' ? 'selected' : ''; ?>>IE</option>
                    <option value="ie_incharge" <?php echo ($_POST['role'] ?? '') === 'ie_incharge' ? 'selected' : ''; ?>>IE Incharge</option>
                </select>
            </div>

            <div class="mb-3" id="incharge_field">
                <label for="incharge_id" class="form-label">
                    <i class="fas fa-user-tie me-2"></i>Select IE Incharge
                </label>
                <select class="form-select" id="incharge_id" name="incharge_id">
                    <option value="">Select IE Incharge</option>
                    <?php foreach ($incharges as $incharge): ?>
                        <option value="<?php echo $incharge['id']; ?>" 
                                <?php echo ($_POST['incharge_id'] ?? '') == $incharge['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($incharge['username']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Password
                </label>
                <input type="password" class="form-control" id="password" name="password" required minlength="6">
                <div class="form-text">Password must be at least 6 characters long.</div>
            </div>

            <div class="mb-4">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock me-2"></i>Confirm Password
                </label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>

        <div class="login-link">
            <p class="mb-0">Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleInchargeField() {
            const role = document.getElementById('role').value;
            const inchargeField = document.getElementById('incharge_field');
            const inchargeSelect = document.getElementById('incharge_id');
            
            if (role === 'ie') {
                inchargeField.style.display = 'block';
                inchargeSelect.required = true;
            } else {
                inchargeField.style.display = 'none';
                inchargeSelect.required = false;
                inchargeSelect.value = '';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleInchargeField();
        });
    </script>
</body>
</html>