<?php
/**
 * Installation Script for Support Log Manager
 * 
 * This script sets up the database and initial configuration
 * for the WordPress/WooCommerce Plugin Support Log Manager
 */

// Prevent direct access if already installed
if (file_exists('config/installed.lock')) {
    die('Application is already installed. If you need to reinstall, please delete the config/installed.lock file.');
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$step = $_GET['step'] ?? 1;
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Database connection test
            if (testDatabaseConnection()) {
                header('Location: install.php?step=2');
                exit;
            } else {
                $error = 'Database connection failed. Please check your configuration.';
            }
            break;
            
        case 2:
            // Create database and tables
            if (setupDatabase()) {
                header('Location: install.php?step=3');
                exit;
            } else {
                $error = 'Database setup failed. Please check permissions and try again.';
            }
            break;
            
        case 3:
            // Create initial admin user and complete installation
            if (completeInstallation()) {
                header('Location: install.php?step=4');
                exit;
            } else {
                $error = 'Installation completion failed. Please try again.';
            }
            break;
    }
}

function testDatabaseConnection() {
    try {
        // First check if we can connect to MySQL server
        $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Test creating the database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
        
        // Test connecting to the specific database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function setupDatabase() {
    try {
        // Ensure database exists
        if (!checkAndCreateDatabase()) {
            return false;
        }
        
        // Initialize database tables
        initializeDatabase();
        
        // Insert sample data for demonstration (optional)
        insertSampleData();
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function insertSampleData() {
    $pdo = getDatabase();
    
    // Sample log entries for demonstration
    $sampleLogs = [
        [
            'client_ref' => 'WP-2024-001',
            'plugin_name' => 'PDF Invoice',
            'plugin_version' => '2.3.1',
            'wp_version' => '6.4.2',
            'wc_version' => '8.5.1',
            'issue_category' => 'Template Issue',
            'issue_summary' => 'Invoice template not displaying custom fields correctly',
            'detailed_description' => 'Customer reported that custom checkout fields are not appearing in the PDF invoice template. The fields are visible in the admin but missing from generated PDFs.',
            'steps_reproduce' => '1. Add custom checkout fields\n2. Complete a test order\n3. Generate PDF invoice\n4. Notice missing custom field data',
            'errors_logs' => 'No specific error logs, but custom fields array appears empty in PDF generation',
            'troubleshooting_steps' => 'Checked template files, verified custom field settings, tested with default template',
            'resolution' => 'Updated template hook priority to ensure custom fields are available during PDF generation',
            'time_spent' => 45,
            'escalated' => 0,
            'status' => 'Resolved',
            'recurring' => 0
        ],
        [
            'client_ref' => 'WC-2024-002',
            'plugin_name' => 'Product Feed',
            'plugin_version' => '1.8.3',
            'wp_version' => '6.4.2',
            'wc_version' => '8.5.1',
            'issue_category' => 'Performance',
            'issue_summary' => 'Feed generation timing out for large catalogs',
            'detailed_description' => 'Client with 5000+ products experiencing timeouts during feed generation process.',
            'steps_reproduce' => '1. Navigate to feed settings\n2. Generate feed for all products\n3. Process times out after 30 seconds',
            'errors_logs' => 'PHP Fatal error: Maximum execution time of 30 seconds exceeded',
            'troubleshooting_steps' => 'Increased PHP max_execution_time, tested with smaller batches',
            'resolution' => '',
            'time_spent' => 60,
            'escalated' => 1,
            'status' => 'Escalated',
            'recurring' => 0
        ]
    ];
    
    $sql = "INSERT INTO support_logs (
        client_ref, plugin_name, plugin_version, wp_version, wc_version,
        issue_category, issue_summary, detailed_description, steps_reproduce,
        errors_logs, troubleshooting_steps, resolution, time_spent,
        escalated, status, recurring
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($sampleLogs as $log) {
        $stmt->execute([
            $log['client_ref'], $log['plugin_name'], $log['plugin_version'],
            $log['wp_version'], $log['wc_version'], $log['issue_category'],
            $log['issue_summary'], $log['detailed_description'], $log['steps_reproduce'],
            $log['errors_logs'], $log['troubleshooting_steps'], $log['resolution'],
            $log['time_spent'], $log['escalated'], $log['status'], $log['recurring']
        ]);
    }
}

function completeInstallation() {
    try {
        // Create required directories
        $directories = ['uploads', 'exports'];
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        
        // Create installation lock file
        file_put_contents('config/installed.lock', date('Y-m-d H:i:s'));
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Support Log Manager</title>
    
    <!-- Material Design 3 -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <style>
        :root {
            --md-sys-color-primary: #6750a4;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #eaddff;
            --md-sys-color-on-primary-container: #21005d;
            --md-sys-color-surface: #fffbfe;
            --md-sys-color-on-surface: #1c1b1f;
            --md-sys-color-surface-variant: #e7e0ec;
            --md-sys-color-on-surface-variant: #49454f;
            --md-sys-color-error: #ba1a1a;
            --md-sys-color-outline-variant: #cab6cf;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 24px;
            background-color: var(--md-sys-color-surface);
            color: var(--md-sys-color-on-surface);
        }

        .install-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: var(--md-sys-color-surface);
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .install-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .install-title {
            font-size: 2rem;
            font-weight: 500;
            color: var(--md-sys-color-primary);
            margin: 0 0 8px 0;
        }

        .install-subtitle {
            color: var(--md-sys-color-on-surface-variant);
            margin: 0;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
            padding: 0 16px;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 16px;
            left: 60%;
            right: -40%;
            height: 2px;
            background-color: var(--md-sys-color-outline-variant);
        }

        .step.completed::after {
            background-color: var(--md-sys-color-primary);
        }

        .step-number {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--md-sys-color-outline-variant);
            color: var(--md-sys-color-on-surface-variant);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .step.active .step-number {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }

        .step.completed .step-number {
            background-color: var(--md-sys-color-primary);
            color: var(--md-sys-color-on-primary);
        }

        .step-label {
            font-size: 0.875rem;
            color: var(--md-sys-color-on-surface-variant);
            text-align: center;
        }

        .step-content {
            margin-bottom: 32px;
        }

        .config-info {
            background-color: var(--md-sys-color-surface-variant);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 24px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .config-item:last-child {
            margin-bottom: 0;
        }

        .config-label {
            font-weight: 500;
        }

        .config-value {
            font-family: monospace;
            color: var(--md-sys-color-on-surface-variant);
        }

        .requirements-list {
            list-style: none;
            padding: 0;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid var(--md-sys-color-outline-variant);
        }

        .requirement-item:last-child {
            border-bottom: none;
        }

        .requirement-status {
            margin-right: 12px;
            font-size: 20px;
        }

        .requirement-status.pass {
            color: #4caf50;
        }

        .requirement-status.fail {
            color: var(--md-sys-color-error);
        }

        .error-message, .success-message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .error-message {
            background-color: #ffeaea;
            border: 1px solid var(--md-sys-color-error);
            color: var(--md-sys-color-error);
        }

        .success-message {
            background-color: #e8f5e8;
            border: 1px solid #4caf50;
            color: #2e7d32;
        }

        .button-group {
            display: flex;
            gap: 16px;
            justify-content: flex-end;
        }

        .mdc-button {
            height: 40px;
        }

        .feature-list {
            margin: 24px 0;
        }

        .feature-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
        }

        .feature-icon {
            margin-right: 12px;
            color: var(--md-sys-color-primary);
        }

        @media (max-width: 768px) {
            .install-container {
                margin: 0;
                padding: 24px;
                border-radius: 0;
            }

            .step-label {
                font-size: 0.75rem;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <h1 class="install-title">Support Log Manager</h1>
            <p class="install-subtitle">WordPress & WooCommerce Plugin Support Installation</p>
        </div>

        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                <div class="step-number"><?php echo $step > 1 ? '✓' : '1'; ?></div>
                <div class="step-label">Requirements</div>
            </div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                <div class="step-number"><?php echo $step > 2 ? '✓' : '2'; ?></div>
                <div class="step-label">Database</div>
            </div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                <div class="step-number"><?php echo $step > 3 ? '✓' : '3'; ?></div>
                <div class="step-label">Setup</div>
            </div>
            <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                <div class="step-number"><?php echo $step >= 4 ? '✓' : '4'; ?></div>
                <div class="step-label">Complete</div>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <span class="material-icons">error</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <span class="material-icons">check_circle</span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="step-content">
            <?php if ($step == 1): ?>
                <h2>System Requirements</h2>
                <p>Please ensure your system meets the following requirements:</p>
                
                <ul class="requirements-list">
                    <li class="requirement-item">
                        <span class="requirement-status pass material-icons">check_circle</span>
                        <span>PHP <?php echo PHP_VERSION; ?> (8.0+ required)</span>
                    </li>
                    <li class="requirement-item">
                        <span class="requirement-status <?php echo extension_loaded('pdo_mysql') ? 'pass' : 'fail'; ?> material-icons">
                            <?php echo extension_loaded('pdo_mysql') ? 'check_circle' : 'cancel'; ?>
                        </span>
                        <span>PDO MySQL Extension</span>
                    </li>
                    <li class="requirement-item">
                        <span class="requirement-status <?php echo is_writable('.') ? 'pass' : 'fail'; ?> material-icons">
                            <?php echo is_writable('.') ? 'check_circle' : 'cancel'; ?>
                        </span>
                        <span>Write permissions on application directory</span>
                    </li>
                    <li class="requirement-item">
                        <span class="requirement-status <?php echo function_exists('session_start') ? 'pass' : 'fail'; ?> material-icons">
                            <?php echo function_exists('session_start') ? 'check_circle' : 'cancel'; ?>
                        </span>
                        <span>Session support</span>
                    </li>
                </ul>

                <div class="config-info">
                    <h3>Database Configuration</h3>
                    <div class="config-item">
                        <span class="config-label">Host:</span>
                        <span class="config-value"><?php echo DB_HOST; ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Database:</span>
                        <span class="config-value"><?php echo DB_NAME; ?></span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Username:</span>
                        <span class="config-value"><?php echo DB_USER; ?></span>
                    </div>
                </div>

                <form method="POST">
                    <div class="button-group">
                        <button type="submit" class="mdc-button mdc-button--raised">
                            <span class="mdc-button__label">Test Database Connection</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 2): ?>
                <h2>Database Setup</h2>
                <p>The database connection test was successful. Now we'll create the necessary database tables and initial configuration.</p>
                
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon material-icons">table_chart</span>
                        <span>Create support_logs table</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">security</span>
                        <span>Create sessions table for secure authentication</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">data_usage</span>
                        <span>Insert sample data for testing (optional)</span>
                    </div>
                </div>

                <form method="POST">
                    <div class="button-group">
                        <button type="submit" class="mdc-button mdc-button--raised">
                            <span class="mdc-button__label">Setup Database</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 3): ?>
                <h2>Final Setup</h2>
                <p>Almost done! We'll now complete the installation by creating necessary directories and configuration files.</p>
                
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon material-icons">folder</span>
                        <span>Create uploads directory for import files</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">folder</span>
                        <span>Create exports directory for export files</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">lock</span>
                        <span>Create installation lock file</span>
                    </div>
                </div>

                <div class="config-info">
                    <h3>Default Admin Credentials</h3>
                    <div class="config-item">
                        <span class="config-label">Username:</span>
                        <span class="config-value">admin</span>
                    </div>
                    <div class="config-item">
                        <span class="config-label">Password:</span>
                        <span class="config-value">support123</span>
                    </div>
                    <p style="margin-top: 16px; font-size: 0.875rem; color: var(--md-sys-color-on-surface-variant);">
                        <strong>Important:</strong> Please change the default password after installation for security.
                    </p>
                </div>

                <form method="POST">
                    <div class="button-group">
                        <button type="submit" class="mdc-button mdc-button--raised">
                            <span class="mdc-button__label">Complete Installation</span>
                        </button>
                    </div>
                </form>

            <?php elseif ($step == 4): ?>
                <h2>Installation Complete!</h2>
                <div class="success-message">
                    <span class="material-icons">check_circle</span>
                    Your Support Log Manager has been successfully installed and configured.
                </div>
                
                <div class="feature-list">
                    <div class="feature-item">
                        <span class="feature-icon material-icons">dashboard</span>
                        <span>Dashboard with comprehensive analytics</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">list</span>
                        <span>Full CRUD operations for support logs</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">view_kanban</span>
                        <span>Multiple view modes (Table, Kanban, Grouped)</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">import_export</span>
                        <span>CSV/JSON import and export functionality</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">search</span>
                        <span>Advanced search and filtering</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon material-icons">security</span>
                        <span>Secure, local-only operation</span>
                    </div>
                </div>

                <div class="button-group">
                    <a href="login.php" class="mdc-button mdc-button--raised">
                        <span class="mdc-button__label">Go to Login</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Material Components Web -->
    <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Material Components
            document.querySelectorAll('.mdc-button').forEach(element => {
                new mdc.ripple.MDCRipple(element);
            });
        });
    </script>
</body>
</html>
