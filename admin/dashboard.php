<?php
require_once '../config.php';
requireLogin();

$db = getDB();

// Get statistics
$stats = [
    'products' => $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
    'testimonials' => $db->query("SELECT COUNT(*) FROM testimonials WHERE is_active = 1")->fetchColumn(),
    'gallery' => $db->query("SELECT COUNT(*) FROM gallery WHERE is_active = 1")->fetchColumn(),
    'contacts' => $db->query("SELECT COUNT(*) FROM contact_submissions WHERE is_read = 0")->fetchColumn()
];

// Get recent contact submissions
$recent_contacts = $db->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 360 Media Admin</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: #000;
            font-size: 1.2rem;
        }

        .stat-info h3 {
            font-size: 2rem;
            margin-bottom: 5px;
            color: #333;
        }

        .stat-info p {
            color: #666;
        }

        .recent-contacts {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .recent-contacts h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .contact-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-name {
            font-weight: 600;
            color: #333;
        }

        .contact-email {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-date {
            color: #999;
            font-size: 0.8rem;
        }

        .view-all {
            display: inline-block;
            margin-top: 15px;
            color: #FFD700;
            text-decoration: none;
        }

        .view-all:hover {
            text-decoration: underline;
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="hero.php"><i class="fas fa-image"></i> Hero Section</a></li>
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
            <h2>Dashboard Overview</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['products']; ?></h3>
                        <p>Active Products</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['testimonials']; ?></h3>
                        <p>Testimonials</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-images"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['gallery']; ?></h3>
                        <p>Gallery Items</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['contacts']; ?></h3>
                        <p>Unread Messages</p>
                    </div>
                </div>
            </div>

            <div class="recent-contacts">
                <h2>Recent Contact Submissions</h2>
                <?php if (empty($recent_contacts)): ?>
                    <p>No recent contact submissions.</p>
                <?php else: ?>
                    <?php foreach ($recent_contacts as $contact): ?>
                        <div class="contact-item">
                            <div class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></div>
                            <div class="contact-email"><?php echo htmlspecialchars($contact['email']); ?></div>
                            <div class="contact-date"><?php echo date('M j, Y g:i A', strtotime($contact['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                    <a href="contacts.php" class="view-all">View All Messages</a>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
