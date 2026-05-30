<?php
// ============================================================
// includes/config.php
// Database configuration and PDO connection
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'driveflow');
define('SITE_NAME', 'DriveFlow');
define('SITE_URL', 'http://localhost/driveflow');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/driveflow/assets/images/vehicles/');
define('UPLOAD_URL', SITE_URL . '/assets/images/vehicles/');

// PDO Connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:monospace;background:#1a1a2e;color:#ff4d6d;padding:20px;border-radius:8px;margin:20px;">
                <h3>⚠️ Database Connection Failed</h3>
                <p>' . htmlspecialchars($e->getMessage()) . '</p>
                <p>Please check your database configuration in <strong>includes/config.php</strong></p>
            </div>');
        }
    }
    return $pdo;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: Log activity
function logActivity($user_id, $activity) {
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, activity, ip_address) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $activity, $ip]);
}

// Helper: Create notification
function createNotification($user_id, $title, $message) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $title, $message]);
}

// Helper: Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// Helper: Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: Check if logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: Require login
function requireLogin($role = null) {
    if (!isLoggedIn()) {
        redirect(SITE_URL . '/login.php');
    }
    if ($role && $_SESSION['role'] !== $role) {
        redirect(SITE_URL . '/login.php');
    }
}

// Helper: Sanitize input
function clean($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Helper: Format currency (PKR)
function formatCurrency($amount) {
    return 'PKR ' . number_format($amount, 0);
}
?>