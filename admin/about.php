<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $highlight_text = sanitize($_POST['highlight_text']);
    $stats = json_encode(array_map('sanitize', $_POST['stats'] ?? []));

    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image'], 'about');
        if (isset($upload_result['success'])) {
            $image = $upload_result['filename'];
        }
    }

    // Update or insert about content
    $stmt = $db->prepare("SELECT id FROM about_section WHERE id = 1");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $query = "UPDATE about_section SET title = ?, content = ?, highlight_text = ?, stats = ?, updated_at = CURRENT_TIMESTAMP";
        $params = [$title, $content, $highlight_text, $stats];

        if ($image) {
            $query .= ", image = ?";
            $params[] = $image;
        }

        $query .= " WHERE id = 1";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
    } else {
        $stmt = $db->prepare("INSERT INTO about_section (title, content, highlight_text, stats, image) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $highlight_text, $stats, $image]);
    }

    $message = 'About section updated successfully!';
}

// Get current about data
$about = $db->query("SELECT * FROM about_section WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$about) {
    $about = [
        'title' => 'About 360 Media',
        'content' => '360 Media is Pakistan\'s leading manufacturer of smart, ultra-slim 360 video booths built for elegance, performance, and innovation. Our products serve wedding planners, event management companies, media houses, and creative professionals who demand sophistication and reliability.',
        'highlight_text' => 'Proudly Made in Pakistan – Designed for the World',
        'stats' => json_encode([
            ['number' => '500+', 'label' => 'Events Covered'],
            ['number' => '50+', 'label' => 'Happy Clients'],
            ['number' => '3', 'label' => 'Years Experience']
        ]),
        'image' => ''
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Section - 360 Media Admin</title>
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

        .stats-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .stats-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .stat-item:last-child {
            border-bottom: none;
        }

        .remove-stat {
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
                <li><a href="about.php" class="active"><i class="fas fa-info-circle"></i> About Section</a></li>
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
            <h2>About Section Management</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Section Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($about['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="6" required><?php echo htmlspecialchars($about['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="highlight_text">Highlight Text</label>
                        <input type="text" id="highlight_text" name="highlight_text" value="<?php echo htmlspecialchars($about['highlight_text']); ?>">
                    </div>

                    <div class="form-group">
                        <label>Statistics</label>
                        <div class="stats-input">
                            <input type="text" id="statNumber" placeholder="Number (e.g., 500+)">
                            <input type="text" id="statLabel" placeholder="Label (e.g., Events Covered)">
                            <button type="button" class="btn btn-primary" onclick="addStat()">Add Stat</button>
                        </div>
                        <div class="stats-list" id="statsList">
                            <?php
                            $stats = json_decode($about['stats'], true);
                            if ($stats) {
                                foreach ($stats as $index => $stat) {
                                    echo "<div class='stat-item'>
                                        <span>{$stat['number']} - {$stat['label']}</span>
                                        <span class='remove-stat' onclick='removeStat({$index})'>&times;</span>
                                    </div>";
                                }
                            }
                            ?>
                        </div>
                        <input type="hidden" name="stats[]" id="statsData" value="<?php echo htmlspecialchars($about['stats']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="image">Background Image</label>
                        <div class="file-upload">
                            <input type="file" id="image" name="image" accept="image/*">
                            <label for="image">
                                <i class="fas fa-image"></i> Choose Image
                            </label>
                        </div>
                        <?php if ($about['image']): ?>
                            <div class="current-file">Current: <?php echo htmlspecialchars($about['image']); ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Update About Section</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        let stats = <?php echo $about['stats'] ?: '[]'; ?>;

        function addStat() {
            const number = document.getElementById('statNumber').value.trim();
            const label = document.getElementById('statLabel').value.trim();
            if (number && label) {
                stats.push({number: number, label: label});
                document.getElementById('statNumber').value = '';
                document.getElementById('statLabel').value = '';
                updateStatsList();
            }
        }

        function removeStat(index) {
            stats.splice(index, 1);
            updateStatsList();
        }

        function updateStatsList() {
            const list = document.getElementById('statsList');
            const data = document.getElementById('statsData');

            list.innerHTML = stats.map((stat, index) =>
                `<div class="stat-item">
                    <span>${stat.number} - ${stat.label}</span>
                    <span class="remove-stat" onclick="removeStat(${index})">&times;</span>
                </div>`
            ).join('');

            data.value = JSON.stringify(stats);
        }
    </script>
</body>
</html>
