<?php
// Database configuration
define('DB_TYPE', 'sqlite'); // Change to 'mysql' for MySQL
define('DB_FILE', __DIR__ . '/database/360media.db');

// MySQL configuration (if using MySQL)
define('DB_HOST', 'localhost');
define('DB_NAME', '360media');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', '360 Media');
define('SITE_URL', 'http://localhost/360media');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ADMIN_EMAIL', 'admin@360media.pk');

// Initialize database connection
function getDB() {
    static $db = null;
    if ($db === null) {
        if (DB_TYPE === 'sqlite') {
            $db = new PDO('sqlite:' . DB_FILE);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } else {
            $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
    return $db;
}

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Session configuration
session_start();

// Helper functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateSlug($string) {
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
}

function uploadImage($file, $folder = 'general') {
    $target_dir = UPLOAD_DIR . $folder . '/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }

    if ($file['size'] > 5000000) { // 5MB limit
        return ['error' => 'File size too large. Maximum 5MB allowed.'];
    }

    $filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['success' => true, 'filename' => $folder . '/' . $filename];
    } else {
        return ['error' => 'Failed to upload file.'];
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: admin/login.php');
        exit;
    }
}
?>
