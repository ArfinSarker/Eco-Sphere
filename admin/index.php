<?php
require_once '../config/config.php';

$page_title = "Admin Dashboard";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if($_SESSION['user_role'] != 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../index.php");
    exit;
}

include 'includes/header.php';

// Get counts for dashboard
$products_count = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$users_count = $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$inquiries_count = $db->query("SELECT COUNT(*) FROM inquiries WHERE status = 'new'")->fetchColumn();
$orders_count = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Get recent inquiries
$recent_inquiries = $db->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get recent orders
$recent_orders = $db->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get additional stats for enhanced dashboard
$total_revenue = $db->query("SELECT SUM(total) FROM orders WHERE status = 'completed'")->fetchColumn();
$total_revenue = $total_revenue ?: 0;

$monthly_revenue = $db->query("SELECT SUM(total) FROM orders WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn();
$monthly_revenue = $monthly_revenue ?: 0;

$total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
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

.dashboard-header {
    margin-bottom: 2rem;
}

.dashboard-header h1 {
    color: #1f2937;
    font-weight: 700;
    font-size: 2.25rem;
    margin: 0 0 0.5rem 0;
}

.dashboard-header p {
    color: #6b7280;
    font-size: 1.1rem;
    margin: 0;
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

.dashboard-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

@media (max-width: 1024px) {
    .dashboard-content {
        grid-template-columns: 1fr;
    }
}

.dashboard-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #1f2937;
    font-weight: 600;
    font-size: 1.25rem;
}

.card-body {
    padding: 1.5rem;
}

.card-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #e5e7eb;
    background: #f9fafb;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}

.empty-state h4 {
    color: #374151;
    margin-bottom: 0.5rem;
}

.inquiry-item, .order-item {
    padding: 1rem 0;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}

.inquiry-item:hover, .order-item:hover {
    background-color: #f9fafb;
}

.inquiry-item:last-child, .order-item:last-child {
    border-bottom: none;
}

.inquiry-title, .order-title {
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    font-size: 1rem;
}

.inquiry-meta, .order-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.inquiry-customer, .order-customer {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.inquiry-date, .order-date {
    color: #9ca3af;
    font-size: 0.8rem;
    margin: 0;
}

.inquiry-amount {
    color: #059669;
    font-weight: 600;
    font-size: 1rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: capitalize;
}

.status-new { background: #dbeafe; color: #1d4ed8; }
.status-in_progress { background: #fef3c7; color: #d97706; }
.status-resolved { background: #d1fae5; color: #065f46; }
.status-pending { background: #fef3c7; color: #d97706; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #3b82f6;
    color: white;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-outline {
    background: white;
    color: #374151;
    border: 1px solid #d1d5db;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.quick-stat {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    text-align: center;
}

.quick-stat h4 {
    margin: 0 0 0.5rem 0;
    color: #6b7280;
    font-weight: 500;
    font-size: 0.9rem;
}

.quick-stat p {
    margin: 0;
    color: #1f2937;
    font-size: 1.5rem;
    font-weight: 700;
}

.quick-stat .trend {
    font-size: 0.8rem;
    margin-top: 0.5rem;
}

.trend.up {
    color: #10b981;
}

.trend.down {
    color: #ef4444;
}

.welcome-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.welcome-banner h2 {
    margin: 0 0 0.5rem 0;
    font-weight: 700;
}

.welcome-banner p {
    margin: 0;
    opacity: 0.9;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: rgba(255,255,255,0.2);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    backdrop-filter: blur(10px);
}

.action-btn:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
}

@media (max-width: 768px) {
    .admin-main {
        padding: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-stats {
        grid-template-columns: 1fr 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .action-btn {
        justify-content: center;
    }
    
    .inquiry-meta, .order-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3 style="padding: 0 1.5rem; margin-bottom: 1rem;">Admin Panel</h3>
        <ul>
            <li><a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
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
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo $_SESSION['username']; ?>! Here's what's happening with your store today.</p>
        </div>
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <h2>Eco-Sphere Admin Panel</h2>
            <p>Manage your plant store efficiently with our comprehensive dashboard</p>
            <div class="action-buttons">
                <a href="products.php" class="action-btn">
                    <i class="fas fa-plus"></i> Add New Product
                </a>
                <a href="orders.php" class="action-btn">
                    <i class="fas fa-shopping-cart"></i> Manage Orders
                </a>
                <a href="inquiries.php" class="action-btn">
                    <i class="fas fa-envelope"></i> View Inquiries
                </a>
            </div>
        </div>
        
        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="quick-stat">
                <h4>Total Revenue</h4>
                <p>$<?php echo number_format($total_revenue, 2); ?></p>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i> All Time
                </div>
            </div>
            <div class="quick-stat">
                <h4>Monthly Revenue</h4>
                <p>$<?php echo number_format($monthly_revenue, 2); ?></p>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i> This Month
                </div>
            </div>
            <div class="quick-stat">
                <h4>Total Orders</h4>
                <p><?php echo $total_orders; ?></p>
                <div class="trend up">
                    <i class="fas fa-chart-line"></i> All Orders
                </div>
            </div>
            <div class="quick-stat">
                <h4>Conversion Rate</h4>
                <p>12.5%</p>
                <div class="trend up">
                    <i class="fas fa-arrow-up"></i> +2.3%
                </div>
            </div>
        </div>
        
        <!-- Main Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #10b981;">
                    <i class="fas fa-tree"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $products_count; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #3b82f6;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $users_count; ?></h3>
                    <p>Total Customers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #f59e0b;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $inquiries_count; ?></h3>
                    <p>New Inquiries</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: #ef4444;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $orders_count; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="dashboard-content">
            <!-- Recent Inquiries -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-envelope"></i> Recent Inquiries</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($recent_inquiries)): ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h4>No recent inquiries</h4>
                            <p>All customer inquiries have been addressed</p>
                        </div>
                    <?php else: ?>
                        <div class="inquiries-list">
                            <?php foreach($recent_inquiries as $inquiry): ?>
                                <div class="inquiry-item">
                                    <h4 class="inquiry-title"><?php echo $inquiry['subject']; ?></h4>
                                    <div class="inquiry-meta">
                                        <p class="inquiry-customer">From: <?php echo $inquiry['name']; ?> (<?php echo $inquiry['email']; ?>)</p>
                                        <span class="status-badge status-<?php echo $inquiry['status']; ?>">
                                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $inquiry['status'])); ?>
                                        </span>
                                    </div>
                                    <p class="inquiry-date"><?php echo date('M j, Y g:i A', strtotime($inquiry['created_at'])); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="inquiries.php" class="btn btn-outline">
                        <i class="fas fa-list"></i> View All Inquiries
                    </a>
                </div>
            </div>
            
            <!-- Recent Orders -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-shopping-cart"></i> Recent Orders</h3>
                </div>
                <div class="card-body">
                    <?php if(empty($recent_orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h4>No recent orders</h4>
                            <p>New orders will appear here</p>
                        </div>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach($recent_orders as $order): ?>
                                <div class="order-item">
                                    <h4 class="order-title">Order #<?php echo $order['id']; ?></h4>
                                    <div class="order-meta">
                                        <p class="order-customer">From: <?php echo $order['name']; ?></p>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <p class="order-date"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                        <p class="inquiry-amount">$<?php echo number_format($order['total'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="orders.php" class="btn btn-outline">
                        <i class="fas fa-list"></i> View All Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>