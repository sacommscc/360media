<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add_contact' || $action === 'edit_contact') {
            $icon = sanitize($_POST['icon']);
            $value = sanitize($_POST['value']);
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            if ($action === 'add_contact') {
                $stmt = $db->prepare("INSERT INTO contact_info (icon, value, sort_order) VALUES (?, ?, ?)");
                $stmt->execute([$icon, $value, $sort_order]);
                $message = 'Contact info added successfully!';
            } else {
                $id = (int)$_POST['id'];
                $stmt = $db->prepare("UPDATE contact_info SET icon = ?, value = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$icon, $value, $sort_order, $id]);
                $message = 'Contact info updated successfully!';
            }
        } elseif ($action === 'delete_contact') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE contact_info SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Contact info deleted successfully!';
        } elseif ($action === 'update_social') {
            $platform = sanitize($_POST['platform']);
            $url = sanitize($_POST['url']);
            $icon_class = sanitize($_POST['icon_class']);
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            $stmt = $db->prepare("INSERT OR REPLACE INTO social_links (platform, url, icon_class, sort_order, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$platform, $url, $icon_class, $sort_order]);
            $message = 'Social link updated successfully!';
        } elseif ($action === 'delete_social') {
            $platform = sanitize($_POST['platform']);
            $stmt = $db->prepare("UPDATE social_links SET is_active = 0 WHERE platform = ?");
            $stmt->execute([$platform]);
            $message = 'Social link deleted successfully!';
        } elseif ($action === 'update_footer') {
            $section_name = sanitize($_POST['section_name']);
            $content = sanitize($_POST['content']);
            $links = json_encode(array_map('sanitize', $_POST['links'] ?? []));
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            $stmt = $db->prepare("INSERT OR REPLACE INTO footer_content (section_name, content, links, sort_order, updated_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)");
            $stmt->execute([$section_name, $content, $links, $sort_order]);
            $message = 'Footer section updated successfully!';
        } elseif ($action === 'delete_footer') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE footer_content SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Footer section deleted successfully!';
        }
    }
}

// Get contact info
$contact_info = $db->query("SELECT * FROM contact_info WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get social links
$social_links = $db->query("SELECT * FROM social_links WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get footer content
$footer_content = $db->query("SELECT * FROM footer_content WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Info Management - 360 Media Admin</title>
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

        .section-container {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFD700;
        }

        .section-title {
            color: #333;
            font-size: 1.2rem;
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

        .contact-list {
            display: grid;
            gap: 15px;
        }

        .contact-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }

        .contact-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: #FFD700;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
        }

        .contact-details h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .contact-details span {
            color: #666;
        }

        .contact-actions {
            display: flex;
            gap: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-title {
            font-size: 1.5rem;
            color: #333;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #FFD700;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .social-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .social-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }

        .social-item h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .social-item input {
            margin-bottom: 10px;
        }

        .footer-sections {
            display: grid;
            gap: 20px;
        }

        .footer-section {
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }

        .footer-section h4 {
            margin-bottom: 15px;
            color: #333;
        }

        .links-input {
            margin-top: 15px;
        }

        .link-item {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .link-item input {
            flex: 1;
        }

        .remove-link {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
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
                <li><a href="contact.php" class="active"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="contacts.php"><i class="fas fa-inbox"></i> Messages</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <h2>Contact Information Management</h2>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Contact Information -->
            <div class="section-container">
                <div class="section-header">
                    <h3 class="section-title">Contact Information</h3>
                    <button class="btn btn-primary" onclick="openModal('contact')">Add Contact Info</button>
                </div>

                <div class="contact-list">
                    <?php foreach ($contact_info as $contact): ?>
                        <div class="contact-item">
                            <div class="contact-info">
                                <div class="contact-icon">
                                    <i class="<?php echo htmlspecialchars($contact['icon']); ?>"></i>
                                </div>
                                <div class="contact-details">
                                    <h4><?php echo htmlspecialchars($contact['value']); ?></h4>
                                    <span>Sort Order: <?php echo $contact['sort_order']; ?></span>
                                </div>
                            </div>
                            <div class="contact-actions">
                                <button class="btn btn-secondary" onclick="editContact(<?php echo $contact['id']; ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteContact(<?php echo $contact['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Social Media Links -->
            <div class="section-container">
                <div class="section-header">
                    <h3 class="section-title">Social Media Links</h3>
                </div>

                <div class="social-grid">
                    <?php
                    $platforms = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'youtube' => 'YouTube'];
                    foreach ($platforms as $platform => $name):
                        $link = array_filter($social_links, fn($l) => $l['platform'] === $platform);
                        $link = reset($link);
                    ?>
                        <div class="social-item">
                            <h4><?php echo $name; ?></h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_social">
                                <input type="hidden" name="platform" value="<?php echo $platform; ?>">
                                <input type="url" name="url" value="<?php echo htmlspecialchars($link['url'] ?? ''); ?>" placeholder="https://...">
                                <input type="text" name="icon_class" value="<?php echo htmlspecialchars($link['icon_class'] ?? 'fab fa-' . $platform); ?>" placeholder="fab fa-<?php echo $platform; ?>">
                                <input type="number" name="sort_order" value="<?php echo $link['sort_order'] ?? 0; ?>" min="0" placeholder="Sort order">
                                <div style="display: flex; gap: 5px; margin-top: 10px;">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                    <?php if ($link): ?>
                                        <button type="submit" name="action" value="delete_social" class="btn btn-danger" onclick="return confirm('Delete this social link?')">Delete</button>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Footer Content -->
            <div class="section-container">
                <div class="section-header">
                    <h3 class="section-title">Footer Content</h3>
                    <button class="btn btn-primary" onclick="openModal('footer')">Add Footer Section</button>
                </div>

                <div class="footer-sections">
                    <?php foreach ($footer_content as $section): ?>
                        <div class="footer-section">
                            <h4><?php echo htmlspecialchars($section['section_name']); ?></h4>
                            <?php if ($section['content']): ?>
                                <p><?php echo nl2br(htmlspecialchars($section['content'])); ?></p>
                            <?php endif; ?>
                            <?php if ($section['links']): ?>
                                <ul>
                                    <?php $links = json_decode($section['links'], true); ?>
                                    <?php foreach ($links as $link): ?>
                                        <li><a href="<?php echo htmlspecialchars($link['url'] ?? '#'); ?>"><?php echo htmlspecialchars($link['text']); ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <div style="margin-top: 15px;">
                                <button class="btn btn-secondary" onclick="editFooter(<?php echo $section['id']; ?>)">Edit</button>
                                <button class="btn btn-danger" onclick="deleteFooter(<?php echo $section['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="contactModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add Contact Information</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="contactForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_contact">
                <input type="hidden" name="id" id="contactId">

                <div class="form-group">
                    <label for="icon">Icon Class</label>
                    <input type="text" id="icon" name="icon" required placeholder="fas fa-phone">
                </div>

                <div class="form-group">
                    <label for="value">Contact Value</label>
                    <input type="text" id="value" name="value" required placeholder="+92 300 1234567">
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                </div>

                <button type="submit" class="btn btn-primary">Save Contact Info</button>
            </form>
        </div>
    </div>

    <!-- Footer Modal -->
    <div id="footerModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="footerModalTitle">Add Footer Section</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="footerForm" method="POST">
                <input type="hidden" name="action" id="footerFormAction" value="update_footer">
                <input type="hidden" name="id" id="footerId">

                <div class="form-group">
                    <label for="section_name">Section Name</label>
                    <input type="text" id="section_name" name="section_name" required placeholder="Company">
                </div>

                <div class="form-group">
                    <label for="content">Content (Optional)</label>
                    <textarea id="footer_content" name="content" rows="3" placeholder="Section content..."></textarea>
                </div>

                <div class="form-group">
                    <label>Links</label>
                    <div class="links-input">
                        <div class="link-item">
                            <input type="text" id="linkText" placeholder="Link Text">
                            <input type="url" id="linkUrl" placeholder="Link URL">
                            <button type="button" class="btn btn-secondary" onclick="addLink()">Add</button>
                        </div>
                        <div id="linksList"></div>
                        <input type="hidden" name="links[]" id="linksData">
                    </div>
                </div>

                <div class="form-group">
                    <label for="footer_sort_order">Sort Order</label>
                    <input type="number" id="footer_sort_order" name="sort_order" value="0" min="0">
                </div>

                <button type="submit" class="btn btn-primary">Save Footer Section</button>
            </form>
        </div>
    </div>

    <script>
        let links = [];

        function openModal(type, id = null) {
            if (type === 'contact') {
                document.getElementById('modalTitle').textContent = id ? 'Edit Contact Info' : 'Add Contact Info';
                document.getElementById('formAction').value = id ? 'edit_contact' : 'add_contact';
                document.getElementById('contactModal').classList.add('show');

                if (id) {
                    document.getElementById('contactId').value = id;
                } else {
                    document.getElementById('contactForm').reset();
                }
            } else if (type === 'footer') {
                document.getElementById('footerModalTitle').textContent = id ? 'Edit Footer Section' : 'Add Footer Section';
                document.getElementById('footerFormAction').value = 'update_footer';
                document.getElementById('footerModal').classList.add('show');

                if (id) {
                    document.getElementById('footerId').value = id;
                } else {
                    document.getElementById('footerForm').reset();
                    links = [];
                    updateLinksList();
                }
            }
        }

        function closeModal() {
            document.getElementById('contactModal').classList.remove('show');
            document.getElementById('footerModal').classList.remove('show');
        }

        function editContact(id) {
            // In a real implementation, you'd fetch contact data via AJAX
            openModal('contact', id);
        }

        function deleteContact(id) {
            if (confirm('Are you sure you want to delete this contact info?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_contact">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function editFooter(id) {
            // In a real implementation, you'd fetch footer data via AJAX
            openModal('footer', id);
        }

        function deleteFooter(id) {
            if (confirm('Are you sure you want to delete this footer section?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_footer">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function addLink() {
            const text = document.getElementById('linkText').value.trim();
            const url = document.getElementById('linkUrl').value.trim();
            if (text && url) {
                links.push({text: text, url: url});
                document.getElementById('linkText').value = '';
                document.getElementById('linkUrl').value = '';
                updateLinksList();
            }
        }

        function removeLink(index) {
            links.splice(index, 1);
            updateLinksList();
        }

        function updateLinksList() {
            const list = document.getElementById('linksList');
            const data = document.getElementById('linksData');

            list.innerHTML = links.map((link, index) =>
                `<div class="link-item">
                    <span>${link.text} → ${link.url}</span>
                    <span class="remove-link" onclick="removeLink(${index})">&times;</span>
                </div>`
            ).join('');

            data.value = JSON.stringify(links);
        }

        // Close modal when clicking outside
        document.getElementById('contactModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('footerModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
