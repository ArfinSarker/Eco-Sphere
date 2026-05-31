<?php
require_once '../config/config.php';

$page_title = "Order History";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle status filter
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

// Get all orders for history
$orders_query = "SELECT o.*, u.username,
                        COUNT(oi.id) as item_count,
                        SUM(oi.quantity) as total_quantity
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 $where_clause 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get order statistics
$stats_query = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(total) as total_revenue,
                    AVG(total) as avg_order_value,
                    COUNT(DISTINCT user_id) as unique_customers
                FROM orders 
                WHERE status != 'cancelled'";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
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
.stat-card:nth-child(2) { border-left-color: #3b82f6; }
.stat-card:nth-child(3) { border-left-color: #f59e0b; }
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
.status-completed { background: #d1fae5; color: #065f46; }
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
}

.btn-outline {
    padding: 0.75rem 1.5rem;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-outline:hover {
    background: #3b82f6;
    color: white;
}

.btn-primary {
    padding: 0.75rem 1.5rem;
    background: #3b82f6;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #2563eb;
    border-color: #2563eb;
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

.section-title {
    color: #1f2937;
    margin-bottom: 1.5rem;
    font-weight: 700;
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
}
</style>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3 style="padding: 0 1.5rem; margin-bottom: 1rem;">Admin Panel</h3>
        <ul>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="order-history.php" class="active"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <h1 class="section-title">Order History & Analytics</h1>
        
        <!-- Order Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div style="color: #10b981; font-size: 0.875rem;">
                    <i class="fas fa-chart-line"></i> All successful orders
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                <div style="color: #3b82f6; font-size: 0.875rem;">
                    <i class="fas fa-dollar-sign"></i> Gross revenue
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Average Order Value</div>
                <div class="stat-value">$<?php echo number_format($stats['avg_order_value'], 2); ?></div>
                <div style="color: #f59e0b; font-size: 0.875rem;">
                    <i class="fas fa-calculator"></i> Per order average
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">Unique Customers</div>
                <div class="stat-value"><?php echo $stats['unique_customers']; ?></div>
                <div style="color: #ef4444; font-size: 0.875rem;">
                    <i class="fas fa-users"></i> Total customers
                </div>
            </div>
        </div>
        
        <!-- Order Filters -->
        <div class="filter-section">
            <h3 style="margin: 0 0 0.5rem 0; color: #1f2937;">Filter Orders</h3>
            <p style="color: #6b7280; margin: 0 0 1rem 0;">View orders by status</p>
            <div class="filter-buttons">
                <a href="order-history.php" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Orders
                </a>
                <a href="order-history.php?status=pending" class="filter-btn <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="order-history.php?status=processing" class="filter-btn <?php echo $status_filter == 'processing' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Processing
                </a>
                <a href="order-history.php?status=completed" class="filter-btn <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Completed
                </a>
                <a href="order-history.php?status=cancelled" class="filter-btn <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Cancelled
                </a>
            </div>
        </div>
        
        <?php if(empty($orders)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag empty-icon"></i>
                <h3 style="color: #374151; margin-bottom: 0.5rem;">No orders found</h3>
                <p style="color: #6b7280;">No orders match your current filter criteria.</p>
                <a href="order-history.php" class="btn-primary" style="margin-top: 1.5rem; display: inline-block;">
                    View All Orders
                </a>
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
                            <p><strong>Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                            <?php if($order['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?php echo $order['transaction_id']; ?></p>
                            <?php endif; ?>
                            <p><strong>Subtotal:</strong> $<?php echo number_format($order['subtotal'], 2); ?></p>
                            <p><strong>Shipping:</strong> $<?php echo number_format($order['shipping'], 2); ?></p>
                            <p><strong>Tax:</strong> $<?php echo number_format($order['tax'], 2); ?></p>
                        </div>
                        
                        <div class="order-section">
                            <h4><i class="fas fa-user-circle"></i> Customer Information</h4>
                            <p><strong>Name:</strong> <?php echo $order['name']; ?></p>
                            <p><strong>Email:</strong> <?php echo $order['email']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $order['phone']; ?></p>
                            <p><strong>Address:</strong><br><?php echo $order['address']; ?>, <?php echo $order['city']; ?>, <?php echo $order['zip_code']; ?></p>
                        </div>
                    </div>
                    
                    <div class="order-footer">
                        <div class="order-summary">
                            <strong>Items:</strong> <?php echo $order['item_count']; ?> • 
                            <strong>Total Quantity:</strong> <?php echo $order['total_quantity']; ?> • 
                            <strong>Order ID:</strong> #<?php echo $order['id']; ?>
                        </div>
                        <div class="order-actions">
                            <a href="order-details.php?id=<?php echo $order['id']; ?>" class="btn-outline">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <a href="../order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn-primary">
                                <i class="fas fa-receipt"></i> View Receipt
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>