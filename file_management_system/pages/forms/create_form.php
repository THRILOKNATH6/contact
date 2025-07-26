<?php
require_once '../../includes/session.php';
$session->requireLevel('ie_manager');

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

if ($_POST && isset($_POST['form_name'])) {
    $form_name = trim($_POST['form_name']);
    $form_description = trim($_POST['form_description']);
    $target_user_level = $_POST['target_user_level'];
    $form_structure = $_POST['form_structure'];
    
    if (empty($form_name) || empty($form_structure)) {
        $error_message = 'Form name and at least one field are required.';
    } else {
        try {
            // Validate JSON structure
            $fields = json_decode($form_structure, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($fields)) {
                $error_message = 'Invalid form structure. Please add at least one field.';
            } else {
                $query = "INSERT INTO custom_forms (form_name, form_description, form_fields, created_by, target_user_level) VALUES (?, ?, ?, ?, ?)";
                $stmt = $db->prepare($query);
                
                if ($stmt->execute([$form_name, $form_description, $form_structure, $_SESSION['user_id'], $target_user_level])) {
                    $session->logActivity($_SESSION['user_id'], 'create_form', 'form', $db->lastInsertId(), 'Created form: ' . $form_name);
                    $success_message = 'Form created successfully!';
                } else {
                    $error_message = 'Failed to create form. Please try again.';
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
    <title>Create Form - File Management System</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <a href="../dashboard/manager.php" class="logo">File Management System</a>
            <ul class="nav-links">
                <li><a href="../dashboard/manager.php">Dashboard</a></li>
                <li><a href="create_form.php">Create Forms</a></li>
                <li><a href="manage_forms.php">Manage Forms</a></li>
                <li><a href="../dashboard/users.php">Manage Users</a></li>
                <li><a href="../dashboard/reports.php">Reports</a></li>
            </ul>
            <div class="user-info">
                <span class="user-level">IE Manager</span>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                <a href="../auth/logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </nav>
    </header>

    <div class="container">
        <h1>Create Custom Form</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <br><a href="manage_forms.php">View all forms</a> | <a href="create_form.php">Create another form</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Form Builder -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Form Details & Builder</h2>
                </div>
                
                <form method="POST" action="" id="form-creator">
                    <div class="form-group">
                        <label for="form_name" class="form-label">Form Name *</label>
                        <input type="text" id="form_name" name="form_name" class="form-control" required 
                               value="<?php echo isset($_POST['form_name']) ? htmlspecialchars($_POST['form_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="form_description" class="form-label">Form Description</label>
                        <textarea id="form_description" name="form_description" class="form-control" rows="3"><?php echo isset($_POST['form_description']) ? htmlspecialchars($_POST['form_description']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="target_user_level" class="form-label">Target User Level *</label>
                        <select id="target_user_level" name="target_user_level" class="form-control form-select" required>
                            <option value="">Select target level</option>
                            <option value="ie" <?php echo (isset($_POST['target_user_level']) && $_POST['target_user_level'] === 'ie') ? 'selected' : ''; ?>>IE (Industrial Engineer)</option>
                            <option value="ie_incharge" <?php echo (isset($_POST['target_user_level']) && $_POST['target_user_level'] === 'ie_incharge') ? 'selected' : ''; ?>>IE Incharge</option>
                            <option value="ie_manager" <?php echo (isset($_POST['target_user_level']) && $_POST['target_user_level'] === 'ie_manager') ? 'selected' : ''; ?>>IE Manager</option>
                        </select>
                        <small style="color: #666;">Who can fill this form</small>
                    </div>
                    
                    <div class="form-group">
                        <h3>Form Fields</h3>
                        <button type="button" id="add-field" class="btn btn-primary">Add Field</button>
                    </div>
                    
                    <div id="fields-container">
                        <!-- Dynamic fields will be added here -->
                    </div>
                    
                    <input type="hidden" id="form-structure" name="form_structure" value="">
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Create Form</button>
                        <a href="../dashboard/manager.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>

            <!-- Form Preview -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Live Preview</h2>
                </div>
                <div id="form-preview">
                    <p style="text-align: center; color: #666; padding: 40px;">Add fields to see the preview</p>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Instructions</h2>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <div>
                    <h4>Available Field Types:</h4>
                    <ul>
                        <li><strong>Text Input:</strong> Single line text</li>
                        <li><strong>Textarea:</strong> Multi-line text</li>
                        <li><strong>Select Dropdown:</strong> Choose one option</li>
                        <li><strong>Radio Buttons:</strong> Choose one option</li>
                        <li><strong>Checkbox:</strong> Multiple selections</li>
                        <li><strong>File Upload:</strong> Upload files</li>
                        <li><strong>Date:</strong> Date picker</li>
                        <li><strong>Number:</strong> Numeric input</li>
                        <li><strong>Email:</strong> Email validation</li>
                    </ul>
                </div>
                
                <div>
                    <h4>Tips:</h4>
                    <ul>
                        <li>Use clear and descriptive field labels</li>
                        <li>Mark important fields as required</li>
                        <li>For select, radio, and checkbox fields, enter options one per line</li>
                        <li>Preview your form before creating</li>
                        <li>Choose the appropriate target user level</li>
                    </ul>
                </div>
                
                <div>
                    <h4>User Level Access:</h4>
                    <ul>
                        <li><strong>IE:</strong> Can only fill forms assigned to IE level</li>
                        <li><strong>IE Incharge:</strong> Can fill IE and IE Incharge forms</li>
                        <li><strong>IE Manager:</strong> Can fill all forms</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/app.js"></script>
    <script>
        // Initialize form builder
        document.addEventListener('DOMContentLoaded', function() {
            const formBuilder = document.getElementById('form-creator');
            if (formBuilder) {
                console.log('Form builder initialized');
            }
        });
    </script>
</body>
</html>