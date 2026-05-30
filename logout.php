<?php
// logout.php
require_once 'includes/config.php';
if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], "User logged out: " . $_SESSION['full_name']);
}
session_destroy();
redirect('login.php');