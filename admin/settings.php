<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'update_settings') {
            $settings = [
                'site_name' => sanitize($_POST['site_name']),
                'site_description' => sanitize($_POST['site_description']),
                'contact_email' => sanitize($_POST['contact_email']),
                'contact_phone' => sanitize($_POST['contact_phone']),
                'whatsapp_number' => sanitize($_POST['whatsapp_number']),
                'address' => sanitize($_POST['address']),
                'facebook_url' => sanitize($_POST['facebook_url']),
                'instagram_url' => sanitize($_POST['instagram_url']),
                'tiktok_url' => sanitize($_POST['tiktok_url']),
                'youtube_url' => sanitize($_POST['youtube_url'])
            ];

            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("INSERT OR REPLACE INTO site_settings (setting_key, setting_value, setting_type, updated_at) VALUES (?, ?, 'text', CURRENT_TIMESTAMP)");
                $stmt->execute([$key, $value]);
            }

            $message = 'Settings updated successfully!';
        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $message = 'All password fields are required.';
            } elseif ($new_password !== $confirm_password) {
                $message = 'New passwords do not match.';
            } elseif (strlen($new_password) < 6) {
                $message = 'New password must be at least 6 characters long.';
            } else {
                $stmt = $db->prepare("SELECT password FROM admin_users WHERE id = ?");
                $stmt->execute([$_SESSION['admin_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['admin_id']]);
                    $message = 'Password changed successfully!';
                } else {
                    $message = 'Current password is incorrect.';
                }
            }
        }
    }
}

// Get current settings
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - 360 Media Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }

        .admin-header {
            background: #000;
            color: #fff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            color: #FFD700;
        }

        .logout-btn {
            background: #FFD700;
            color: #000;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .dashboard-container {
            display: flex;
            min-height: calc(100vh - 80px);
        }

        .sidebar {
            width: 250px;
            background: #fff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin-bottom: 5px;
        }

        .sidebar a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #FFD700;
            color: #000;
        }

        .sidebar a i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            flex: 1;
            padding: 30px;
        }

        .settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .settings-section {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .settings-section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFD700;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FFD700;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #FFD700;
            color: #000;
        }

        .btn-primary:hover {
            background: #FFA500;
        }

        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .social-links-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .password-section {
            grid-column: 1 / -1;
        }

        @media (max-width: 768px) {
            .settings-container {
                grid-template-columns: 1fr;
            }

            .social-links-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>360 Media Admin Panel</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </header>

    <div class="dashboard-container">
        <aside class="sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="hero.php"><i class="fas fa-image"></i> Hero Section</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Section</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="experience.php"><i class="fas fa-star"></i> Experience</a></li>
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="contacts.php"><i class="fas fa-inbox"></i> Messages</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h2>Website Settings</h2>

            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <!-- General Settings -->
                <div class="settings-section">
                    <h2>General Settings</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">

                        <div class="form-group">
                            <label for="site_name">Site Name</label>
                            <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? '360 Media'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="site_description">Site Description</label>
                            <input type="text" id="site_description" name="site_description" value="<?php echo htmlspecialchars($settings['site_description'] ?? 'Pakistan\'s leading manufacturer of premium 360 video booth machines'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact_email">Contact Email</label>
                            <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? 'info@360media.pk'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="contact_phone">Contact Phone</label>
                            <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '+92 300 1234567'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp Number</label>
                            <input type="tel" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? '+923001234567'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($settings['address'] ?? 'Lahore, Pakistan'); ?>" required>
                        </div>

                        <button type="submit" class="btn btn-primary">Update General Settings</button>
                    </form>
                </div>

                <!-- Social Media Links -->
                <div class="settings-section">
                    <h2>Social Media Links</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_settings">

                        <div class="social-links-grid">
                            <div class="form-group">
                                <label for="facebook_url">Facebook URL</label>
                                <input type="url" id="facebook_url" name="facebook_url" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? '#'); ?>" placeholder="https://facebook.com/...">
                            </div>

                            <div class="form-group">
                                <label for="instagram_url">Instagram URL</label>
                                <input type="url" id="instagram_url" name="instagram_url" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? '#'); ?>" placeholder="https://instagram.com/...">
                            </div>

                            <div class="form-group">
                                <label for="tiktok_url">TikTok URL</label>
                                <input type="url" id="tiktok_url" name="tiktok_url" value="<?php echo htmlspecialchars($settings['tiktok_url'] ?? '#'); ?>" placeholder="https://tiktok.com/...">
                            </div>

                            <div class="form-group">
                                <label for="youtube_url">YouTube URL</label>
                                <input type="url" id="youtube_url" name="youtube_url" value="<?php echo htmlspecialchars($settings['youtube_url'] ?? '#'); ?>" placeholder="https://youtube.com/...">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Social Links</button>
                    </form>
                </div>

                <!-- Password Change -->
                <div class="settings-section password-section">
                    <h2>Change Password</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">

                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        </div>

                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
