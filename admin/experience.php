<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $features = json_encode(array_map('sanitize', $_POST['features'] ?? []));

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
        $upload_result = uploadImage($_FILES['background_image'], 'experience');
        if (isset($upload_result['success'])) {
            $background_image = $upload_result['filename'];
        }
    }

    // Update or insert experience content
    $stmt = $db->prepare("SELECT id FROM experience_section WHERE id = 1");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $query = "UPDATE experience_section SET title = ?, content = ?, features = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$title, $content, $features];

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
        $stmt = $db->prepare("INSERT INTO experience_section (title, content, background_video, background_image, features) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $background_video, $background_image, $features]);
    }

    $message = 'Experience section updated successfully!';
}

// Get current experience data
$experience = $db->query("SELECT * FROM experience_section WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$experience) {
    $experience = [
        'title' => 'Where Technology Meets Celebration',
        'content' => 'Our 360 video booths transform ordinary events into extraordinary experiences. From intimate weddings to grand corporate functions, we capture every moment in stunning 360-degree detail.',
        'background_video' => '',
        'background_image' => '',
        'features' => json_encode([
            ['icon' => 'fas fa-heart', 'title' => 'Weddings', 'description' => 'Capture love stories from every angle'],
            ['icon' => 'fas fa-glass-cheers', 'title' => 'Parties', 'description' => 'Create unforgettable party memories'],
            ['icon' => 'fas fa-building', 'title' => 'Corporate', 'description' => 'Elevate brand experiences']
        ])
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Experience Section - 360 Media Admin</title>
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

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #FFD700;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .features-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .features-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }

        .feature-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .feature-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .feature-title {
            font-weight: 600;
            color: #333;
        }

        .feature-description {
            color: #666;
            font-size: 0.9rem;
        }

        .remove-feature {
            color: #dc3545;
            cursor: pointer;
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

        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        .feature-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .feature-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 10px;
            align-items: end;
        }

        .feature-form-grid input {
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 3px;
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
                <li><a href="experience.php" class="active"><i class="fas fa-star"></i> Experience</a></li>
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="contacts.php"><i class="fas fa-inbox"></i> Messages</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h2>Experience Section Management</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Section Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($experience['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="4" required><?php echo htmlspecialchars($experience['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="background_video">Background Video (MP4)</label>
                        <div class="file-upload">
                            <input type="file" id="background_video" name="background_video" accept="video/mp4">
                            <label for="background_video">
                                <i class="fas fa-video"></i> Choose Video File
                            </label>
                        </div>
                        <?php if ($experience['background_video']): ?>
                            <div class="current-file">Current: <?php echo htmlspecialchars($experience['background_video']); ?></div>
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
                        <?php if ($experience['background_image']): ?>
                            <div class="current-file">Current: <?php echo htmlspecialchars($experience['background_image']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Features</label>

                        <div class="feature-form">
                            <div class="feature-form-grid">
                                <div>
                                    <label>Icon Class</label>
                                    <input type="text" id="featureIcon" placeholder="fas fa-heart" value="fas fa-star">
                                </div>
                                <div>
                                    <label>Title</label>
                                    <input type="text" id="featureTitle" placeholder="Feature Title">
                                </div>
                                <div>
                                    <label>Description</label>
                                    <input type="text" id="featureDescription" placeholder="Feature description">
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="addFeature()">Add Feature</button>
                            </div>
                        </div>

                        <div class="features-list" id="featuresList">
                            <?php
                            $features = json_decode($experience['features'], true);
                            if ($features) {
                                foreach ($features as $index => $feature) {
                                    echo "<div class='feature-item'>
                                        <div class='feature-info'>
                                            <div class='feature-title'><i class='{$feature['icon']}'></i> {$feature['title']}</div>
                                            <div class='feature-description'>{$feature['description']}</div>
                                        </div>
                                        <span class='remove-feature' onclick='removeFeature({$index})'>&times;</span>
                                    </div>";
                                }
                            }
                            ?>
                        </div>
                        <input type="hidden" name="features[]" id="featuresData" value="<?php echo htmlspecialchars($experience['features']); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Experience Section</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        let features = <?php echo $experience['features'] ?: '[]'; ?>;

        function addFeature() {
            const icon = document.getElementById('featureIcon').value.trim();
            const title = document.getElementById('featureTitle').value.trim();
            const description = document.getElementById('featureDescription').value.trim();

            if (title && description) {
                features.push({icon: icon || 'fas fa-star', title: title, description: description});
                document.getElementById('featureIcon').value = 'fas fa-star';
                document.getElementById('featureTitle').value = '';
                document.getElementById('featureDescription').value = '';
                updateFeaturesList();
            }
        }

        function removeFeature(index) {
            features.splice(index, 1);
            updateFeaturesList();
        }

        function updateFeaturesList() {
            const list = document.getElementById('featuresList');
            const data = document.getElementById('featuresData');

            list.innerHTML = features.map((feature, index) =>
                `<div class="feature-item">
                    <div class="feature-info">
                        <div class="feature-title"><i class="${feature.icon}"></i> ${feature.title}</div>
                        <div class="feature-description">${feature.description}</div>
                    </div>
                    <span class="remove-feature" onclick="removeFeature(${index})">&times;</span>
                </div>`
            ).join('');

            data.value = JSON.stringify(features);
        }
    </script>
</body>
</html>
