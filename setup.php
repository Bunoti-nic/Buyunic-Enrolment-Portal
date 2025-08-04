<?php
/**
 * BUYUNIC Enrollment Portal Setup Script
 * This script initializes the database and checks system requirements
 */

// Prevent direct access in production
$setupMode = true;

// Check if setup is already completed
$setupCompleteFile = 'setup_complete.txt';
if (file_exists($setupCompleteFile) && !isset($_GET['force'])) {
    die('Setup has already been completed. If you need to run setup again, add ?force=1 to the URL.');
}

$errors = [];
$warnings = [];
$success = [];

// System Requirements Check
function checkSystemRequirements() {
    global $errors, $warnings, $success;
    
    // PHP Version
    if (version_compare(PHP_VERSION, '7.4.0') >= 0) {
        $success[] = "PHP Version: " . PHP_VERSION . " ✓";
    } else {
        $errors[] = "PHP 7.4 or higher is required. Current version: " . PHP_VERSION;
    }
    
    // Required Extensions
    $requiredExtensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'curl', 'gd'];
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            $success[] = "Extension {$ext}: Available ✓";
        } else {
            $errors[] = "Required PHP extension '{$ext}' is not available";
        }
    }
    
    // Directory Permissions
    $directories = ['uploads', 'vendor'];
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                $success[] = "Directory {$dir}: Created ✓";
            } else {
                $errors[] = "Cannot create directory '{$dir}'";
            }
        } elseif (is_writable($dir)) {
            $success[] = "Directory {$dir}: Writable ✓";
        } else {
            $errors[] = "Directory '{$dir}' is not writable";
        }
    }
    
    // Check if .htaccess exists for URL rewriting
    if (!file_exists('.htaccess')) {
        $warnings[] = ".htaccess file not found. URL rewriting may not work properly.";
    }
}

// Database Setup
function setupDatabase() {
    global $errors, $success;
    
    try {
        // Database configuration
        $host = 'localhost';
        $dbname = 'buyunic_enrollment';
        $username = 'root';
        $password = '';
        
        // Connect to MySQL server (without database)
        $pdo = new PDO("mysql:host={$host}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set SQL mode to handle timestamps properly
        $pdo->exec("SET sql_mode = ''");
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS {$dbname} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $success[] = "Database '{$dbname}' created or already exists ✓";
        
        // Connect to the specific database
        $pdo = new PDO("mysql:host={$host};dbname={$dbname}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Set SQL mode again for the database connection
        $pdo->exec("SET sql_mode = ''");
        
        // Read and execute SQL schema
        $sqlFile = 'database/schema.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            
            // Split SQL file into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^(CREATE DATABASE|USE)/i', $statement)) {
                    $pdo->exec($statement);
                }
            }
            
            $success[] = "Database schema created successfully ✓";
        } else {
            $errors[] = "SQL schema file not found: {$sqlFile}";
        }
        
        // Verify tables were created
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $expectedTables = [
            'users', 'email_verification_tokens', 'password_reset_tokens', 'otp_tokens',
            'training_programs', 'applications', 'personal_details', 'next_of_kin',
            'academic_background', 'internee_information', 'program_selections',
            'document_uploads', 'payments', 'user_sessions', 'notifications',
            'messages', 'admin_logs', 'pdf_generations', 'consents',
            'qr_verifications', 'system_settings'
        ];
        
        $missingTables = array_diff($expectedTables, $tables);
        if (empty($missingTables)) {
            $success[] = "All required tables created successfully ✓";
        } else {
            $errors[] = "Missing tables: " . implode(', ', $missingTables);
        }
        
        // Check if default data exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM training_programs");
        $programCount = $stmt->fetchColumn();
        
        if ($programCount > 0) {
            $success[] = "Default training programs loaded ✓";
        } else {
            $warnings[] = "No training programs found. Default data may not have been loaded.";
        }
        
        // Check if admin user exists
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount > 0) {
            $success[] = "Default admin user exists ✓";
        } else {
            $warnings[] = "No admin user found. You should create an admin account.";
        }
        
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
}

// Create configuration files
function createConfigFiles() {
    global $errors, $success, $warnings;
    
    // Create .htaccess file for URL rewriting and security
    $htaccessContent = '
# BUYUNIC Enrollment Portal - Apache Configuration

# Disable directory browsing
Options -Indexes

# Enable URL rewriting
RewriteEngine On

# Force HTTPS (uncomment in production)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# Protect sensitive files
<Files ~ "^\.ht">
    Order allow,deny
    Deny from all
    Satisfy all
</Files>

<Files ~ "(\.sql|\.log|setup\.php|composer\.(json|lock))$">
    Order allow,deny
    Deny from all
    Satisfy all
</Files>

# Protect uploads directory from script execution
<Directory "uploads">
    Options -ExecCGI
    AddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi
</Directory>

# Cache static files
<IfModule mod_expires.c>
    ExpiresActive on
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/svg+xml "access plus 1 month"
</IfModule>

# Compress files
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
';
    
    if (file_put_contents('.htaccess', $htaccessContent)) {
        $success[] = ".htaccess file created ✓";
    } else {
        $warnings[] = "Could not create .htaccess file. You may need to create it manually.";
    }
    
    // Create robots.txt for SEO
    $robotsContent = "User-agent: *\nDisallow: /admin/\nDisallow: /auth/\nDisallow: /uploads/\nDisallow: /vendor/\nDisallow: /database/\nDisallow: /includes/\nDisallow: /classes/\n";
    
    if (file_put_contents('robots.txt', $robotsContent)) {
        $success[] = "robots.txt file created ✓";
    } else {
        $warnings[] = "Could not create robots.txt file.";
    }
}

// Process setup if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_setup'])) {
    checkSystemRequirements();
    
    if (empty($errors)) {
        setupDatabase();
        createConfigFiles();
        
        // Mark setup as complete
        if (empty($errors)) {
            file_put_contents($setupCompleteFile, date('Y-m-d H:i:s') . " - Setup completed successfully\n");
            $success[] = "Setup completed successfully! You can now use the enrollment portal.";
        }
    }
} else {
    // Initial requirements check
    checkSystemRequirements();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUYUNIC Enrollment Portal - Setup</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .setup-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .setup-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: white;
            border-radius: 1rem;
        }
        
        .check-section {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .check-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .check-item:last-child {
            border-bottom: none;
        }
        
        .success { color: #059669; }
        .warning { color: #d97706; }
        .error { color: #dc2626; }
        
        .setup-actions {
            text-align: center;
            margin-top: 2rem;
        }
        
        .config-note {
            background: #fffbeb;
            border: 1px solid #f59e0b;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="setup-header">
            <h1><i class="fas fa-graduation-cap"></i> BUYUNIC Enrollment Portal</h1>
            <p>System Setup & Configuration</p>
        </div>
        
        <div class="check-section">
            <h2><i class="fas fa-cogs"></i> System Requirements</h2>
            
            <?php if (!empty($success)): ?>
                <div class="success-checks">
                    <?php foreach ($success as $item): ?>
                        <div class="check-item success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($item); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($warnings)): ?>
                <div class="warning-checks">
                    <?php foreach ($warnings as $item): ?>
                        <div class="check-item warning">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($item); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-checks">
                    <?php foreach ($errors as $item): ?>
                        <div class="check-item error">
                            <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($item); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!file_exists($setupCompleteFile)): ?>
            <div class="config-note">
                <h3><i class="fas fa-info-circle"></i> Before Running Setup</h3>
                <p>Please ensure you have:</p>
                <ul>
                    <li>MySQL/MariaDB server running</li>
                    <li>Database credentials configured in <code>config/database.php</code></li>
                    <li>SMTP settings configured for email functionality</li>
                    <li>Proper file permissions set on the uploads directory</li>
                </ul>
                <p><strong>Note:</strong> The default admin login is: <code>admin@buyunic.ug</code> / <code>password</code></p>
            </div>
            
            <div class="setup-actions">
                <?php if (empty($errors)): ?>
                    <form method="POST">
                        <button type="submit" name="run_setup" class="btn btn-primary btn-large">
                            <i class="fas fa-rocket"></i> Run Setup
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-error">
                        <strong>Cannot proceed with setup.</strong><br>
                        Please fix the errors above before running the setup.
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="check-section">
                <div class="alert alert-success">
                    <h3><i class="fas fa-check-circle"></i> Setup Complete!</h3>
                    <p>The BUYUNIC Enrollment Portal has been successfully set up.</p>
                    <div style="margin-top: 1rem;">
                        <a href="index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                        <a href="admin/login.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-shield-alt"></i> Admin Login
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="check-section">
            <h3><i class="fas fa-book"></i> Next Steps</h3>
            <ol>
                <li><strong>Security:</strong> Change the default admin password immediately</li>
                <li><strong>Email:</strong> Configure SMTP settings in <code>config/database.php</code></li>
                <li><strong>Payments:</strong> Set up payment gateway credentials</li>
                <li><strong>SSL:</strong> Enable HTTPS for production use</li>
                <li><strong>Backup:</strong> Set up regular database backups</li>
                <li><strong>Remove:</strong> Delete or protect the <code>setup.php</code> file</li>
            </ol>
        </div>
        
        <div style="text-align: center; margin-top: 2rem; color: #6b7280;">
            <p>BUYUNIC Enrollment Portal v1.0</p>
            <p>For support, contact: <a href="mailto:info@buyunic.ug">info@buyunic.ug</a></p>
        </div>
    </div>
</body>
</html>