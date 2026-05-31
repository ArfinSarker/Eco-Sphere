<?php
require_once '../config/config.php';

$page_title = "Manage Products";

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'includes/header.php';

if($_POST) {
    if(isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $insert_query = "INSERT INTO products (name, description, price, category_id, stock_quantity, featured) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if($insert_stmt->execute([$name, $description, $price, $category_id, $stock_quantity, $featured])) {
            $product_id = $db->lastInsertId();
            
            if(isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                $upload_dir = '../images/products/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                foreach($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = time() . '_' . basename($_FILES['product_images']['name'][$key]);
                        $file_path = $upload_dir . $file_name;
                        
                        if(move_uploaded_file($tmp_name, $file_path)) {
                            $is_primary = ($key === 0) ? 1 : 0;
                            
                            $image_query = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
                            $image_stmt = $db->prepare($image_query);
                            $image_stmt->execute([$product_id, 'images/products/' . $file_name, $is_primary]);
                        }
                    }
                }
            }
            
            $_SESSION['success'] = "Product added successfully!";
            header("Location: products.php");
            exit;
        } else {
            $error = "Failed to add product.";
        }
    }
    
    if(isset($_POST['update_product'])) {
        $id = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $category_id = $_POST['category_id'];
        $stock_quantity = $_POST['stock_quantity'];
        $featured = isset($_POST['featured']) ? 1 : 0;
        
        $update_query = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, stock_quantity = ?, featured = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if($update_stmt->execute([$name, $description, $price, $category_id, $stock_quantity, $featured, $id])) {
            
            if(isset($_FILES['product_images']) && !empty($_FILES['product_images']['name'][0])) {
                $upload_dir = '../images/products/';
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $check_primary_query = "SELECT COUNT(*) as count FROM product_images WHERE product_id = ? AND is_primary = 1";
                $check_primary_stmt = $db->prepare($check_primary_query);
                $check_primary_stmt->execute([$id]);
                $has_primary = $check_primary_stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
                
                foreach($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                    if($_FILES['product_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = time() . '_' . basename($_FILES['product_images']['name'][$key]);
                        $file_path = $upload_dir . $file_name;
                        
                        if(move_uploaded_file($tmp_name, $file_path)) {
                            $is_primary = (!$has_primary && $key === 0) ? 1 : 0;
                            
                            $image_query = "INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)";
                            $image_stmt = $db->prepare($image_query);
                            $image_stmt->execute([$id, 'images/products/' . $file_name, $is_primary]);
                            
                            if($is_primary) {
                                $has_primary = true;
                            }
                        }
                    }
                }
            }
            
            $_SESSION['success'] = "Product updated successfully!";
            header("Location: products.php");
            exit;
        } else {
            $error = "Failed to update product.";
        }
    }
}

if(isset($_GET['delete'])) {
    $delete_query = "DELETE FROM products WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if($delete_stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product.";
    }
    header("Location: products.php");
    exit;
}

if(isset($_GET['delete_image'])) {
    $image_id = $_GET['delete_image'];
    $product_id = $_GET['product_id'];
    
    $image_query = "SELECT image_url FROM product_images WHERE id = ?";
    $image_stmt = $db->prepare($image_query);
    $image_stmt->execute([$image_id]);
    $image = $image_stmt->fetch(PDO::FETCH_ASSOC);
    
    if($image) {
        $file_path = '../' . $image['image_url'];
        if(file_exists($file_path)) {
            unlink($file_path);
        }
        
        $delete_image_query = "DELETE FROM product_images WHERE id = ?";
        $delete_image_stmt = $db->prepare($delete_image_query);
        
        if($delete_image_stmt->execute([$image_id])) {
            $_SESSION['success'] = "Image deleted successfully!";
        } else {
            $error = "Failed to delete image.";
        }
    }
    header("Location: products.php?edit=" . $product_id);
    exit;
}

if(isset($_GET['set_primary'])) {
    $image_id = $_GET['set_primary'];
    $product_id = $_GET['product_id'];
    
    $reset_query = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
    $reset_stmt = $db->prepare($reset_query);
    $reset_stmt->execute([$product_id]);
    
    $primary_query = "UPDATE product_images SET is_primary = 1 WHERE id = ?";
    $primary_stmt = $db->prepare($primary_query);
    
    if($primary_stmt->execute([$image_id])) {
        $_SESSION['success'] = "Primary image updated successfully!";
    } else {
        $error = "Failed to update primary image.";
    }
    header("Location: products.php?edit=" . $product_id);
    exit;
}

$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   ORDER BY p.created_at DESC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute();
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);

$images_query = "SELECT product_id, image_url FROM product_images WHERE is_primary = 1";
$images_stmt = $db->prepare($images_query);
$images_stmt->execute();
$product_images = [];
while($row = $images_stmt->fetch(PDO::FETCH_ASSOC)) {
    $product_images[$row['product_id']] = $row['image_url'];
}

$edit_product = null;
$product_images_list = [];
if(isset($_GET['edit'])) {
    $edit_query = "SELECT * FROM products WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['edit']]);
    $edit_product = $edit_stmt->fetch(PDO::FETCH_ASSOC);
    
    $images_list_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC";
    $images_list_stmt = $db->prepare($images_list_query);
    $images_list_stmt->execute([$_GET['edit']]);
    $product_images_list = $images_list_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
.admin-container {
    display: flex;
    min-height: calc(100vh - 80px);
    background: #f8f9fa;
}

.admin-main {
    flex: 1;
    padding: 2rem;
    background: #f8f9fa;
    overflow-x: auto;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 0;
}

.admin-header h1 {
    color: #1f2937;
    font-weight: 700;
    font-size: 2rem;
    margin: 0;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    border-left: 4px solid;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-card:nth-child(1) { border-left-color: #10b981; }
.stat-card:nth-child(2) { border-left-color: #3b82f6; }
.stat-card:nth-child(3) { border-left-color: #f59e0b; }
.stat-card:nth-child(4) { border-left-color: #ef4444; }
.stat-card:nth-child(5) { 
    border-left-color: #8b5cf6;
    background: linear-gradient(135deg, #8b5cf6, #a855f7);
    color: white;
    cursor: pointer;
}

.stat-card:nth-child(5):hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
}

.stat-card:nth-child(5) .stat-info h3,
.stat-card:nth-child(5) .stat-info p {
    color: white;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.stat-card:nth-child(5) .stat-icon {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
}

.stat-info h3 {
    font-size: 2rem;
    margin: 0;
    color: #1f2937;
    font-weight: 700;
}

.stat-info p {
    margin: 0;
    color: #6b7280;
    font-weight: 500;
}

.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    margin: 0;
    color: #1f2937;
    font-weight: 600;
}

.search-input {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    width: 300px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.data-table th {
    background: #f9fafb;
    font-weight: 600;
    color: #374151;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table tr:hover {
    background: #f9fafb;
}

.product-thumb {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.product-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.product-info .text-muted {
    color: #6b7280;
    font-size: 0.875rem;
    margin: 0;
}

.category-badge {
    background: #d1fae5;
    color: #065f46;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}

.price {
    color: #059669;
    font-size: 1.1rem;
    font-weight: 700;
}

.stock-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.stock-badge {
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    text-align: center;
    width: fit-content;
}

.stock-badge.in-stock {
    background: #d1fae5;
    color: #065f46;
}

.stock-badge.low-stock {
    background: #fef3c7;
    color: #92400e;
}

.stock-badge.out-of-stock {
    background: #fee2e2;
    color: #991b1b;
}

.text-warning {
    color: #d97706;
    font-size: 0.75rem;
    font-weight: 500;
}

.text-danger {
    color: #dc2626;
    font-size: 0.75rem;
    font-weight: 500;
}

.badge {
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-success {
    background: #d1fae5;
    color: #065f46;
}

.badge-secondary {
    background: #f3f4f6;
    color: #6b7280;
}

.status-badge {
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #f3f4f6;
    color: #6b7280;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-sm {
    padding: 0.5rem;
    border-radius: 6px;
    font-size: 0.8rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: none;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-info {
    background: #06b6d4;
    color: white;
    border: none;
}

.btn-info:hover {
    background: #0891b2;
}

.empty-state {
    padding: 3rem;
    text-align: center;
    color: #6b7280;
}

.empty-state i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.empty-state p {
    margin-bottom: 1.5rem;
}

.text-center {
    text-align: center;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}

.modal-content {
    background-color: white;
    margin: 2% auto;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
    animation: modalSlideIn 0.3s ease;
}

.modal-content.large {
    width: 90%;
    max-width: 800px;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #8b5cf6, #a855f7);
    color: white;
    border-radius: 12px 12px 0 0;
}

.modal-header h2 {
    margin: 0;
    font-weight: 600;
}

.close {
    color: white;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    transition: color 0.2s ease;
}

.close:hover {
    color: #f1f5f9;
}

.modal-body {
    padding: 1.5rem;
    max-height: 70vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #374151;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus {
    outline: none;
    border-color: #8b5cf6;
    box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.file-upload-area {
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 2rem;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

.file-upload-area:hover {
    border-color: #8b5cf6;
    background-color: #f8fafc;
}

.file-upload-area input {
    display: none;
}

.upload-placeholder i {
    font-size: 2rem;
    color: #9ca3af;
    margin-bottom: 1rem;
}

.upload-placeholder p {
    margin: 0 0 0.5rem 0;
    color: #374151;
    font-weight: 500;
}

.upload-placeholder small {
    color: #6b7280;
}

.image-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.preview-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.preview-item img {
    width: 100%;
    height: 120px;
    object-fit: cover;
}

.remove-image {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.current-images {
    margin-top: 1.5rem;
}

.current-images h4 {
    margin-bottom: 1rem;
    color: #374151;
}

.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.image-item {
    position: relative;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.image-item img {
    width: 100%;
    height: 150px;
    object-fit: cover;
}

.image-actions {
    padding: 0.75rem;
    background: rgba(0,0,0,0.8);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.image-actions .btn-sm {
    padding: 0.4rem 0.75rem;
    font-size: 0.7rem;
    width: 100%;
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 0.75rem;
}

.checkbox-label input {
    display: none;
}

.checkmark {
    width: 20px;
    height: 20px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.checkbox-label input:checked + .checkmark {
    background: #8b5cf6;
    border-color: #8b5cf6;
}

.checkbox-label input:checked + .checkmark::after {
    content: '✓';
    color: white;
    font-size: 0.8rem;
    font-weight: bold;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.new-product-btn {
    background: linear-gradient(135deg, #8b5cf6, #a855f7);
    color: white;
    border: none;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(139, 92, 246, 0.3);
}

.new-product-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
    background: linear-gradient(135deg, #7c3aed, #9333ea);
}

.new-product-btn i {
    font-size: 1.2rem;
}

@media (max-width: 1024px) {
    .admin-main {
        padding: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content.large {
        width: 95%;
        margin: 5% auto;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .search-input {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .modal-body {
        padding: 1rem;
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .data-table {
        font-size: 0.8rem;
    }
    
    .data-table th,
    .data-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3 style="padding: 0 1.5rem; margin-bottom: 1rem;">Admin Panel</h3>
        <ul>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php" class="active"><i class="fas fa-tree"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="order-history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <div class="admin-header">
            <h1>Manage Products</h1>
            <button onclick="showAddProductModal()" class="new-product-btn">
                <i class="fas fa-plus-circle"></i>
                New Product
            </button>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b981;">
                    <i class="fas fa-tree"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo count($products); ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #3b82f6;">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo array_reduce($products, function($carry, $item) { return $carry + ($item['featured'] ? 1 : 0); }, 0); ?></h3>
                    <p>Featured Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo array_reduce($products, function($carry, $item) { return $carry + $item['stock_quantity']; }, 0); ?></h3>
                    <p>Total Stock</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #ef4444;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo array_reduce($products, function($carry, $item) { return $carry + ($item['stock_quantity'] == 0 ? 1 : 0); }, 0); ?></h3>
                    <p>Out of Stock</p>
                </div>
            </div>
            
            <div class="stat-card" onclick="showAddProductModal()">
                <div class="stat-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="stat-info">
                    <h3>New Product</h3>
                    <p>Add New Item</p>
                </div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <h3>All Products</h3>
                <div class="table-actions">
                    <input type="text" id="searchProducts" placeholder="Search products..." class="search-input">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($products)): ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-tree"></i>
                                        <h3>No products found</h3>
                                        <p>Get started by adding your first product</p>
                                        <button onclick="showAddProductModal()" class="new-product-btn" style="display: inline-flex;">
                                            <i class="fas fa-plus-circle"></i>
                                            New Product
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($products as $product): ?>
                                <tr>
                                    <td>
                                        <img src="../<?php echo $product_images[$product['id']] ?? 'images/placeholder.jpg'; ?>" 
                                             alt="<?php echo $product['name']; ?>" 
                                             class="product-thumb">
                                    </td>
                                    <td>
                                        <div class="product-info">
                                            <h4><?php echo $product['name']; ?></h4>
                                            <p class="text-muted"><?php echo substr($product['description'], 0, 100) . '...'; ?></p>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="category-badge"><?php echo $product['category_name']; ?></span>
                                    </td>
                                    <td>
                                        <strong class="price">$<?php echo number_format($product['price'], 2); ?></strong>
                                    </td>
                                    <td>
                                        <div class="stock-info">
                                            <span class="stock-badge <?php echo $product['stock_quantity'] > 10 ? 'in-stock' : ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-of-stock'); ?>">
                                                <?php echo $product['stock_quantity']; ?>
                                            </span>
                                            <?php if($product['stock_quantity'] <= 10 && $product['stock_quantity'] > 0): ?>
                                                <small class="text-warning">Low stock</small>
                                            <?php elseif($product['stock_quantity'] == 0): ?>
                                                <small class="text-danger">Out of stock</small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($product['featured']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-star"></i> Featured
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Regular</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['stock_quantity'] > 0 ? 'active' : 'inactive'; ?>">
                                            <?php echo $product['stock_quantity'] > 0 ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button onclick="editProduct(<?php echo $product['id']; ?>)" class="btn-sm btn-outline" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="products.php?delete=<?php echo $product['id']; ?>" class="btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <a href="../product-details.php?id=<?php echo $product['id']; ?>" class="btn-sm btn-info" title="View" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
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

<div id="productModal" class="modal" style="display: none;">
    <div class="modal-content large">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Product</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        
        <form method="post" enctype="multipart/form-data" id="productForm">
            <div class="modal-body">
                <input type="hidden" name="product_id" id="product_id">
                <input type="hidden" name="update_product" id="update_product" value="0">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Product Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required 
                               placeholder="Enter product name">
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="5" required 
                              placeholder="Enter product description"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required 
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label for="stock_quantity">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" class="form-control" min="0" required 
                               placeholder="Enter stock quantity">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Product Images</label>
                    <div class="file-upload-area" id="uploadArea" onclick="document.getElementById('product_images').click()">
                        <div class="upload-placeholder" id="uploadPlaceholder">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload product images</p>
                            <small>Supported formats: JPG, PNG, GIF. Max 5MB per image.</small>
                        </div>
                        <input type="file" id="product_images" name="product_images[]" multiple accept="image/*" onchange="previewImages(this)">
                    </div>
                    <div id="imagePreview" class="image-preview-grid"></div>
                    
                    <?php if(isset($edit_product) && !empty($product_images_list)): ?>
                        <div class="current-images">
                            <h4>Current Images</h4>
                            <div class="image-grid">
                                <?php foreach($product_images_list as $image): ?>
                                    <div class="image-item">
                                        <img src="../<?php echo $image['image_url']; ?>" alt="Product Image">
                                        <div class="image-actions">
                                            <?php if($image['is_primary']): ?>
                                                <span class="badge badge-success">Primary</span>
                                            <?php else: ?>
                                                <a href="products.php?set_primary=<?php echo $image['id']; ?>&product_id=<?php echo $edit_product['id']; ?>" class="btn-sm btn-outline">Set Primary</a>
                                            <?php endif; ?>
                                            <a href="products.php?delete_image=<?php echo $image['id']; ?>&product_id=<?php echo $edit_product['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="featured" id="featured" value="1">
                        <span class="checkmark"></span>
                        Featured Product
                    </label>
                    <small style="color: #6b7280; display: block; margin-top: 0.5rem;">
                        Featured products will be highlighted on the homepage
                    </small>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                <button type="submit" name="add_product" class="new-product-btn" id="submitBtn" style="border: none;">
                    <i class="fas fa-plus-circle"></i>
                    Add Product
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddProductModal() {
    document.getElementById('modalTitle').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('update_product').value = '0';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus-circle"></i> Add Product';
    document.getElementById('submitBtn').name = 'add_product';
    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('uploadPlaceholder').style.display = 'block';
    document.getElementById('productModal').style.display = 'block';
    
    const form = document.getElementById('productForm');
    form.classList.remove('was-validated');
}

function editProduct(productId) {
    window.location.href = 'products.php?edit=' + productId;
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

function previewImages(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('uploadPlaceholder');
    preview.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        placeholder.style.display = 'none';
        
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'preview-item';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Preview ' + (index + 1);
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'remove-image';
                removeBtn.innerHTML = '&times;';
                removeBtn.title = 'Remove image';
                removeBtn.onclick = function() {
                    imgContainer.remove();
                    const dt = new DataTransfer();
                    const files = Array.from(input.files);
                    files.splice(index, 1);
                    files.forEach(file => dt.items.add(file));
                    input.files = dt.files;
                    
                    if (input.files.length === 0) {
                        placeholder.style.display = 'block';
                    }
                };
                
                imgContainer.appendChild(img);
                imgContainer.appendChild(removeBtn);
                preview.appendChild(imgContainer);
            }
            reader.readAsDataURL(file);
        });
    }
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    const form = e.target;
    if (!form.checkValidity()) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.add('was-validated');
        
        const firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
        }
    }
});

window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target == modal) {
        closeModal();
    }
}

<?php if($edit_product): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('product_id').value = '<?php echo $edit_product["id"]; ?>';
    document.getElementById('update_product').value = '1';
    document.getElementById('name').value = '<?php echo addslashes($edit_product["name"]); ?>';
    document.getElementById('description').value = '<?php echo addslashes($edit_product["description"]); ?>';
    document.getElementById('price').value = '<?php echo $edit_product["price"]; ?>';
    document.getElementById('stock_quantity').value = '<?php echo $edit_product["stock_quantity"]; ?>';
    document.getElementById('category_id').value = '<?php echo $edit_product["category_id"]; ?>';
    document.getElementById('featured').checked = <?php echo $edit_product['featured'] ? 'true' : 'false'; ?>;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i> Update Product';
    document.getElementById('submitBtn').name = 'update_product';
    document.getElementById('productModal').style.display = 'block';
});
<?php endif; ?>

document.getElementById('searchProducts').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        if (row.querySelector('.product-info h4')) {
            const productName = row.querySelector('.product-info h4').textContent.toLowerCase();
            const productDesc = row.querySelector('.product-info .text-muted').textContent.toLowerCase();
            const category = row.querySelector('.category-badge').textContent.toLowerCase();
            
            if (productName.includes(searchTerm) || productDesc.includes(searchTerm) || category.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });
});

const uploadArea = document.getElementById('uploadArea');
if (uploadArea) {
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.style.borderColor = '#8b5cf6';
        this.style.backgroundColor = '#f8fafc';
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.backgroundColor = '';
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.style.borderColor = '#d1d5db';
        this.style.backgroundColor = '';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('product_images').files = files;
            previewImages(document.getElementById('product_images'));
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>