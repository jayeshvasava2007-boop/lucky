<?php
/**
 * Logout Handler
 * Sans Digital Work - SDW
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

// Setup secure session BEFORE starting it
setupSecureSession();
session_start();

logoutUser();
header('Location: login.php');
exit();
?>
