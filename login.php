<?php
// Check if application is installed
if (!file_exists('config/installed.lock')) {
    header('Location: install.php');
    exit;
}

// Initialize session configuration first
require_once 'config/session.php';

// Start session after configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/auth.php';
require_once 'includes/csrf.php';
require_once 'config/database.php';

// Initialize session handler
initializeSession();

// Check if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$timeout = false;

// Check for timeout parameter
if (isset($_GET['timeout'])) {
    $timeout = true;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validate CSRF token
    if (!validateCSRFToken($csrf_token)) {
        $error = 'Security token validation failed. Please try again.';
    } elseif (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } elseif (login($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Support Log Manager</title>
    
    <!-- Material Design 3 -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css" rel="stylesheet">
    
    <style>
        /* Login Page Specific Styles */
        :root {
            --md-sys-color-primary: #6750a4;
            --md-sys-color-on-primary: #ffffff;
            --md-sys-color-primary-container: #eaddff;
            --md-sys-color-on-primary-container: #21005d;
            --md-sys-color-secondary: #625b71;
            --md-sys-color-on-secondary: #ffffff;
            --md-sys-color-secondary-container: #e8def8;
            --md-sys-color-on-secondary-container: #1d192b;
            --md-sys-color-surface: #fffbfe;
            --md-sys-color-on-surface: #1c1b1f;
            --md-sys-color-surface-variant: #e7e0ec;
            --md-sys-color-on-surface-variant: #49454f;
            --md-sys-color-error: #ba1a1a;
            --md-sys-color-on-error: #ffffff;
            --md-sys-color-outline: #79747e;
            --md-sys-color-outline-variant: #cab6cf;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, var(--md-sys-color-primary) 0%, var(--md-sys-color-secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--md-sys-color-on-surface);
        }

        .login-container {
            background-color: var(--md-sys-color-surface);
            border-radius: 28px;
            padding: 48px 32px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 24px;
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 500;
            color: var(--md-sys-color-primary);
            margin: 0 0 8px 0;
        }

        .login-subtitle {
            font-size: 1rem;
            color: var(--md-sys-color-on-surface-variant);
            margin: 0;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .form-field {
            position: relative;
        }

        .mdc-text-field {
            width: 100%;
        }

        .error-message {
            background-color: #ffeaea;
            border: 1px solid var(--md-sys-color-error);
            color: var(--md-sys-color-error);
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .timeout-message {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .login-button {
            height: 48px;
            margin-top: 16px;
        }

        .login-footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid var(--md-sys-color-outline-variant);
        }

        .version-info {
            font-size: 0.75rem;
            color: var(--md-sys-color-on-surface-variant);
        }

        .security-notice {
            font-size: 0.875rem;
            color: var(--md-sys-color-on-surface-variant);
            text-align: center;
            margin-top: 24px;
            padding: 16px;
            background-color: var(--md-sys-color-surface-variant);
            border-radius: 8px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                padding: 32px 24px;
                margin: 16px;
                border-radius: 20px;
            }

            .login-title {
                font-size: 1.75rem;
            }
        }

        /* Loading State */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading .mdc-button__label::after {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            margin-left: 8px;
            border: 2px solid currentColor;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Focus styles */
        .mdc-text-field--focused .mdc-floating-label {
            color: var(--md-sys-color-primary);
        }

        .mdc-text-field--focused .mdc-notched-outline__leading,
        .mdc-text-field--focused .mdc-notched-outline__notch,
        .mdc-text-field--focused .mdc-notched-outline__trailing {
            border-color: var(--md-sys-color-primary);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Support Log Manager</h1>
            <p class="login-subtitle">WordPress & WooCommerce Plugin Support</p>
        </div>

        <?php if ($timeout): ?>
            <div class="timeout-message">
                <span class="material-icons">schedule</span>
                Your session has expired. Please log in again.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-message">
                <span class="material-icons">error</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="" id="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <div class="form-field">
                <div class="mdc-text-field mdc-text-field--outlined">
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="mdc-text-field__input" 
                           required 
                           autocomplete="username"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    <div class="mdc-notched-outline">
                        <div class="mdc-notched-outline__leading"></div>
                        <div class="mdc-notched-outline__notch">
                            <label for="username" class="mdc-floating-label">Username</label>
                        </div>
                        <div class="mdc-notched-outline__trailing"></div>
                    </div>
                </div>
            </div>

            <div class="form-field">
                <div class="mdc-text-field mdc-text-field--outlined">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="mdc-text-field__input" 
                           required 
                           autocomplete="current-password">
                    <div class="mdc-notched-outline">
                        <div class="mdc-notched-outline__leading"></div>
                        <div class="mdc-notched-outline__notch">
                            <label for="password" class="mdc-floating-label">Password</label>
                        </div>
                        <div class="mdc-notched-outline__trailing"></div>
                    </div>
                </div>
            </div>

            <button type="submit" class="mdc-button mdc-button--raised login-button">
                <span class="mdc-button__label">Sign In</span>
            </button>
        </form>

        <div class="security-notice">
            <span class="material-icons" style="font-size: 16px; vertical-align: middle;">security</span>
            This application is for local use only. All data is stored securely on your local server.
        </div>

        <div class="login-footer">
            <div class="version-info">
                Support Log Manager v1.0<br>
                Local PHP/MariaDB Application
            </div>
        </div>
    </div>

    <!-- Material Components Web -->
    <script src="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Material Components
            document.querySelectorAll('.mdc-text-field').forEach(element => {
                new mdc.textField.MDCTextField(element);
            });

            document.querySelectorAll('.mdc-button').forEach(element => {
                new mdc.ripple.MDCRipple(element);
            });

            // Handle form submission
            document.getElementById('login-form').addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });

            // Focus username field on load
            document.getElementById('username').focus();

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Enter key to submit from any field
                if (e.key === 'Enter' && (e.target.id === 'username' || e.target.id === 'password')) {
                    document.getElementById('login-form').submit();
                }
            });

            // Auto-clear error messages after 5 seconds
            const errorMessage = document.querySelector('.error-message');
            if (errorMessage) {
                setTimeout(() => {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        errorMessage.style.display = 'none';
                    }, 300);
                }, 5000);
            }

            // Auto-clear timeout message after 10 seconds
            const timeoutMessage = document.querySelector('.timeout-message');
            if (timeoutMessage) {
                setTimeout(() => {
                    timeoutMessage.style.opacity = '0';
                    timeoutMessage.style.transition = 'opacity 0.3s ease';
                    setTimeout(() => {
                        timeoutMessage.style.display = 'none';
                    }, 300);
                }, 10000);
            }
        });
    </script>
</body>
</html>
