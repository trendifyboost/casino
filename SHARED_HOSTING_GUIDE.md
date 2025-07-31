# Complete Shared Hosting Deployment Guide

## Overview
This guide will help you deploy the Casino Platform to shared hosting providers like Hostinger, cPanel hosting, or similar services.

## Prerequisites
- Shared hosting account with PHP 7.4+ support
- MySQL database access
- File manager or FTP access
- Basic understanding of hosting control panels

## Step 1: Prepare Your Hosting Environment

### 1.1 Check Hosting Requirements
Ensure your hosting provider supports:
- ✅ PHP 7.4 or higher
- ✅ MySQL 5.7 or higher
- ✅ PHP Extensions: PDO, mysqli, curl, json, mbstring, openssl, fileinfo
- ✅ File upload capabilities
- ✅ Directory creation permissions

### 1.2 Create MySQL Database
1. Login to your hosting control panel (cPanel/hPanel)
2. Navigate to "MySQL Databases" or "Database Manager"
3. Create a new database (e.g., `your_account_casino`)
4. Create a database user with full privileges
5. Note down: hostname, database name, username, password

## Step 2: Upload Files

### 2.1 Download Project Files
Download all project files including:
```
├── install.php              # Installation script
├── app.py                   # Main application
├── main.py                  # WSGI entry point
├── models.py                # Database models
├── mysql_config.py          # MySQL configuration
├── routes/                  # Route handlers
├── templates/               # HTML templates
├── static/                  # CSS, JS, images
└── uploads/                 # File upload directory
```

### 2.2 Upload to Hosting
1. Access File Manager or use FTP client
2. Upload all files to your domain's public folder (usually `public_html` or `www`)
3. Ensure folder structure is preserved
4. Set permissions:
   - Files: 644
   - Directories: 755
   - uploads/ directory: 755 (writable)

## Step 3: Run Installation

### 3.1 Access Installation Script
1. Open your browser
2. Navigate to: `https://yourdomain.com/install.php`
3. Follow the installation wizard

### 3.2 Installation Steps

#### Step 1: System Requirements
- The installer will check PHP version, extensions, and permissions
- Fix any issues shown before proceeding

#### Step 2: Database Configuration
Enter your MySQL details:
- **Database Host**: Usually `localhost`
- **Database Port**: Usually `3306`
- **Database Name**: Your created database name
- **Username**: Database username
- **Password**: Database password
- **Site Name**: Your casino name
- **Admin Username**: Admin login username
- **Admin Password**: Strong password for admin

#### Step 3: Installation Process
- Creates database tables automatically
- Sets up configuration files
- Inserts default data
- Creates security files

#### Step 4: Completion
- Installation complete message
- Links to your website and admin panel

### 3.3 Post-Installation Security
1. **Delete install.php**: Remove the installation file
   ```bash
   rm install.php
   ```
2. **Secure uploads directory**: Ensure no PHP execution
3. **Change default passwords**: Login and update admin credentials

## Step 4: Configure for Shared Hosting

### 4.1 Python Application Setup
Since this is a Flask Python application, you need to configure it for your hosting:

#### Option A: Hostinger (Python Support)
1. Enable Python application in hosting panel
2. Set Python version to 3.8+
3. Upload Python files to application directory
4. Configure startup file as `main.py`

#### Option B: Traditional Shared Hosting (PHP Alternative)
If your hosting doesn't support Python:
1. The install.php creates a complete PHP frontend
2. Database tables are ready for PHP application
3. Consider converting key features to PHP or use subdomain

### 4.2 Environment Variables
Set these in your hosting control panel:
```
DATABASE_URL=mysql+pymysql://user:pass@localhost/dbname
SESSION_SECRET=your_random_secret_key
UPLOAD_FOLDER=/path/to/uploads
```

### 4.3 URL Rewriting
The installer creates `.htaccess` for:
- Security headers
- File protection
- Static file caching
- Clean URLs

## Step 5: Testing Your Installation

### 5.1 Frontend Testing
1. Visit your domain homepage
2. Check responsive design on mobile/desktop
3. Test user registration
4. Test login functionality

### 5.2 Admin Panel Testing
1. Access `/admin` route
2. Login with your admin credentials
3. Test game management
4. Test payment method setup
5. Test user management

### 5.3 File Upload Testing
1. Upload game thumbnails
2. Upload homepage slider images
3. Test payment screenshots upload

## Step 6: Go Live Checklist

### 6.1 Security Configuration
- [ ] Changed default admin password
- [ ] Removed install.php file
- [ ] Set secure session secret
- [ ] Configured HTTPS (SSL certificate)
- [ ] Protected sensitive directories

### 6.2 Content Setup
- [ ] Added payment methods (bKash, Nagad, etc.)
- [ ] Uploaded homepage slider images
- [ ] Added initial games to catalog
- [ ] Configured site settings
- [ ] Set minimum deposit/withdrawal amounts

### 6.3 Testing
- [ ] User registration works
- [ ] Admin panel accessible
- [ ] File uploads functional
- [ ] Responsive design verified
- [ ] Payment process tested

## Troubleshooting

### Common Issues

#### Database Connection Errors
```
SQLSTATE[HY000] [1045] Access denied for user
```
**Solution**: Check database credentials in configuration

#### File Permission Errors
```
Permission denied when uploading files
```
**Solution**: Set uploads directory to 755 permissions

#### PHP Extension Missing
```
Class 'PDO' not found
```
**Solution**: Contact hosting provider to enable PDO extension

#### Memory Limit Errors
```
Fatal error: Allowed memory size exhausted
```
**Solution**: Increase PHP memory limit in hosting panel

### Hosting-Specific Guides

#### Hostinger
1. Use File Manager for uploads
2. Database hostname is usually `localhost`
3. Enable Python app in hosting panel
4. Set document root to public folder

#### cPanel Hosting
1. Use File Manager or FTP
2. Database created in MySQL section
3. PHP version set in MultiPHP Manager
4. Check error logs in Error Logs section

#### SiteGround
1. Upload via Site Tools > File Manager
2. Database in MySQL > Databases
3. PHP version in PHP Manager
4. SSL available in Security section

## Support
- Check error logs in hosting control panel
- Contact hosting provider for server-specific issues
- Ensure all file permissions are correct
- Verify database connection settings

Your casino platform is now ready for production use on shared hosting!