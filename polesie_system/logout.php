<?php
/**
 * Logout handler for OAO "Polesieelectromash" ERP System
 */

require_once 'includes/config.php';

// Log activity before logout
if (isLoggedIn()) {
    try {
        $pdo = getDBConnection();
        logActivity($pdo, $_SESSION['user_id'], 'user_logout', 'users', $_SESSION['user_id']);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Destroy session
session_destroy();

// Redirect to login page
redirect('index.php');
?>
