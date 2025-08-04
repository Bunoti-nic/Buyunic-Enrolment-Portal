<?php
require_once '../includes/functions.php';

// Check if user is logged in
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'] ?? null;
    
    // Destroy user session in database
    if ($userId) {
        destroyUserSession($userId);
        
        // Create logout notification
        createNotification(
            $userId,
            'Logged Out',
            'You have been successfully logged out of your account.',
            'info'
        );
    }
}

// Destroy PHP session
session_destroy();

// Redirect to login page with success message
header('Location: login.php?logged_out=1');
exit();
?>