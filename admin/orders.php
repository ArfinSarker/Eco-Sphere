<?php
require_once '../config/config.php';

$page_title = "Manage Orders";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'includes/header.php';

// Handle status update with stock management
if(isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    $old_status = $_POST['old_status'];
    
    // Direct status update without verification
    updateOrderStatus($order_id, $new_status, $old_status);
}

function updateOrderStatus($order_id, $new_status, $old_status) {
    global $db;
    
    $update_query = "UPDATE orders SET status = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$new_status, $order_id])) {
        
        // If status changed to 'completed', reduce stock
        if($new_status == 'completed' && $old_status != 'completed') {
            // Get order items
            $items_query = "SELECT * FROM order_items WHERE order_id = ?";
            $items_stmt = $db->prepare($items_query);
            $items_stmt->execute([$order_id]);
            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update stock for each product
            foreach($order_items as $item) {
                $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND stock_quantity >= ?";
                $update_stock_stmt = $db->prepare($update_stock_query);
                $update_stock_stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                
                if($update_stock_stmt->rowCount() == 0) {
                    $_SESSION['error'] = "Insufficient stock for some products. Order status updated but stock not reduced.";
                    break;
                }
            }
            
            $_SESSION['success'] = "Order status updated to completed and stock quantities reduced!";
        } else {
            $_SESSION['success'] = "Order status updated successfully!";
        }
    } else {
        $_SESSION['error'] = "Failed to update order status.";
    }
    header("Location: orders.php");
    exit;
}

// Handle delete action
if(isset($_GET['delete'])) {
    $delete_query = "DELETE FROM orders WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if($delete_stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = "Order deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete order.";
    }
    header("Location: orders.php");
    exit;
}

// Handle quick status update
if(isset($_POST['quick_update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $current_status_query = "SELECT status FROM orders WHERE id = ?";
    $current_status_stmt = $db->prepare($current_status_query);
    $current_status_stmt->execute([$order_id]);
    $current_status = $current_status_stmt->fetch(PDO::FETCH_ASSOC);
    
    updateOrderStatus($order_id, $new_status, $current_status['status']);
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filter
$where_conditions = [];
$params = [];

if($status_filter != 'all') {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get orders with detailed information
$orders_query = "SELECT o.*, u.username, 
                        COUNT(oi.id) as item_count,
                        SUM(oi.quantity) as total_quantity
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 $where_clause 
                 GROUP BY o.id 
                 ORDER BY 
                    CASE o.status
                        WHEN 'pending' THEN 1
                        WHEN 'processing' THEN 2
                        WHEN 'delivered' THEN 3
                        WHEN 'completed' THEN 4
                        WHEN 'cancelled' THEN 5
                        ELSE 6
                    END,
                    o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filter
$counts_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$counts_stmt = $db->prepare($counts_query);
$counts_stmt->execute();
$status_counts = [
    'all' => 0,
    'pending' => 0,
    'processing' => 0,
    'delivered' => 0,
    'completed' => 0,
    'cancelled' => 0
];

while($row = $counts_stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = $row['count'];
    $status_counts['all'] += $row['count'];
}

// Get revenue statistics
$revenue_query = "SELECT 
                    SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'pending' THEN total ELSE 0 END) as pending_revenue,
                    COUNT(*) as total_orders
                  FROM orders";
$revenue_stmt = $db->prepare($revenue_query);
$revenue_stmt->execute();
$revenue_stats = $revenue_stmt->fetch(PDO::FETCH_ASSOC);
?>

<style>
.admin-container {
    display: flex;
    min-height: calc(100vh - 80px);
    background: #f8f9fa;
}

.admin-sidebar {
    width: 250px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
}

.admin-sidebar h3 {
    padding: 0 1.5rem;
    margin-bottom: 1.5rem;
    font-size: 1.3rem;
    font-weight: 700;
}

.admin-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.admin-sidebar li {
    margin-bottom: 0.5rem;
}

.admin-sidebar a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.admin-sidebar a:hover,
.admin-sidebar a.active {
    background: rgba(255,255,255,0.1);
    border-left-color: white;
}

.admin-sidebar i {
    width: 20px;
    text-align: center;
}

.admin-main {
    flex: 1;
    padding: 2rem;
    background: #f8f9fa;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e9ecef;
}

.admin-header h1 {
    color: #2c3e50;
    margin: 0;
    font-weight: 700;
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
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.stat-card:nth-child(1) { border-left-color: #10b981; }
.stat-card:nth-child(2) { border-left-color: #f59e0b; }
.stat-card:nth-child(3) { border-left-color: #3b82f6; }
.stat-card:nth-child(4) { border-left-color: #ef4444; }

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    margin: 0.5rem 0;
    color: #1f2937;
}

.stat-label {
    color: #6b7280;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.filter-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1rem;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    text-decoration: none;
    color: #6b7280;
    font-weight: 600;
    transition: all 0.2s ease;
    background: white;
}

.filter-btn:hover,
.filter-btn.active {
    background: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.order-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.order-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.order-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.order-meta {
    flex: 1;
}

.order-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.order-customer {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0;
}

.order-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending { background: #fef3c7; color: #d97706; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-delivered { background: #d1fae5; color: #065f46; }
.status-completed { background: #10b981; color: white; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

.order-body {
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 2rem;
}

.order-section h4 {
    color: #374151;
    margin-bottom: 1rem;
    font-size: 1rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.order-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.order-item {
    display: flex;
    justify-content: space-between;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.order-item:last-child {
    border-bottom: none;
}

.order-footer {
    padding: 1.5rem;
    background: #f9fafb;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-summary {
    color: #6b7280;
    font-size: 0.9rem;
}

.order-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-outline {
    background: white;
    border: 2px solid #3b82f6;
    color: #3b82f6;
}

.btn-outline:hover {
    background: #3b82f6;
    color: white;
}

.btn-primary {
    background: #3b82f6;
    color: white;
    border: 2px solid #3b82f6;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.btn-success {
    background: #10b981;
    color: white;
    border: 2px solid #10b981;
}

.btn-success:hover {
    background: #059669;
    border-color: #059669;
}

.btn-warning {
    background: #f59e0b;
    color: white;
    border: 2px solid #f59e0b;
}

.btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
}

.btn-danger {
    background: #ef4444;
    color: white;
    border: 2px solid #ef4444;
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
}

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.status-form {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.status-select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    min-width: 120px;
}

.status-btn {
    padding: 0.5rem 1rem;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
}

.status-btn:hover {
    background: #2563eb;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.empty-icon {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1.5rem;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border-left: 4px solid;
}

.alert-success {
    background: #d1fae5;
    border-color: #10b981;
    color: #065f46;
}

.alert-error {
    background: #fee2e2;
    border-color: #ef4444;
    color: #dc2626;
}

.payment-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.payment-cash { background: #e5e7eb; color: #374151; }
.payment-bkash { background: #e20074; color: white; }
.payment-nagad { background: #f8a61c; color: white; }
.payment-card { background: #3b82f6; color: white; }

/* Fix for status update form */
.auto-submit-form {
    display: inline;
}

.auto-submit-form select {
    padding: 0.5rem;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: white;
    color: #374151;
    font-size: 0.875rem;
    cursor: pointer;
    transition: border-color 0.2s ease;
}

.auto-submit-form select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

@media (max-width: 1024px) {
    .order-body {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .order-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
    }
}

@media (max-width: 768px) {
    .order-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .status-form {
        justify-content: space-between;
    }
}
</style>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3>Admin Panel</h3>
        <ul>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Orders</a></li>
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
            <h1>Order Management</h1>
            <div style="display: flex; align-items: center; gap: 1rem;">
                <span style="background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600;">
                    Revenue: $<?php echo number_format($revenue_stats['total_revenue'] ?? 0, 2); ?>
                </span>
            </div>
        </div>
        
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
        
        <!-- Order Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo $status_counts['all']; ?></div>
                <div style="color: #10b981; font-size: 0.875rem;">
                    <i class="fas fa-chart-line"></i> All orders
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-value"><?php echo $status_counts['pending']; ?></div>
                <div style="color: #f59e0b; font-size: 0.875rem;">
                    <i class="fas fa-clock"></i> Awaiting processing
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Processing</div>
                <div class="stat-value"><?php echo $status_counts['processing']; ?></div>
                <div style="color: #3b82f6; font-size: 0.875rem;">
                    <i class="fas fa-cog"></i> In progress
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Completed</div>
                <div class="stat-value"><?php echo $status_counts['completed']; ?></div>
                <div style="color: #ef4444; font-size: 0.875rem;">
                    <i class="fas fa-check-circle"></i> Finished orders
                </div>
            </div>
        </div>
        
        <!-- Order Filters -->
        <div class="filter-section">
            <h3 style="margin: 0 0 0.5rem 0; color: #1f2937;">Filter Orders</h3>
            <p style="color: #6b7280; margin: 0 0 1rem 0;">View orders by status</p>
            <div class="filter-buttons">
                <a href="orders.php" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Orders (<?php echo $status_counts['all']; ?>)
                </a>
                <a href="orders.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending (<?php echo $status_counts['pending']; ?>)
                </a>
                <a href="orders.php?status=processing" class="filter-btn <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Processing (<?php echo $status_counts['processing']; ?>)
                </a>
                <a href="orders.php?status=delivered" class="filter-btn <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Delivered (<?php echo $status_counts['delivered']; ?>)
                </a>
                <a href="orders.php?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Completed (<?php echo $status_counts['completed']; ?>)
                </a>
                <a href="orders.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Cancelled (<?php echo $status_counts['cancelled']; ?>)
                </a>
            </div>
        </div>
        
        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag empty-icon"></i>
                <h3 style="color: #374151; margin-bottom: 0.5rem;">No orders found</h3>
                <p style="color: #6b7280;">No orders match your current filter criteria.</p>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-meta">
                            <h3 class="order-number">Order #<?php echo $order['id']; ?></h3>
                            <p class="order-customer">
                                <i class="fas fa-user"></i> <?php echo $order['username'] ?? 'Guest'; ?> • 
                                <i class="fas fa-calendar"></i> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        <div style="text-align: right;">
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <p style="font-size: 1.5rem; font-weight: 700; margin: 0.5rem 0 0; color: white;">
                                $<?php echo number_format($order['total'], 2); ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <div class="order-section">
                            <h4><i class="fas fa-box"></i> Order Items</h4>
                            <?php
                            $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                            $items_stmt = $db->prepare($items_query);
                            $items_stmt->execute([$order['id']]);
                            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <ul class="order-items">
                                <?php foreach($order_items as $item): ?>
                                    <li class="order-item">
                                        <span><?php echo $item['product_name']; ?> × <?php echo $item['quantity']; ?></span>
                                        <span style="font-weight: 600;">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <div class="order-section">
                            <h4><i class="fas fa-credit-card"></i> Payment Details</h4>
                            <p>
                                <strong>Method:</strong> 
                                <span class="payment-badge payment-<?php echo $order['payment_method']; ?>">
                                    <i class="fas fa-credit-card"></i>
                                    <?php echo strtoupper($order['payment_method']); ?>
                                </span>
                            </p>
                            <?php if($order['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?php echo $order['transaction_id']; ?></p>
                            <?php else: ?>
                                <p><strong>Transaction ID:</strong> <span style="color: #6b7280;">N/A</span></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-section">
                            <h4><i class="fas fa-user-circle"></i> Customer Information</h4>
                            <p><strong>Name:</strong> <?php echo $order['name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $order['phone']; ?></p>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-summary">
                            <strong>Items:</strong> <?php echo $order['item_count']; ?> • 
                            <strong>Total Quantity:</strong> <?php echo $order['total_quantity']; ?> • 
                            <strong>Order ID:</strong> #<?php echo $order['id']; ?>
                        </div>
                        <div class="order-actions">
                            <!-- Fixed Status Update Form -->
                            <form method="post" class="auto-submit-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <input type="hidden" name="old_status" value="<?php echo $order['status']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" class="status-select" onchange="this.form.submit()">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </form>
                            
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                <i class="fas fa-eye"></i> Details
                            </a>
                            <a href="../order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-receipt"></i> Receipt
                            </a>
                            <a href="orders.php?delete=<?php echo $order['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this order?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Search functionality
function searchOrders() {
    const searchTerm = document.getElementById('searchOrders').value.toLowerCase();
    const orderCards = document.querySelectorAll('.order-card');
    
    orderCards.forEach(card => {
        const orderId = card.querySelector('.order-number').textContent.toLowerCase();
        const customerName = card.querySelector('.order-customer').textContent.toLowerCase();
        
        if (orderId.includes(searchTerm) || customerName.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Initialize search if element exists
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchOrders');
    if (searchInput) {
        searchInput.addEventListener('input', searchOrders);
    }
});

// Enhanced status update with confirmation for certain status changes
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const newStatus = this.value;
            const orderId = this.form.querySelector('input[name="order_id"]').value;
            
            // Add confirmation for critical status changes
            if (newStatus === 'cancelled') {
                if (!confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                    this.form.reset();
                    return;
                }
            }
            
            if (newStatus === 'completed') {
                if (!confirm('Mark this order as completed? This will reduce product stock quantities.')) {
                    this.form.reset();
                    return;
                }
            }
            
            // Submit the form
            this.form.submit();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>