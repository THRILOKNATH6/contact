# File & Record Management System

A comprehensive PHP web application for managing files and records with a 3-tier user hierarchy system. The system supports dynamic form creation, file uploads, and role-based access control.

## Features

### User Management
- **3-Tier User System:**
  - **IE Manager:** Full system access, user approval, form creation, record management
  - **IE Incharge:** Team management, record oversight, commit capabilities
  - **IE:** Basic record creation, file uploads, commit own records

### Core Functionality
- **Dynamic Form Builder:** Managers can create custom forms with various field types
- **Record Management:** Create, edit, view, and commit records
- **File Upload System:** Attach files to records with proper organization
- **Approval Workflow:** Manager approval required for new user registrations
- **Commit System:** Records can be committed to prevent further editing (except by managers)

### Security Features
- Password hashing with PHP's built-in password_hash()
- Session-based authentication
- Role-based access control
- SQL injection prevention with prepared statements
- XSS protection with htmlspecialchars()

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.2 or higher
- Web server (Apache/Nginx)
- PHP extensions: PDO, PDO_MySQL, JSON

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE file_management_system;
```

2. Update database configuration in `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'file_management_system');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 2. File Setup

1. Upload all files to your web server directory
2. Ensure the `uploads/` directory is writable:
```bash
chmod 755 uploads/
```

3. Set proper permissions for the application:
```bash
chmod 644 *.php
chmod 644 config/*.php
chmod 644 includes/*.php
chmod 644 dashboard/*.php
```

### 3. Initial Setup

1. Access the application in your browser
2. The database tables will be created automatically on first access
3. Register the first IE Manager account
4. Since there are no managers to approve, you'll need to manually approve the first manager in the database:
```sql
UPDATE users SET status = 'approved' WHERE role = 'ie_manager' LIMIT 1;
```

## Usage Guide

### For IE Managers

1. **User Approval:**
   - Access the dashboard to see pending user registrations
   - Approve or reject new user accounts
   - Assign IEs to specific Incharges

2. **Form Builder:**
   - Create dynamic forms with various field types
   - Define required/optional fields
   - Set up dropdown options for select fields

3. **Record Management:**
   - View all records in the system
   - Edit any record (even committed ones)
   - Commit/uncommit records as needed

### For IE Incharges

1. **Team Management:**
   - View IEs assigned to your team
   - Monitor their record creation activity
   - Access team member records

2. **Record Oversight:**
   - Review records created by team members
   - Commit records on behalf of team members
   - Generate team reports

### For IEs

1. **Record Creation:**
   - Select from available forms created by managers
   - Fill out dynamic forms with various field types
   - Save records as drafts

2. **File Management:**
   - Upload files to existing records
   - Organize files by record

3. **Record Commitment:**
   - Commit own records when ready
   - Cannot edit committed records (only managers can)

## File Structure

```
├── index.php                 # Main entry point
├── login.php                 # User login
├── signup.php                # User registration
├── logout.php                # Logout functionality
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── auth.php              # Authentication functions
│   └── functions.php         # General utility functions
├── dashboard/
│   ├── manager.php           # IE Manager dashboard
│   ├── incharge.php          # IE Incharge dashboard
│   ├── ie.php                # IE dashboard
│   ├── form_builder.php      # Dynamic form builder
│   └── create_record.php     # Record creation interface
├── uploads/                  # File upload directory
└── README.md                 # This file
```

## Database Schema

### Users Table
- `id` - Primary key
- `username` - Unique username
- `email` - Unique email address
- `password_hash` - Hashed password
- `role` - User role (ie, ie_incharge, ie_manager)
- `status` - Account status (pending, approved, rejected)
- `incharge_id` - Foreign key to incharge (for IEs)
- `manager_id` - Foreign key to manager (for Incharges)

### Forms Table
- `id` - Primary key
- `name` - Form name
- `description` - Form description
- `fields_json` - JSON structure of form fields
- `created_by` - User who created the form
- `is_active` - Form availability status

### Records Table
- `id` - Primary key
- `form_id` - Foreign key to forms table
- `user_id` - User who created the record
- `data_json` - JSON data from form submission
- `status` - Record status (draft, committed)
- `committed_by` - User who committed the record
- `committed_at` - Timestamp of commitment

### Files Table
- `id` - Primary key
- `record_id` - Foreign key to records table
- `filename` - Generated filename
- `original_filename` - Original uploaded filename
- `filepath` - File storage path
- `file_size` - File size in bytes
- `mime_type` - File MIME type
- `uploaded_by` - User who uploaded the file

## Security Considerations

1. **Password Security:** All passwords are hashed using PHP's `password_hash()` function
2. **SQL Injection:** All database queries use prepared statements
3. **XSS Protection:** All user input is escaped using `htmlspecialchars()`
4. **File Upload Security:** Files are stored with unique names and validated
5. **Session Security:** Proper session management and logout functionality

## Customization

### Adding New Field Types

To add new field types to the form builder:

1. Update the form builder JavaScript in `dashboard/form_builder.php`
2. Add the field type to the select options
3. Update the form rendering logic in `dashboard/create_record.php`
4. Add any necessary validation logic

### Styling

The application uses Bootstrap 5 for styling. You can customize the appearance by:

1. Modifying the CSS in the `<style>` sections
2. Replacing Bootstrap with your preferred CSS framework
3. Adding custom CSS classes

## Troubleshooting

### Common Issues

1. **Database Connection Error:**
   - Verify database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Check database permissions

2. **File Upload Issues:**
   - Verify `uploads/` directory permissions
   - Check PHP upload settings in `php.ini`
   - Ensure sufficient disk space

3. **Session Issues:**
   - Check PHP session configuration
   - Verify session storage permissions
   - Clear browser cookies if needed

### Error Logging

Enable PHP error logging by adding to your `php.ini`:
```ini
error_reporting = E_ALL
log_errors = On
error_log = /path/to/error.log
```

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the code comments for implementation details
3. Verify your server meets the requirements
4. Check the error logs for specific error messages

## License

This project is open source and available under the MIT License.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

---

**Note:** This system is designed for internal use within organizations. Ensure proper security measures are in place before deploying to production environments.