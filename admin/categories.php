<?php
require_once '../config/config.php';

$page_title = "Manage Categories";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Handle form actions
if($_POST) {
    if(isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $insert_query = "INSERT INTO categories (name, description) VALUES (?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if($insert_stmt->execute([$name, $description])) {
            $_SESSION['success'] = "Category added successfully!";
            header("Location: categories.php");
            exit;
        } else {
            $error = "Failed to add category.";
        }
    }
    
    if(isset($_POST['update_category'])) {
        $id = $_POST['category_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        
        $update_query = "UPDATE categories SET name = ?, description = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if($update_stmt->execute([$name, $description, $id])) {
            $_SESSION['success'] = "Category updated successfully!";
            header("Location: categories.php");
            exit;
        } else {
            $error = "Failed to update category.";
        }
    }
}

// Handle delete action
if(isset($_GET['delete'])) {
    // Check if category has products
    $check_query = "SELECT COUNT(*) FROM products WHERE category_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$_GET['delete']]);
    $product_count = $check_stmt->fetchColumn();
    
    if($product_count > 0) {
        $_SESSION['error'] = "Cannot delete category. There are products associated with this category.";
    } else {
        $delete_query = "DELETE FROM categories WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if($delete_stmt->execute([$_GET['delete']])) {
            $_SESSION['success'] = "Category deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete category.";
        }
    }
    header("Location: categories.php");
    exit;
}

// Get all categories
$categories_query = "SELECT c.*, COUNT(p.id) as product_count 
                     FROM categories c 
                     LEFT JOIN products p ON c.id = p.category_id 
                     GROUP BY c.id 
                     ORDER BY c.name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific category for editing
$edit_category = null;
if(isset($_GET['edit'])) {
    $edit_query = "SELECT * FROM categories WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['edit']]);
    $edit_category = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}

// Calculate statistics
$total_categories = count($categories);
$total_products = array_reduce($categories, function($carry, $item) { 
    return $carry + $item['product_count']; 
}, 0);
$most_products = max(array_column($categories, 'product_count'));
$empty_categories = array_reduce($categories, function($carry, $item) { 
    return $carry + ($item['product_count'] == 0 ? 1 : 0); 
}, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EcoSphere Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: #2e7d32;
            --light-green: #4caf50;
            --dark-green: #1b5e20;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #424242;
            --white: #ffffff;
            --error: #d32f2f;
            --warning: #ff9800;
            --success: #388e3c;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            width: 250px;
            background: var(--dark-green);
            color: var(--white);
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            z-index: 100;
        }

        .admin-sidebar h3 {
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 1.2rem;
        }

        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }

        .admin-sidebar li {
            margin: 0;
        }

        .admin-sidebar a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .admin-sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
        }

        .admin-sidebar a.active {
            background: rgba(255, 255, 255, 0.15);
            color: var(--white);
            border-left: 3px solid var(--light-green);
        }

        .admin-sidebar i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .admin-main {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            color: var(--dark-green);
            font-weight: 600;
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-green);
            color: var(--white);
        }

        .btn-primary:hover {
            background: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-outline {
            background: transparent;
            color: var(--primary-green);
            border: 1px solid var(--primary-green);
        }

        .btn-outline:hover {
            background: rgba(46, 125, 50, 0.1);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }

        .btn-danger {
            background: var(--error);
            color: var(--white);
        }

        .btn-danger:hover {
            background: #b71c1c;
        }

        /* Alert Styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: var(--radius);
            font-weight: 500;
        }

        .alert-success {
            background: #e8f5e9;
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-error {
            background: #ffebee;
            color: var(--error);
            border-left: 4px solid var(--error);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--white);
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.25rem;
            color: var(--dark-gray);
        }

        .stat-info p {
            color: var(--dark-gray);
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Table Styles */
        .table-card {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--medium-gray);
        }

        .table-header h3 {
            color: var(--dark-gray);
            font-weight: 600;
        }

        .search-input {
            padding: 0.75rem 1rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--radius);
            width: 250px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.2);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: var(--dark-gray);
            border-bottom: 1px solid var(--medium-gray);
        }

        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--medium-gray);
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background: rgba(46, 125, 50, 0.03);
        }

        .category-info h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--dark-gray);
        }

        .text-muted {
            color: #757575;
        }

        .count-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .count-badge.has-products {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .count-badge.no-products {
            background: #f5f5f5;
            color: #757575;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-active {
            background: #e8f5e8;
            color: #2e7d32;
        }

        .status-inactive {
            background: #fff3e0;
            color: #ef6c00;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .text-center {
            text-align: center;
        }

        .empty-state {
            padding: 3rem 1rem;
            text-align: center;
            color: #757575;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--medium-gray);
        }

        .empty-state h3 {
            margin-bottom: 0.5rem;
            color: var(--dark-gray);
        }

        /* Modal Styles */
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

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--medium-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            color: var(--dark-gray);
            font-weight: 600;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: #757575;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--dark-gray);
        }

        .modal-body {
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-gray);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--medium-gray);
            border-radius: var(--radius);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 2px rgba(46, 125, 50, 0.2);
        }

        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--medium-gray);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .admin-container {
                flex-direction: column;
            }
            
            .admin-sidebar {
                width: 100%;
                height: auto;
            }
            
            .admin-sidebar ul {
                display: flex;
                overflow-x: auto;
            }
            
            .admin-sidebar li {
                flex-shrink: 0;
            }
            
            .admin-sidebar a {
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .admin-sidebar a.active {
                border-left: none;
                border-bottom: 3px solid var(--light-green);
            }
        }

        @media (max-width: 768px) {
            .admin-main {
                padding: 1rem;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .search-input {
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <h3>Admin Panel</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
                <li><a href="categories.php" class="active"><i class="fas fa-tags"></i> Categories</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
                <li><a href="order-history.php"><i class="fas fa-history"></i> Order History</a></li>
                <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
                <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
                <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
                <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h1>Manage Categories</h1>
                <button onclick="showAddCategoryModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Category
                </button>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if(isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-error">
                    <p><?php echo $error; ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Categories Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" style="background: var(--primary-green);">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_categories; ?></h3>
                        <p>Total Categories</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #2196F3;">
                        <i class="fas fa-tree"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_products; ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #4CAF50;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $most_products; ?></h3>
                        <p>Most Products</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: #FF9800;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $empty_categories; ?></h3>
                        <p>Empty Categories</p>
                    </div>
                </div>
            </div>
            
            <!-- Categories Table -->
            <div class="table-card">
                <div class="table-header">
                    <h3>All Categories</h3>
                    <div class="table-actions">
                        <input type="text" id="searchCategories" placeholder="Search categories..." class="search-input">
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <div class="empty-state">
                                            <i class="fas fa-tags" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                                            <h3>No categories found</h3>
                                            <p>Get started by adding your first category</p>
                                            <button onclick="showAddCategoryModal()" class="btn btn-primary">
                                                <i class="fas fa-plus"></i> Add New Category
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($categories as $category): ?>
                                    <tr>
                                        <td>
                                            <div class="category-info">
                                                <h4><?php echo htmlspecialchars($category['name']); ?></h4>
                                            </div>
                                        </td>
                                        <td>
                                            <p class="text-muted"><?php echo $category['description'] ? htmlspecialchars($category['description']) : 'No description'; ?></p>
                                        </td>
                                        <td>
                                            <div class="product-count">
                                                <span class="count-badge <?php echo $category['product_count'] > 0 ? 'has-products' : 'no-products'; ?>">
                                                    <?php echo $category['product_count']; ?> products
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $category['product_count'] > 0 ? 'active' : 'inactive'; ?>">
                                                <?php echo $category['product_count'] > 0 ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="editCategory(<?php echo $category['id']; ?>)" class="btn btn-sm btn-outline" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if($category['product_count'] == 0): ?>
                                                    <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-danger" title="Cannot delete - has products" disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Category Modal -->
    <div id="categoryModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Category</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            
            <form method="post" id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" name="category_id" id="category_id">
                    
                    <div class="form-group">
                        <label for="name">Category Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4" placeholder="Enter category description..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Add Category</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function showAddCategoryModal() {
            document.getElementById('modalTitle').textContent = 'Add New Category';
            document.getElementById('categoryForm').reset();
            document.getElementById('category_id').value = '';
            document.getElementById('submitBtn').textContent = 'Add Category';
            
            // Update form action
            const form = document.getElementById('categoryForm');
            // Remove any existing hidden input for update
            const existingUpdateInput = document.querySelector('input[name="update_category"]');
            if (existingUpdateInput) {
                existingUpdateInput.remove();
            }
            // Add hidden input for add
            if (!document.querySelector('input[name="add_category"]')) {
                const addInput = document.createElement('input');
                addInput.type = 'hidden';
                addInput.name = 'add_category';
                addInput.value = '1';
                form.appendChild(addInput);
            }
            
            document.getElementById('categoryModal').style.display = 'flex';
        }

        function editCategory(categoryId) {
            window.location.href = 'categories.php?edit=' + categoryId;
        }

        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // If editing, populate form
        <?php if($edit_category): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('modalTitle').textContent = 'Edit Category';
            document.getElementById('category_id').value = '<?php echo $edit_category["id"]; ?>';
            document.getElementById('name').value = '<?php echo addslashes($edit_category["name"]); ?>';
            document.getElementById('description').value = '<?php echo addslashes($edit_category["description"]); ?>';
            document.getElementById('submitBtn').textContent = 'Update Category';
            
            // Update form action
            const form = document.getElementById('categoryForm');
            // Remove any existing hidden input for add
            const existingAddInput = document.querySelector('input[name="add_category"]');
            if (existingAddInput) {
                existingAddInput.remove();
            }
            // Add hidden input for update
            if (!document.querySelector('input[name="update_category"]')) {
                const updateInput = document.createElement('input');
                updateInput.type = 'hidden';
                updateInput.name = 'update_category';
                updateInput.value = '1';
                form.appendChild(updateInput);
            }
            
            document.getElementById('categoryModal').style.display = 'flex';
        });
        <?php endif; ?>

        // Search functionality
        document.getElementById('searchCategories').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.data-table tbody tr');
            
            rows.forEach(row => {
                const categoryName = row.querySelector('.category-info h4').textContent.toLowerCase();
                const categoryDesc = row.querySelector('.text-muted').textContent.toLowerCase();
                
                if (categoryName.includes(searchTerm) || categoryDesc.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>