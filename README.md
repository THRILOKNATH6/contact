# File Management System

A comprehensive PHP-based file and record management system with three user levels, dynamic form creation, and commit-based workflow.

## Features

### User Hierarchy
- **IE (Industrial Engineer)**: Can create and edit their own records until committed
- **IE Incharge**: Can manage IE records under their supervision and commit records
- **IE Manager**: Full system access, user approval, form creation, and override capabilities

### Core Functionality
- **User Registration**: Requires manager approval for all new accounts
- **Dynamic Forms**: Managers can create custom input forms with various field types
- **Record Management**: Create, edit, and commit records with access control
- **File Upload**: Attach files to records with proper permissions
- **Commit System**: Once committed, records can only be edited by managers
- **Role-based Dashboards**: Different interfaces for each user level

### Technical Features
- Responsive Bootstrap 5 UI with modern design
- PHP 8+ with PDO for database operations
- MySQL/MariaDB database
- Session-based authentication
- File upload with type and size validation
- JSON-based dynamic form storage

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)

### Setup Instructions

1. **Clone or download the project files**
   ```bash
   git clone <repository-url>
   cd file-management-system
   ```

2. **Configure database connection**
   Edit `config/database.php` with your database credentials:
   ```php
   private $host = 'localhost';
   private $db_name = 'file_management';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

3. **Run the setup script**
   Navigate to the project root in your browser:
   ```
   http://your-domain/setup.php
   ```
   This will:
   - Create the database and tables
   - Create the uploads directory
   - Insert the default admin user

4. **Access the system**
   Navigate to:
   ```
   http://your-domain/public/login.php
   ```

### Default Admin Credentials
- **Username**: admin
- **Password**: password

**⚠️ Important**: Change the default password after first login!

## Project Structure

```
file-management-system/
├── config/
│   └── database.php          # Database configuration
├── classes/
│   ├── User.php             # User management
│   ├── Form.php             # Dynamic form handling
│   ├── Record.php           # Record management
│   └── FileManager.php      # File upload/download
├── includes/
│   └── auth.php             # Authentication functions
├── database/
│   └── schema.sql           # Database schema
├── public/                  # Web-accessible files
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   ├── dashboard.php       # Main dashboard
│   ├── manage_users.php    # User management (Manager only)
│   ├── manage_forms.php    # Form builder (Manager only)
│   ├── records.php         # Record management
│   ├── files.php           # File management
│   └── logout.php          # Logout functionality
├── uploads/                 # File storage directory
├── setup.php               # Database setup script
└── README.md              # This file
```

## User Workflow

### For IE Users
1. Register account (requires manager approval)
2. Login after approval
3. Create records using available forms
4. Upload files to records
5. Commit records when ready (cannot edit after commit)

### For IE Incharge Users
1. Register account (requires manager approval)
2. Login after approval
3. View and manage records from IEs under supervision
4. Create and commit own records
5. Access files from supervised IEs

### For IE Manager Users
1. Default admin account or approved registration
2. Approve/reject user registrations
3. Create and manage dynamic forms
4. View and edit all records (even committed ones)
5. Override record commits/uncommits
6. Full file access across the system

## Database Schema

### Users Table
- Stores user information with role hierarchy
- Links IEs to Incharges and Managers

### Forms Table
- Dynamic form definitions in JSON format
- Created and managed by managers

### Records Table
- Form submissions with commit status
- JSON data storage for flexibility

### Files Table
- File metadata with record associations
- Physical files stored in uploads directory

## Security Features

- Password hashing using PHP's password_hash()
- Session-based authentication
- Role-based access control
- SQL injection prevention with PDO prepared statements
- File type and size validation for uploads
- CSRF protection considerations

## Customization

### Adding New Field Types
Edit `classes/Form.php` in the `renderFormField()` method to add new input types.

### Modifying User Roles
Update the database schema and authentication logic to add new user levels.

### Changing File Upload Limits
Modify the `FileManager` class and PHP settings for upload size limits.

## Development Notes

- The system uses Bootstrap 5 for responsive design
- All user inputs are sanitized and validated
- Error handling is implemented throughout
- File uploads are restricted by type and size
- The commit system ensures data integrity

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL/MariaDB is running
   - Verify database exists

2. **File Upload Issues**
   - Check uploads directory permissions (777)
   - Verify PHP upload settings (upload_max_filesize, post_max_size)
   - Ensure web server has write access

3. **Permission Denied Errors**
   - Check file/directory permissions
   - Ensure web server user has appropriate access

### Log Files
Check your web server error logs for detailed error information.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

For support and questions, please open an issue in the repository or contact the development team.