<?php
require_once '../includes/config.php';
if (isLoggedIn()) {
    $u = currentUser();
    logActivity('Logged Out', 'auth', ($u['name'] ?? 'User').' signed out');
}
session_destroy();
header('Location: ../index.php');
exit;
