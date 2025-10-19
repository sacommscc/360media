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
            $description = sanitize($_POST['description']);
            $features = json_encode(array_map('sanitize', $_POST['features'] ?? []));
            $price_range = sanitize($_POST['price_range']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $sort_order = (int)($_POST['sort_order'] ?? 0);

            // Handle image upload
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_result = uploadImage($_FILES['image'], 'products');
                if (isset($upload_result['success'])) {
                    $image = $upload_result['filename'];
                }
            }

            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO products (name, description, image, features, price_range, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $image, $features, $price_range, $is_featured, $sort_order]);
                $message = 'Product added successfully!';
            } else {
                $id = (int)$_POST['id'];
                $query = "UPDATE products SET name = ?, description = ?, features = ?, price_range = ?, is_featured = ?, sort_order = ?, updated_at = CURRENT_TIMESTAMP";
                $params = [$name, $description, $features, $price_range, $is_featured, $sort_order];

                if ($image) {
                    $query .= ", image = ?";
                    $params[] = $image;
                }

                $query .= " WHERE id = ?";
                $params[] = $id;

                $stmt = $db->prepare($query);
                $stmt->execute($params);
                $message = 'Product updated successfully!';
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Product deleted successfully!';
        } elseif ($action === 'toggle_featured') {
            $id = (int)$_POST['id'];
            $stmt = $db->prepare("UPDATE products SET is_featured = NOT is_featured WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Product featured status updated!';
        }
    }
}

// Get all products
$products = $db->query("SELECT * FROM products WHERE is_active = 1 ORDER BY sort_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - 360 Media Admin</title>
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

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .product-image {
            height: 200px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .product-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }

        .product-image.placeholder {
            color: #666;
            font-size: 3rem;
        }

        .featured-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #FFD700;
            color: #000;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .product-info {
            padding: 20px;
        }

        .product-name {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .product-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .product-actions button {
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

        .featured-btn {
            background: #FFD700;
            color: #000;
        }

        .featured-btn:hover {
            background: #FFA500;
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

        .features-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .features-list {
            max-height: 150px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }

        .feature-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }

        .feature-item:last-child {
            border-bottom: none;
        }

        .remove-feature {
            color: #dc3545;
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
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
                <li><a href="products.php" class="active"><i class="fas fa-box"></i> Products</a></li>
                <li><a href="experience.php"><i class="fas fa-star"></i> Experience</a></li>
                <li><a href="gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
                <li><a href="testimonials.php"><i class="fas fa-comments"></i> Testimonials</a></li>
                <li><a href="partners.php"><i class="fas fa-handshake"></i> Partners</a></li>
                <li><a href="contact.php"><i class="fas fa-envelope"></i> Contact Info</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="section-header">
                <h2>Products Management</h2>
                <button class="btn btn-primary" onclick="openModal('add')">Add New Product</button>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image'] && file_exists('../uploads/' . $product['image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="placeholder">
                                    <i class="fas fa-box"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($product['is_featured']): ?>
                                <div class="featured-badge">Featured</div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</div>
                            <div class="product-actions">
                                <button class="edit-btn" onclick="editProduct(<?php echo $product['id']; ?>)">Edit</button>
                                <button class="featured-btn" onclick="toggleFeatured(<?php echo $product['id']; ?>)">
                                    <?php echo $product['is_featured'] ? 'Unfeature' : 'Feature'; ?>
                                </button>
                                <button class="delete-btn" onclick="deleteProduct(<?php echo $product['id']; ?>)">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Product</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">

                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label for="image">Product Image</label>
                    <div class="file-upload">
                        <input type="file" id="image" name="image" accept="image/*">
                        <label for="image">
                            <i class="fas fa-image"></i> Choose Image
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Features</label>
                    <div class="features-input">
                        <input type="text" id="featureInput" placeholder="Add a feature">
                        <button type="button" class="btn btn-secondary" onclick="addFeature()">Add</button>
                    </div>
                    <div class="features-list" id="featuresList"></div>
                    <input type="hidden" name="features[]" id="featuresData">
                </div>

                <div class="form-group">
                    <label for="price_range">Price Range</label>
                    <input type="text" id="price_range" name="price_range" placeholder="e.g., $5,000 - $10,000">
                </div>

                <div class="form-group">
                    <label for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" value="0" min="0">
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured">
                        <label for="is_featured">Featured Product</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Product</button>
            </form>
        </div>
    </div>

    <script>
        let features = [];

        function openModal(action, productId = null) {
            document.getElementById('formAction').value = action;
            document.getElementById('modalTitle').textContent = action === 'add' ? 'Add New Product' : 'Edit Product';
            document.getElementById('productModal').classList.add('show');

            if (action === 'edit' && productId) {
                // Load product data (would need AJAX in real implementation)
                document.getElementById('productId').value = productId;
            } else {
                document.getElementById('productForm').reset();
                features = [];
                updateFeaturesList();
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('show');
        }

        function addFeature() {
            const input = document.getElementById('featureInput');
            const feature = input.value.trim();
            if (feature) {
                features.push(feature);
                input.value = '';
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
                    <span>${feature}</span>
                    <span class="remove-feature" onclick="removeFeature(${index})">&times;</span>
                </div>`
            ).join('');

            data.value = JSON.stringify(features);
        }

        function editProduct(id) {
            // In a real implementation, you'd fetch product data via AJAX
            openModal('edit', id);
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
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

        function toggleFeatured(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="toggle_featured">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
