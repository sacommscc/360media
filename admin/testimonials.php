<?php
require_once '../config.php';
requireLogin();

$db = getDB();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add' || $action === 'edit') {
            $name = sanitize($_POST['name']);
            $position = sanitize($_POST['position']);
            $company = sanitize($_POST['company']);
            $content = sanitize($_POST['content']);
            $rating = (int)($_POST['rating'] ?? 5);
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['image'], 'testimonials');
                if (isset($upload_result['success'])) {
                    $image = $upload_result['filename'];
                }
            }

            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO testimonials (name, position, company, content, image, rating, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $position, $company, $content, $image, $rating, $sort_order]);
                $message = 'Testimonial added successfully!';
            } else {
                $id = (int)$_POST['id'];
                $query = "UPDATE testimonials SET name = ?, position = ?, company = ?, content = ?, rating = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP";
                $params = [$name, $position, $company, $content, $rating, $sort_order];

                if ($image) {
                    $query .= ", image = ?";
                    $params[] = $image;
                }

                $query .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $message = 'Testimonial updated successfully!';
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE testimonials SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Testimonial deleted successfully!';
        }
    }
}

// Get all testimonials
$testimonials = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testimonials Management - 360 Media Admin</title>
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

        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .testimonial-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .testimonial-content {
            padding: 20px;
        }

        .testimonial-text {
            font-style: italic;
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .author-info h4 {
            margin-bottom: 5px;
            color: #333;
        }

        .author-info span {
            color: #FFD700;
            font-weight: 500;
        }

        .testimonial-rating {
            color: #FFD700;
            margin-bottom: 10px;
        }

        .testimonial-actions {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .testimonial-actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: #007bff;
            color: #fff;
        }

        .edit-btn:hover {
            background: #0056b3;
        }

        .delete-btn {
            background: #dc3545;
            color: #fff;
        }

        .delete-btn:hover {
            background: #c82333;
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
            min-height: 100px;
        }

        .rating-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .rating-stars {
            display: flex;
            gap: 5px;
        }

        .star {
            color: #ddd;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .star.active {
            color: #FFD700;
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
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php" class="active"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="section-header">
                <h2>Testimonials Management</h2>
                <button class="btn btn-primary" onclick="openModal('add')">Add New Testimonial</button>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-content">
                            <div class="testimonial-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $testimonial['rating'] ? '' : '-half-alt'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="testimonial-text">"<?php echo htmlspecialchars(substr($testimonial['content'], 0, 150)); ?>..."</div>
                            <div class="testimonial-author">
                                <div class="author-info">
                                    <h4><?php echo htmlspecialchars($testimonial['name']); ?></h4>
                                    <span><?php echo htmlspecialchars($testimonial['company'] ?: $testimonial['position']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="testimonial-actions">
                            <button class="edit-btn" onclick="editTestimonial(<?php echo $testimonial['id']; ?>)">Edit</button>
                            <button class="delete-btn" onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>)">Delete</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="testimonialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Testimonial</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="testimonialForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="testimonialId">

                <div class="form-group">
                    <label for="name">Client Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="position">Position</label>
                    <input type="text" id="position" name="position">
                </div>

                <div class="form-group">
                    <label for="company">Company</label>
                    <input type="text" id="company" name="company">
                </div>

                <div class="form-group">
                    <label for="content">Testimonial Content</label>
                    <textarea id="content" name="content" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-input">
                        <div class="rating-stars" id="ratingStars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star star" data-rating="<?php echo $i; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="rating" value="5">
                    </div>
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                </div>

                <div class="form-group">
                    <label for="image">Client Photo (Optional)</label>
                    <div class="file-upload">
                        <input type="file" id="image" name="image" accept="image/*">
                        <label for="image">
                            <i class="fas fa-image"></i> Choose Image
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Testimonial</button>
            </form>
        </div>
    </div>

    <script>
        let currentRating = 5;

        // Rating stars functionality
        document.querySelectorAll('.star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                setRating(rating);
            });
        });

        function setRating(rating) {
            currentRating = rating;
            document.getElementById('rating').value = rating;
            document.querySelectorAll('.star').forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }

        // Initialize rating
        setRating(5);

        function openModal(action, testimonialId = null) {
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add New Testimonial' : 'Edit Testimonial';
            document.getElementById('testimonialModal').classList.add('show');

            if (action === 'edit' && testimonialId) {
                // Load testimonial data (would need AJAX in real implementation)
                document.getElementById('testimonialId').value = testimonialId;
            } else {
                document.getElementById('testimonialForm').reset();
                setRating(5);
            }
        }

        function closeModal() {
            document.getElementById('testimonialModal').classList.remove('show');
        }

        function editTestimonial(id) {
            // In a real implementation, you'd fetch testimonial data via AJAX
            openModal('edit', id);
        }

        function deleteTestimonial(id) {
            if (confirm('Are you sure you want to delete this testimonial?')) {
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

        // Close modal when clicking outside
        document.getElementById('testimonialModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
