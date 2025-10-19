<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $benefits = json_encode(array_map('sanitize', $_POST['benefits'] ?? []));

    // Update or insert partners content
    $stmt = $db->prepare("SELECT id FROM partners_section WHERE id = 1");
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        $stmt = $db->prepare("UPDATE partners_section SET title = ?, content = ?, benefits = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1");
        $stmt->execute([$title, $content, $benefits]);
    } else {
        $stmt = $db->prepare("INSERT INTO partners_section (title, content, benefits) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $benefits]);
    }

    $message = 'Partners section updated successfully!';
}

// Get current partners data
$partners = $db->query("SELECT * FROM partners_section WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
if (!$partners) {
    $partners = [
        'title' => 'Become a Distributor',
        'content' => 'Join our exclusive network of partners and bring luxury 360 video experiences to your region. We offer comprehensive support, training, and marketing materials to ensure your success.',
        'benefits' => json_encode([
            ['icon' => 'fas fa-handshake', 'title' => 'Exclusive Partnership', 'description' => 'Be the first in your area with our premium booths'],
            ['icon' => 'fas fa-graduation-cap', 'title' => 'Training & Support', 'description' => 'Complete training and ongoing technical support'],
            ['icon' => 'fas fa-chart-line', 'title' => 'Marketing Materials', 'description' => 'Branded materials and marketing campaigns']
        ])
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Partners Section - 360 Media Admin</title>
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

        .benefits-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .benefits-list {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }

        .benefit-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .benefit-item:last-child {
            border-bottom: none;
        }

        .benefit-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .benefit-title {
            font-weight: 600;
            color: #333;
        }

        .benefit-description {
            color: #666;
            font-size: 0.9rem;
        }

        .remove-benefit {
            color: #dc3545;
            cursor: pointer;
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

        .benefit-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .benefit-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 2fr;
            gap: 10px;
            align-items: end;
        }

        .benefit-form-grid input {
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
                <li><a href="experience.php"><i class="fas fa-star"></i> Experience</a></li>
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php" class="active"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="contacts.php"><i class="fas fa-inbox"></i> Messages</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h2>Partners Section Management</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label for="title">Section Title</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($partners['title']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="4" required><?php echo htmlspecialchars($partners['content']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Benefits</label>

                        <div class="benefit-form">
                            <div class="benefit-form-grid">
                                <div>
                                    <label>Icon Class</label>
                                    <input type="text" id="benefitIcon" placeholder="fas fa-handshake" value="fas fa-star">
                                </div>
                                <div>
                                    <label>Title</label>
                                    <input type="text" id="benefitTitle" placeholder="Benefit Title">
                                </div>
                                <div>
                                    <label>Description</label>
                                    <input type="text" id="benefitDescription" placeholder="Benefit description">
                                </div>
                                <button type="button" class="btn btn-secondary" onclick="addBenefit()">Add Benefit</button>
                            </div>
                        </div>

                        <div class="benefits-list" id="benefitsList">
                            <?php
                            $benefits = json_decode($partners['benefits'], true);
                            if ($benefits) {
                                foreach ($benefits as $index => $benefit) {
                                    echo "<div class='benefit-item'>
                                        <div class='benefit-info'>
                                            <div class='benefit-title'><i class='{$benefit['icon']}'></i> {$benefit['title']}</div>
                                            <div class='benefit-description'>{$benefit['description']}</div>
                                        </div>
                                        <span class='remove-benefit' onclick='removeBenefit({$index})'>&times;</span>
                                    </div>";
                                }
                            }
                            ?>
                        </div>
                        <input type="hidden" name="benefits[]" id="benefitsData" value="<?php echo htmlspecialchars($partners['benefits']); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Update Partners Section</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        let benefits = <?php echo $partners['benefits'] ?: '[]'; ?>;

        function addBenefit() {
            const icon = document.getElementById('benefitIcon').value.trim();
            const title = document.getElementById('benefitTitle').value.trim();
            const description = document.getElementById('benefitDescription').value.trim();

            if (title && description) {
                benefits.push({icon: icon || 'fas fa-star', title: title, description: description});
                document.getElementById('benefitIcon').value = 'fas fa-star';
                document.getElementById('benefitTitle').value = '';
                document.getElementById('benefitDescription').value = '';
                updateBenefitsList();
            }
        }

        function removeBenefit(index) {
            benefits.splice(index, 1);
            updateBenefitsList();
        }

        function updateBenefitsList() {
            const list = document.getElementById('benefitsList');
            const data = document.getElementById('benefitsData');

            list.innerHTML = benefits.map((benefit, index) =>
                `<div class="benefit-item">
                    <div class="benefit-info">
                        <div class="benefit-title"><i class="${benefit.icon}"></i> ${benefit.title}</div>
                        <div class="benefit-description">${benefit.description}</div>
                    </div>
                    <span class="remove-benefit" onclick="removeBenefit(${index})">&times;</span>
                </div>`
            ).join('');

            data.value = JSON.stringify(benefits);
        }
    </script>
</body>
</html>
