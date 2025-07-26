# File Management System

A comprehensive PHP-based file and record management system with role-based access control for IE (Industrial Engineer) organizations.

## Features

### User Levels & Permissions
1. **IE (Industrial Engineer)** - Basic level
   - Upload files and fill forms
   - View own data
   - Commit own data (cannot edit after commit)

2. **IE Incharge** - Middle management
   - All IE permissions
   - Can edit IE users' uncommitted data
   - Can commit IE users' data
   - Access to forms designated for IE Incharge level

3. **IE Manager** - Full administrative control
   - All lower-level permissions
   - Create and manage custom forms
   - Approve/reject user registrations
   - Manage all users and data
   - Access to all forms and reports
   - Can edit any uncommitted data

### Core Functionality
- **User Registration & Approval**: New users need manager approval
- **File Upload System**: Secure file upload with validation
- **Dynamic Form Builder**: Managers can create custom forms
- **Commit System**: Data becomes read-only after committing
- **Role-based Access Control**: Hierarchical permission system
- **Activity Logging**: Complete audit trail
- **Responsive Design**: Modern, mobile-friendly interface

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE file_management_db;
```

2. Import the database schema:
```bash
mysql -u your_username -p file_management_db < database/schema.sql
```

Or manually run the SQL commands from `database/schema.sql`

### 2. Configuration

1. Update database credentials in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'file_management_db';
private $username = 'your_mysql_username';
private $password = 'your_mysql_password';
```

### 3. File Permissions

Set appropriate permissions for the uploads directory:
```bash
chmod 755 assets/uploads/
```

### 4. Web Server Configuration

#### Apache
Ensure `.htaccess` support is enabled and `mod_rewrite` is active.

#### Nginx
Add this to your nginx configuration:
```nginx
location ~ \.php$ {
    try_files $uri =404;
    fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

## Default Login

After installation, use these credentials to access the system:

- **Username**: admin
- **Password**: password
- **Level**: IE Manager

⚠️ **Important**: Change the default password immediately after first login.

## File Structure

```
file_management_system/
├── assets/
│   ├── css/style.css          # Main stylesheet
│   ├── js/app.js              # JavaScript functionality
│   └── uploads/               # File upload directory
├── config/
│   └── database.php           # Database configuration
├── database/
│   └── schema.sql             # Database schema
├── includes/
│   └── session.php            # Session management
├── pages/
│   ├── auth/                  # Authentication pages
│   ├── dashboard/             # User dashboards
│   └── forms/                 # Form-related pages
├── index.php                  # Main entry point
└── README.md                  # This file
```

## Usage Guide

### For IE Users
1. **Register**: Sign up and wait for manager approval
2. **Upload Files**: Use drag-and-drop or click to upload
3. **Fill Forms**: Complete forms created by managers
4. **Commit Data**: Finalize your work (cannot edit after)

### For IE Incharge
1. All IE user capabilities
2. **Review IE Data**: View and edit IE users' uncommitted data
3. **Manage Submissions**: Approve and commit IE users' work

### For IE Managers
1. All lower-level capabilities
2. **User Management**: Approve/reject registrations
3. **Form Creation**: Build custom forms with various field types
4. **System Administration**: Full access to all data and settings

## Security Features

- **Password Hashing**: bcrypt encryption for all passwords
- **Session Management**: Secure session handling
- **File Validation**: Strict file type and size validation
- **SQL Injection Protection**: Prepared statements
- **Access Control**: Role-based permission checking
- **Activity Logging**: Complete audit trail

## Supported File Types

- **Documents**: PDF, DOC, DOCX
- **Spreadsheets**: XLS, XLSX
- **Text**: TXT
- **Images**: JPG, JPEG, PNG, GIF
- **Maximum Size**: 10MB per file

## Form Builder

Managers can create dynamic forms with these field types:
- Text Input
- Textarea
- Select Dropdown
- Radio Buttons
- Checkboxes
- File Upload
- Date Picker
- Number Input
- Email Input

## API Endpoints

The system includes basic AJAX endpoints for:
- Form validation
- File upload progress
- Dynamic content loading
- User management actions

## Troubleshooting

### Common Issues

1. **File Upload Fails**
   - Check directory permissions
   - Verify file size limits in PHP configuration
   - Ensure allowed file types

2. **Database Connection Error**
   - Verify credentials in `config/database.php`
   - Check MySQL server status
   - Confirm database exists

3. **Permission Denied**
   - Clear browser cache and cookies
   - Check user level and approval status
   - Verify session configuration

### Debug Mode

To enable debug mode, add this to any PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Check the troubleshooting section
- Review the code documentation
- Submit issues via the repository

## Version History

- **v1.0.0**: Initial release with core functionality
- **v1.1.0**: Added form builder and enhanced UI
- **v1.2.0**: Implemented commit system and activity logging

## Security Notes

- Regularly update PHP and MySQL
- Monitor file uploads for security
- Review user permissions periodically
- Keep the system updated
- Backup database regularly
- Use HTTPS in production

## Performance Tips

- Enable PHP OPcache
- Use database indexing
- Optimize file storage
- Implement caching where appropriate
- Monitor server resources

---

**Note**: This system is designed for internal organizational use. Ensure proper security measures are in place before deploying to production environments.