<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $title = sanitize($_POST['title']);
            $category = sanitize($_POST['category']);
            $video_url = sanitize($_POST['video_url']);
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['image'], 'gallery');
                if (isset($upload_result['success'])) {
                    $image = $upload_result['filename'];
                }
            }

            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO gallery (title, image, category, video_url, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $image, $category, $video_url, $sort_order]);
                $message = 'Gallery item added successfully!';
            } else {
                $id = (int)$_POST['id'];
                $query = "UPDATE gallery SET title = ?, category = ?, video_url = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP";
                $params = [$title, $category, $video_url, $sort_order];

                if ($image) {
                    $query .= ", image = ?";
                    $params[] = $image;
                }

                $query .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $message = 'Gallery item updated successfully!';
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE gallery SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Gallery item deleted successfully!';
        }
    }
}

// Get all gallery items
$gallery_items = $db->query("SELECT * FROM gallery WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$categories = $db->query("SELECT DISTINCT category FROM gallery WHERE is_active = 1 AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery Management - 360 Media Admin</title>
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

        .filter-section {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 20px;
        }

        .filter-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
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

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .gallery-item {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .gallery-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-image.placeholder {
            color: #666;
            font-size: 3rem;
        }

        .gallery-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gallery-item:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-info {
            padding: 15px;
        }

        .gallery-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .gallery-category {
            color: #FFD700;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .gallery-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 5px;
        }

        .gallery-actions button {
            padding: 6px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: rgba(0, 123, 255, 0.9);
            color: #fff;
        }

        .edit-btn:hover {
            background: rgba(0, 123, 255, 1);
        }

        .delete-btn {
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
        }

        .delete-btn:hover {
            background: rgba(220, 53, 69, 1);
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
            max-width: 600px;
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
                <li><a href="gallery.php" class="active"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="section-header">
                <h2>Gallery Management</h2>
                <button class="btn btn-primary" onclick="openModal('add')">Add New Item</button>
            </div>

            <div class="filter-section">
                <select class="filter-select" id="categoryFilter">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($gallery_items as $item): ?>
                    <div class="gallery-item" data-category="<?php echo htmlspecialchars($item['category']); ?>">
                        <div class="gallery-image">
                            <?php if ($item['image'] && file_exists('../uploads/' . $item['image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="gallery-overlay">
                                <i class="fas fa-<?php echo $item['video_url'] ? 'play' : 'eye'; ?>"></i>
                            </div>
                        </div>
                        <div class="gallery-actions">
                            <button class="edit-btn" onclick="editGalleryItem(<?php echo $item['id']; ?>)">Edit</button>
                            <button class="delete-btn" onclick="deleteGalleryItem(<?php echo $item['id']; ?>)">Delete</button>
                        </div>
                        <div class="gallery-info">
                            <div class="gallery-title"><?php echo htmlspecialchars($item['title'] ?: 'Gallery Item'); ?></div>
                            <div class="gallery-category"><?php echo htmlspecialchars($item['category'] ?: 'general'); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="galleryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Gallery Item</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="galleryForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="galleryId">

                <div class="form-group">
                    <label for="title">Title (Optional)</label>
                    <input type="text" id="title" name="title" placeholder="Gallery item title">
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="general">General</option>
                        <option value="weddings">Weddings</option>
                        <option value="corporate">Corporate</option>
                        <option value="parties">Parties</option>
                        <option value="manufacturing">Manufacturing</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Image</label>
                    <div class="file-upload">
                        <input type="file" id="image" name="image" accept="image/*" required>
                        <label for="image">
                            <i class="fas fa-image"></i> Choose Image
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="video_url">Video URL (Optional)</label>
                    <input type="url" id="video_url" name="video_url" placeholder="https://youtube.com/...">
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                </div>

                <button type="submit" class="btn btn-primary">Save Gallery Item</button>
            </form>
        </div>
    </div>

    <script>
        function openModal(action, galleryId = null) {
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add New Gallery Item' : 'Edit Gallery Item';
            document.getElementById('galleryModal').classList.add('show');

            if (action === 'edit' && galleryId) {
                document.getElementById('galleryId').value = galleryId;
            } else {
                document.getElementById('galleryForm').reset();
            }
        }

        function closeModal() {
            document.getElementById('galleryModal').classList.remove('show');
        }

        function editGalleryItem(id) {
            // In a real implementation, you'd fetch gallery item data via AJAX
            openModal('edit', id);
        }

        function deleteGalleryItem(id) {
            if (confirm('Are you sure you want to delete this gallery item?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Category filter
        document.getElementById('categoryFilter').addEventListener('change', function() {
            const selectedCategory = this.value;
            const items = document.querySelectorAll('.gallery-item');

            items.forEach(item => {
                if (selectedCategory === '' || item.getAttribute('data-category') === selectedCategory) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Close modal when clicking outside
        document.getElementById('galleryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
