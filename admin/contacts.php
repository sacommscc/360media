<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'mark_read') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE contact_submissions SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Message marked as read!';
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("DELETE FROM contact_submissions WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Message deleted successfully!';
        }
    }
}

// Get contact submissions with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get total count
$total_count = $db->query("SELECT COUNT(*) FROM contact_submissions")->fetchColumn();
$total_pages = ceil($total_count / $per_page);

// Get submissions
$submissions = $db->prepare("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT ? OFFSET ?");
$submissions->execute([$per_page, $offset]);
$submissions = $submissions->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$unread_count = $db->query("SELECT COUNT(*) FROM contact_submissions WHERE is_read = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages - 360 Media Admin</title>
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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .stats-info {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-info h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #FFD700;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
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

        .btn-danger {
            background: #dc3545;
            color: #fff;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }

        .btn-success:hover {
            background: #218838;
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

        .contacts-list {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .contact-item {
            border-bottom: 1px solid #eee;
            padding: 20px;
            position: relative;
        }

        .contact-item:last-child {
            border-bottom: none;
        }

        .contact-item.unread {
            background: #f8f9ff;
            border-left: 4px solid #FFD700;
        }

        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }

        .contact-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .contact-meta {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-meta span {
            margin-right: 15px;
        }

        .contact-message {
            color: #555;
            line-height: 1.5;
            margin-bottom: 15px;
        }

        .contact-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }

        .pagination a:hover,
        .pagination .current {
            background: #FFD700;
            color: #000;
            border-color: #FFD700;
        }

        .no-messages {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .no-messages i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #ddd;
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
                <li><a href="contacts.php" class="active"><i class="fas fa-inbox"></i> Messages</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="section-header">
                <h2>Contact Messages</h2>
            </div>

            <div class="stats-info">
                <h3>Message Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_count; ?></div>
                        <div class="stat-label">Total Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $unread_count; ?></div>
                        <div class="stat-label">Unread Messages</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_count - $unread_count; ?></div>
                        <div class="stat-label">Read Messages</div>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="contacts-list">
                <?php if (empty($submissions)): ?>
                    <div class="no-messages">
                        <i class="fas fa-inbox"></i>
                        <h3>No messages yet</h3>
                        <p>Contact form submissions will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($submissions as $submission): ?>
                        <div class="contact-item <?php echo $submission['is_read'] ? '' : 'unread'; ?>">
                            <div class="contact-header">
                                <div class="contact-info">
                                    <h4><?php echo htmlspecialchars($submission['name']); ?></h4>
                                    <div class="contact-meta">
                                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($submission['email']); ?></span>
                                        <?php if ($submission['phone']): ?>
                                            <span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($submission['phone']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($submission['created_at'])); ?></span>
                                        <?php if (!$submission['is_read']): ?>
                                            <span class="unread-badge">Unread</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="contact-message">
                                <?php echo nl2br(htmlspecialchars($submission['message'])); ?>
                            </div>
                            <div class="contact-actions">
                                <?php if (!$submission['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                        <button type="submit" class="btn btn-success">Mark as Read</button>
                                    </form>
                                <?php endif; ?>
                                <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>" class="btn btn-primary">Reply</a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
