<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Default admin credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'support123'); // In production, use hashed passwords

function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function login($username, $password) {
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

function logout() {
    session_unset();
    session_destroy();
    session_write_close();
    setcookie(session_name(), '', 0, '/');
    session_regenerate_id(true);
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
    
    // Check session timeout (4 hours)
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 14400) {
        logout();
        header('Location: login.php?timeout=1');
        exit;
    }
}

// Initialize session handler
function initializeSession() {
    $pdo = getDatabase();
    $handler = new DatabaseSessionHandler($pdo);
    session_set_save_handler($handler, true);
}
?>
