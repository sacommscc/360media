<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $subtitle = sanitize($_POST['subtitle']);
    $cta_primary_text = sanitize($_POST['cta_primary_text']);
    $cta_primary_link = sanitize($_POST['cta_primary_link']);
    $cta_secondary_text = sanitize($_POST['cta_secondary_text']);
    $cta_secondary_link = sanitize($_POST['cta_secondary_link']);

    // Handle background video upload
    $background_video = '';
    if (isset($_FILES['background_video']) && $_FILES['background_video']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['background_video'], 'videos');
        if (isset($upload_result['success'])) {
            $background_video = $upload_result['filename'];
        }
    }

    // Handle background image upload
    $background_image = '';
    if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['background_image'], 'hero');
        if (isset($upload_result['success'])) {
            $background_image = $upload_result['filename'];
        }
    }

    // Update or insert hero content
    $stmt = $db->prepare("SELECT id FROM hero_section WHERE id = 1");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $query = "UPDATE hero_section SET title = ?, subtitle = ?, cta_primary_text = ?, cta_primary_link = ?, cta_secondary_text = ?, cta_secondary_link = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$title, $subtitle, $cta_primary_text, $cta_primary_link, $cta_secondary_text, $cta_secondary_link];

        if ($background_video) {
            $query .= ", background_video = ?";
            $params[] = $background_video;
        }
        if ($background_image) {
            $query .= ", background_image = ?";
            $params[] = $background_image;
        }

        $query .= " WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $db->prepare("INSERT INTO hero_section (title, subtitle, background_video, background_image, cta_primary_text, cta_primary_link, cta_secondary_text, cta_secondary_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $subtitle, $background_video, $background_image, $cta_primary_text, $cta_primary_link, $cta_secondary_text, $cta_secondary_link]);
    }

    $message = 'Hero section updated successfully!';
}

// Get current hero data
$hero = $db->query("SELECT * FROM hero_section WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$hero) {
    $hero = [
        'title' => 'Luxury in Motion',
        'subtitle' => 'Experience Every Angle with Pakistan\'s Leading 360 Video Booth Manufacturer',
        'background_video' => '',
        'background_image' => '',
        'cta_primary_text' => 'Explore Booths',
        'cta_primary_link' => '#products',
        'cta_secondary_text' => 'Get a Quote',
        'cta_secondary_link' => '#contact'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hero Section - 360 Media Admin</title>
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

        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
        }

        .file-upload {
            position: relative;
            display: inline-block;
            width: 100%;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-upload label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px;
            border: 2px dashed #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
            transition: border-color 0.3s ease;
        }

        .file-upload label:hover {
            border-color: #FFD700;
        }

        .file-upload label i {
            margin-right: 10px;
            color: #666;
        }

        .current-file {
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
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
                <li><a href="hero.php" class="active"><i class="fas fa-image"></i> Hero Section</a></li>
                <li><a href="about.php"><i class="fas fa-info-circle"></i> About Section</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="experience.php"><i class="fas fa-star"></i> Experience</a></li>
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h2>Hero Section Management</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Hero Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($hero['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="subtitle">Hero Subtitle</label>
                        <textarea id="subtitle" name="subtitle" required><?php echo htmlspecialchars($hero['subtitle']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="background_video">Background Video (MP4)</label>
                        <div class="file-upload">
                            <input type="file" id="background_video" name="background_video" accept="video/mp4">
                            <label for="background_video">
                                <i class="fas fa-video"></i> Choose Video File
                            </label>
                        </div>
                        <?php if ($hero['background_video']): ?>
                            <div class="current-file">Current: <?php echo htmlspecialchars($hero['background_video']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="background_image">Background Image (Fallback)</label>
                        <div class="file-upload">
                            <input type="file" id="background_image" name="background_image" accept="image/*">
                            <label for="background_image">
                                <i class="fas fa-image"></i> Choose Image File
                            </label>
                        </div>
                        <?php if ($hero['background_image']): ?>
                            <div class="current-file">Current: <?php echo htmlspecialchars($hero['background_image']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="cta_primary_text">Primary CTA Text</label>
                        <input type="text" id="cta_primary_text" name="cta_primary_text" value="<?php echo htmlspecialchars($hero['cta_primary_text']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cta_primary_link">Primary CTA Link</label>
                        <input type="text" id="cta_primary_link" name="cta_primary_link" value="<?php echo htmlspecialchars($hero['cta_primary_link']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cta_secondary_text">Secondary CTA Text</label>
                        <input type="text" id="cta_secondary_text" name="cta_secondary_text" value="<?php echo htmlspecialchars($hero['cta_secondary_text']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cta_secondary_link">Secondary CTA Link</label>
                        <input type="text" id="cta_secondary_link" name="cta_secondary_link" value="<?php echo htmlspecialchars($hero['cta_secondary_link']); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Hero Section</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
