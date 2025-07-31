# MySQL Shared Hosting Deployment Guide

## Overview
This casino platform is now configured to work with MySQL databases, making it perfect for shared hosting deployment.

## Required Dependencies
The following Python packages are required (already installed in this project):
- Flask==3.0.0
- Flask-SQLAlchemy==3.1.1
- PyMySQL==1.1.1
- mysql-connector-python==9.4.0
- Werkzeug==3.0.1
- gunicorn==23.0.0

## MySQL Configuration

### Step 1: Database Setup
1. Create a MySQL database in your shared hosting control panel
2. Note down your MySQL credentials:
   - Host (usually localhost)
   - Username
   - Password
   - Database name
   - Port (usually 3306)

### Step 2: Update Configuration
Edit `mysql_config.py` with your actual MySQL credentials:

```python
MYSQL_CONFIG = {
    'host': 'localhost',                    # Your MySQL host
    'user': 'your_actual_username',         # Your MySQL username
    'password': 'your_actual_password',     # Your MySQL password
    'database': 'your_actual_database',     # Your database name
    'port': 3306,                          # MySQL port
    'charset': 'utf8mb4'
}
```

### Step 3: Environment Variable
Set the DATABASE_URL environment variable in your shared hosting:
```
DATABASE_URL=mysql+pymysql://username:password@localhost:3306/database_name?charset=utf8mb4
```

## File Structure for Upload
Upload these files to your shared hosting:

```
├── app.py                    # Main application file
├── main.py                   # WSGI entry point
├── models.py                 # Database models
├── mysql_config.py           # MySQL configuration
├── routes/                   # Route handlers
│   ├── __init__.py
│   ├── auth.py
│   ├── user.py
│   ├── admin.py
│   └── main.py
├── templates/                # HTML templates
├── static/                   # CSS, JS, images
└── uploads/                  # File upload directory
```

## Shared Hosting Compatibility Features

✅ **SQLite Fallback**: If MySQL isn't available, falls back to SQLite
✅ **PyMySQL Driver**: Uses PyMySQL for broad compatibility
✅ **Connection Pooling**: Optimized for shared hosting limitations
✅ **File Uploads**: Local file system storage
✅ **Session Management**: File-based sessions (no Redis required)
✅ **No Complex Dependencies**: Pure Python, no Node.js required

## Default Admin Access
- URL: `/admin`
- Username: `admin`
- Password: `admin`
- **Important**: Change these credentials after first login!

## Security Considerations
1. Change default admin credentials immediately
2. Set a secure SESSION_SECRET environment variable
3. Use HTTPS in production
4. Regularly backup your database
5. Keep file upload directories secure

## Troubleshooting

### Common Issues:
1. **Database Connection Error**: Check DATABASE_URL format
2. **Import Errors**: Ensure all dependencies are installed
3. **File Upload Issues**: Check uploads/ directory permissions
4. **Session Issues**: Set SESSION_SECRET environment variable

### MySQL Connection String Examples:
```bash
# Standard MySQL
mysql+pymysql://user:pass@localhost/dbname

# MySQL with custom port
mysql+pymysql://user:pass@localhost:3307/dbname

# MySQL with additional parameters
mysql+pymysql://user:pass@localhost/dbname?charset=utf8mb4&autocommit=true
```

## Testing the Deployment
1. Upload all files to your shared hosting
2. Set environment variables (DATABASE_URL, SESSION_SECRET)
3. Access your domain - should show the casino homepage
4. Test admin login at `/admin`
5. Register a test user account
6. Upload a test game in admin panel

Your casino platform is now ready for production use on shared hosting!