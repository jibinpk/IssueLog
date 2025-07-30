<?php
session_start();
require_once 'includes/auth.php';
require_once 'config/database.php';

// Initialize session handler
initializeSession();

// Perform logout
logout();

// Redirect to login page with a logout message
header('Location: login.php?logout=1');
exit;
?>
