<?php
/**
 * Casino Platform Installation Script
 * This script handles MySQL configuration and deployment for shared hosting
 * Compatible with Hostinger and other shared hosting providers
 */

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

class CasinoInstaller {
    private $config = [];
    private $errors = [];
    private $success = [];
    private $requirements = [
        'php_version' => '7.4.0',
        'required_extensions' => ['pdo', 'pdo_mysql', 'curl', 'json', 'mbstring', 'openssl', 'fileinfo'],
        'required_functions' => ['file_get_contents', 'file_put_contents', 'mkdir', 'chmod'],
        'writable_directories' => ['uploads', 'instance', 'static/uploads'],
        'config_files' => ['app.py', 'main.py', 'models.py']
    ];

    public function __construct() {
        $this->config = [
            'db_host' => $_POST['db_host'] ?? 'localhost',
            'db_name' => $_POST['db_name'] ?? '',
            'db_user' => $_POST['db_user'] ?? '',
            'db_pass' => $_POST['db_pass'] ?? '',
            'db_port' => $_POST['db_port'] ?? '3306',
            'admin_user' => $_POST['admin_user'] ?? 'admin',
            'admin_pass' => $_POST['admin_pass'] ?? '',
            'site_name' => $_POST['site_name'] ?? 'Casino Platform',
            'site_url' => $_POST['site_url'] ?? $this->getCurrentUrl()
        ];
    }

    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['REQUEST_URI']);
        return $protocol . '://' . $host . $path;
    }

    public function checkRequirements() {
        $this->checkPhpVersion();
        $this->checkExtensions();
        $this->checkFunctions();
        $this->checkDirectories();
        $this->checkFiles();
        return empty($this->errors);
    }

    private function checkPhpVersion() {
        if (version_compare(PHP_VERSION, $this->requirements['php_version'], '<')) {
            $this->errors[] = "PHP version {$this->requirements['php_version']} or higher required. Current: " . PHP_VERSION;
        } else {
            $this->success[] = "PHP version " . PHP_VERSION . " ✓";
        }
    }

    private function checkExtensions() {
        foreach ($this->requirements['required_extensions'] as $ext) {
            if (!extension_loaded($ext)) {
                $this->errors[] = "Required PHP extension missing: {$ext}";
            } else {
                $this->success[] = "PHP extension {$ext} ✓";
            }
        }
    }

    private function checkFunctions() {
        foreach ($this->requirements['required_functions'] as $func) {
            if (!function_exists($func)) {
                $this->errors[] = "Required PHP function disabled: {$func}";
            } else {
                $this->success[] = "PHP function {$func} ✓";
            }
        }
    }

    private function checkDirectories() {
        foreach ($this->requirements['writable_directories'] as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->errors[] = "Cannot create directory: {$dir}";
                    continue;
                }
            }

            if (!is_writable($dir)) {
                if (!chmod($dir, 0755)) {
                    $this->errors[] = "Directory not writable: {$dir}";
                } else {
                    $this->success[] = "Directory {$dir} writable ✓";
                }
            } else {
                $this->success[] = "Directory {$dir} writable ✓";
            }
        }
    }

    private function checkFiles() {
        foreach ($this->requirements['config_files'] as $file) {
            if (!file_exists($file)) {
                $this->errors[] = "Required file missing: {$file}";
            } else {
                $this->success[] = "File {$file} found ✓";
            }
        }
    }

    public function testDatabaseConnection() {
        try {
            $dsn = "mysql:host={$this->config['db_host']};port={$this->config['db_port']};dbname={$this->config['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->config['db_user'], $this->config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            $this->success[] = "Database connection successful ✓";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Database connection failed: " . $e->getMessage();
            return false;
        }
    }

    public function createDatabaseTables() {
        try {
            $dsn = "mysql:host={$this->config['db_host']};port={$this->config['db_port']};dbname={$this->config['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->config['db_user'], $this->config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Read and execute SQL schema
            $sql = $this->getDatabaseSchema();
            $pdo->exec($sql);
            
            $this->success[] = "Database tables created successfully ✓";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Failed to create database tables: " . $e->getMessage();
            return false;
        }
    }

    private function getDatabaseSchema() {
        return "
        CREATE TABLE IF NOT EXISTS `user` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `full_name` varchar(100) NOT NULL,
            `phone` varchar(20) NOT NULL UNIQUE,
            `username` varchar(50) UNIQUE,
            `password_hash` varchar(256) NOT NULL,
            `balance` decimal(10,2) DEFAULT 0.00,
            `bonus_balance` decimal(10,2) DEFAULT 0.00,
            `referral_code` varchar(10) NOT NULL UNIQUE,
            `referred_by` int(11),
            `referral_commission` decimal(10,2) DEFAULT 0.00,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `last_login` timestamp NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`referred_by`) REFERENCES `user`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `admin` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL UNIQUE,
            `password_hash` varchar(256) NOT NULL,
            `role` varchar(20) DEFAULT 'admin',
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `last_login` timestamp NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `game` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(100) NOT NULL,
            `category` varchar(50) NOT NULL,
            `thumbnail` varchar(255),
            `game_file` varchar(255),
            `winning_percentage` decimal(5,2) DEFAULT 50.00,
            `min_bet` decimal(10,2) DEFAULT 1.00,
            `max_bet` decimal(10,2) DEFAULT 1000.00,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `payment_method` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `account_number` varchar(50) NOT NULL,
            `instructions` text,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `deposit_request` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `payment_method_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `transaction_id` varchar(100),
            `screenshot` varchar(255),
            `status` varchar(20) DEFAULT 'pending',
            `admin_notes` text,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `processed_at` timestamp NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `user`(`id`),
            FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `withdrawal_request` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `payment_method_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `account_details` text NOT NULL,
            `status` varchar(20) DEFAULT 'pending',
            `admin_notes` text,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `processed_at` timestamp NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `user`(`id`),
            FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `homepage_slider` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `title` varchar(100) NOT NULL,
            `description` text,
            `image_path` varchar(255) NOT NULL,
            `link_url` varchar(255),
            `order_position` int(11) DEFAULT 0,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `site_settings` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `site_name` varchar(100) DEFAULT 'Casino Platform',
            `site_description` text,
            `contact_email` varchar(100),
            `contact_phone` varchar(20),
            `maintenance_mode` tinyint(1) DEFAULT 0,
            `referral_bonus_percentage` decimal(5,2) DEFAULT 5.00,
            `min_deposit` decimal(10,2) DEFAULT 10.00,
            `min_withdrawal` decimal(10,2) DEFAULT 20.00,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `transaction` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `type` varchar(20) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `description` varchar(255),
            `reference_id` varchar(100),
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`user_id`) REFERENCES `user`(`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
    }

    public function createConfigFile() {
        $databaseUrl = "mysql+pymysql://{$this->config['db_user']}:{$this->config['db_pass']}@{$this->config['db_host']}:{$this->config['db_port']}/{$this->config['db_name']}?charset=utf8mb4";
        
        $configContent = "# Casino Platform Configuration
# Generated by install.php on " . date('Y-m-d H:i:s') . "

# Database Configuration
DATABASE_URL={$databaseUrl}

# Session Configuration
SESSION_SECRET=" . bin2hex(random_bytes(32)) . "

# Site Configuration
SITE_NAME={$this->config['site_name']}
SITE_URL={$this->config['site_url']}

# Admin Configuration
DEFAULT_ADMIN_USER={$this->config['admin_user']}
DEFAULT_ADMIN_PASS={$this->config['admin_pass']}
";

        if (file_put_contents('.env', $configContent)) {
            $this->success[] = "Configuration file created ✓";
            return true;
        } else {
            $this->errors[] = "Failed to create configuration file";
            return false;
        }
    }

    public function createHtaccess() {
        $htaccessContent = "# Casino Platform .htaccess
# Redirect all requests to Python application

RewriteEngine On

# Security headers
Header always set X-Frame-Options DENY
Header always set X-Content-Type-Options nosniff
Header always set X-XSS-Protection \"1; mode=block\"
Header always set Strict-Transport-Security \"max-age=31536000; includeSubDomains\"

# Hide sensitive files
<Files ~ \"^\.env$\">
    Order allow,deny
    Deny from all
</Files>

<Files ~ \"^\.py$\">
    Order allow,deny
    Deny from all
</Files>

# Allow uploads directory
<Directory \"uploads\">
    Options -Indexes
    AllowOverride None
    Order allow,deny
    Allow from all
</Directory>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css \"access plus 1 month\"
    ExpiresByType application/javascript \"access plus 1 month\"
    ExpiresByType image/png \"access plus 1 month\"
    ExpiresByType image/jpg \"access plus 1 month\"
    ExpiresByType image/jpeg \"access plus 1 month\"
    ExpiresByType image/gif \"access plus 1 month\"
</IfModule>
";

        if (file_put_contents('.htaccess', $htaccessContent)) {
            $this->success[] = ".htaccess file created ✓";
            return true;
        } else {
            $this->errors[] = "Failed to create .htaccess file";
            return false;
        }
    }

    public function insertDefaultData() {
        try {
            $dsn = "mysql:host={$this->config['db_host']};port={$this->config['db_port']};dbname={$this->config['db_name']};charset=utf8mb4";
            $pdo = new PDO($dsn, $this->config['db_user'], $this->config['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // Insert default admin user
            $adminHash = password_hash($this->config['admin_pass'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT IGNORE INTO admin (username, password_hash, role) VALUES (?, ?, 'super_admin')");
            $stmt->execute([$this->config['admin_user'], $adminHash]);

            // Insert default site settings
            $stmt = $pdo->prepare("INSERT IGNORE INTO site_settings (site_name, site_description) VALUES (?, 'Professional Casino Platform')");
            $stmt->execute([$this->config['site_name']]);

            // Insert default payment methods
            $paymentMethods = [
                ['bKash', '+880123456789', 'Send money to this bKash number and provide transaction ID'],
                ['Nagad', '+880123456789', 'Send money to this Nagad number and provide transaction ID'],
                ['Rocket', '+880123456789', 'Send money to this Rocket number and provide transaction ID']
            ];

            $stmt = $pdo->prepare("INSERT IGNORE INTO payment_method (name, account_number, instructions) VALUES (?, ?, ?)");
            foreach ($paymentMethods as $method) {
                $stmt->execute($method);
            }

            $this->success[] = "Default data inserted successfully ✓";
            return true;
        } catch (PDOException $e) {
            $this->errors[] = "Failed to insert default data: " . $e->getMessage();
            return false;
        }
    }

    public function finalizeInstallation() {
        // Create installation completion marker
        $installInfo = [
            'installed_at' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'database' => $this->config['db_name'],
            'admin_user' => $this->config['admin_user']
        ];

        if (file_put_contents('install.lock', json_encode($installInfo))) {
            $this->success[] = "Installation completed successfully ✓";
            return true;
        } else {
            $this->errors[] = "Failed to create installation lock file";
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getSuccess() {
        return $this->success;
    }

    public function isInstalled() {
        return file_exists('install.lock');
    }
}

// Handle installation process
$installer = new CasinoInstaller();
$step = $_GET['step'] ?? 'requirements';

if ($installer->isInstalled() && $step !== 'complete') {
    header('Location: ?step=complete');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 'database':
            if ($installer->testDatabaseConnection()) {
                header('Location: ?step=install');
                exit;
            }
            break;
        case 'install':
            $success = true;
            $success &= $installer->createDatabaseTables();
            $success &= $installer->createConfigFile();
            $success &= $installer->createHtaccess();
            $success &= $installer->insertDefaultData();
            
            if ($success) {
                $installer->finalizeInstallation();
                header('Location: ?step=complete');
                exit;
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Casino Platform Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            min-height: 100vh;
        }
        .install-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 0 20px;
        }
        .install-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 215, 0, 0.3);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #ffd700;
        }
        .step.active {
            background: #ffd700;
            color: #000;
        }
        .step.completed {
            background: #28a745;
            border-color: #28a745;
        }
        .btn-casino {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            border: none;
            color: #000;
            font-weight: bold;
        }
        .btn-casino:hover {
            background: linear-gradient(45deg, #ffed4e, #ffd700);
            color: #000;
        }
        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid #28a745;
            color: #28a745;
        }
        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #fff;
        }
        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffd700;
            color: #fff;
            box-shadow: 0 0 0 0.2rem rgba(255, 215, 0, 0.25);
        }
        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        .form-label {
            color: #ffd700;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="text-center mb-4">
            <h1><i class="fas fa-dice"></i> Casino Platform</h1>
            <p class="lead">Installation Wizard</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step <?= $step === 'requirements' ? 'active' : ($step !== 'requirements' ? 'completed' : '') ?>">1</div>
            <div class="step <?= $step === 'database' ? 'active' : (in_array($step, ['install', 'complete']) ? 'completed' : '') ?>">2</div>
            <div class="step <?= $step === 'install' ? 'active' : ($step === 'complete' ? 'completed' : '') ?>">3</div>
            <div class="step <?= $step === 'complete' ? 'active' : '' ?>">4</div>
        </div>

        <div class="card install-card">
            <div class="card-body p-4">
                <?php if ($step === 'requirements'): ?>
                    <?php $requirementsPassed = $installer->checkRequirements(); ?>
                    <h3><i class="fas fa-check-circle"></i> System Requirements</h3>
                    <p class="mb-4">Checking your server compatibility...</p>

                    <?php if (!empty($installer->getSuccess())): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check"></i> Requirements Met:</h6>
                            <ul class="mb-0">
                                <?php foreach ($installer->getSuccess() as $success): ?>
                                    <li><?= htmlspecialchars($success) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($installer->getErrors())): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Issues Found:</h6>
                            <ul class="mb-0">
                                <?php foreach ($installer->getErrors() as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($requirementsPassed): ?>
                        <div class="text-center mt-4">
                            <a href="?step=database" class="btn btn-casino btn-lg">
                                <i class="fas fa-arrow-right"></i> Continue to Database Setup
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <button class="btn btn-secondary btn-lg" disabled>
                                <i class="fas fa-times"></i> Please Fix Issues First
                            </button>
                        </div>
                    <?php endif; ?>

                <?php elseif ($step === 'database'): ?>
                    <h3><i class="fas fa-database"></i> Database Configuration</h3>
                    <p class="mb-4">Enter your MySQL database details:</p>

                    <?php if (!empty($installer->getErrors())): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Database Connection Error:</h6>
                            <ul class="mb-0">
                                <?php foreach ($installer->getErrors() as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Host</label>
                                <input type="text" class="form-control" name="db_host" value="<?= htmlspecialchars($installer->config['db_host']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Port</label>
                                <input type="text" class="form-control" name="db_port" value="<?= htmlspecialchars($installer->config['db_port']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Name</label>
                                <input type="text" class="form-control" name="db_name" value="<?= htmlspecialchars($installer->config['db_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Username</label>
                                <input type="text" class="form-control" name="db_user" value="<?= htmlspecialchars($installer->config['db_user']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Database Password</label>
                                <input type="password" class="form-control" name="db_pass" value="<?= htmlspecialchars($installer->config['db_pass']) ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Site Name</label>
                                <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($installer->config['site_name']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admin Username</label>
                                <input type="text" class="form-control" name="admin_user" value="<?= htmlspecialchars($installer->config['admin_user']) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admin Password</label>
                                <input type="password" class="form-control" name="admin_pass" value="<?= htmlspecialchars($installer->config['admin_pass']) ?>" required>
                            </div>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-casino btn-lg">
                                <i class="fas fa-check"></i> Test Database Connection
                            </button>
                        </div>
                    </form>

                <?php elseif ($step === 'install'): ?>
                    <h3><i class="fas fa-cogs"></i> Installing Casino Platform</h3>
                    <p class="mb-4">Setting up your casino platform...</p>

                    <?php if (!empty($installer->getSuccess())): ?>
                        <div class="alert alert-success">
                            <h6><i class="fas fa-check"></i> Installation Progress:</h6>
                            <ul class="mb-0">
                                <?php foreach ($installer->getSuccess() as $success): ?>
                                    <li><?= htmlspecialchars($success) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($installer->getErrors())): ?>
                        <div class="alert alert-danger">
                            <h6><i class="fas fa-exclamation-triangle"></i> Installation Errors:</h6>
                            <ul class="mb-0">
                                <?php foreach ($installer->getErrors() as $error): ?>
                                    <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="text-center">
                            <a href="?step=database" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Database Setup
                            </a>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="text-center">
                                <button type="submit" class="btn btn-casino btn-lg">
                                    <i class="fas fa-rocket"></i> Complete Installation
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                <?php elseif ($step === 'complete'): ?>
                    <div class="text-center">
                        <h3><i class="fas fa-check-circle text-success"></i> Installation Complete!</h3>
                        <p class="lead mb-4">Your casino platform is now ready to use.</p>
                        
                        <div class="alert alert-success text-start">
                            <h6><i class="fas fa-info-circle"></i> Next Steps:</h6>
                            <ol>
                                <li>Delete this install.php file for security</li>
                                <li>Access admin panel: <a href="/admin" target="_blank"><?= $installer->config['site_url'] ?>/admin</a></li>
                                <li>Login with: <?= htmlspecialchars($installer->config['admin_user']) ?> / [your password]</li>
                                <li>Configure payment methods and add games</li>
                                <li>Test user registration and functionality</li>
                            </ol>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <a href="/" class="btn btn-casino btn-lg me-md-2">
                                <i class="fas fa-home"></i> Visit Website
                            </a>
                            <a href="/admin" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-user-cog"></i> Admin Panel
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <small class="text-muted">Casino Platform Installation Wizard v1.0</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>