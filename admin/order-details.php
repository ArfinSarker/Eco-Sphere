<?php
require_once '../config/config.php';

$page_title = "Order Details";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get order ID from URL
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: order-history.php");
    exit;
}

$order_id = $_GET['id'];

// Get order details
$order_query = "SELECT o.*, u.username, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$order_stmt = $db->prepare($order_query);
$order_stmt->execute([$order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if(!$order) {
    header("Location: order-history.php");
    exit;
}

// Get order items
$items_query = "SELECT oi.*, p.name as product_name, pi.image_url 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE oi.order_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$order_id]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
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

.order-details-container {
    max-width: 1200px;
    margin: 0 auto;
}

.order-header-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
    overflow: hidden;
}

.order-header {
    padding: 2rem;
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
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
}

.order-customer {
    font-size: 1.1rem;
    opacity: 0.9;
    margin: 0;
}

.order-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 1rem;
}

.status-pending { background: #fef3c7; color: #d97706; }
.status-processing { background: #dbeafe; color: #1d4ed8; }
.status-completed { background: #d1fae5; color: #065f46; }
.status-cancelled { background: #fee2e2; color: #dc2626; }

.order-total {
    text-align: right;
}

.order-amount {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0;
    color: white;
}

.order-date {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0.5rem 0 0 0;
}

.details-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.details-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    background: #f8fafc;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
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

.order-items-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.order-item {
    display: flex;
    align-items: center;
    padding: 1rem 0;
    border-bottom: 1px solid #f3f4f6;
    gap: 1rem;
}

.order-item:last-child {
    border-bottom: none;
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    object-fit: cover;
    border: 1px solid #e5e7eb;
}

.item-details {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.25rem 0;
}

.item-price {
    color: #6b7280;
    font-size: 0.9rem;
    margin: 0;
}

.item-total {
    font-weight: 700;
    color: #059669;
    font-size: 1.1rem;
}

.info-grid {
    display: grid;
    gap: 1rem;
}

.info-group {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.info-group:last-child {
    border-bottom: none;
}

.info-label {
    color: #6b7280;
    font-weight: 500;
}

.info-value {
    color: #1f2937;
    font-weight: 600;
    text-align: right;
}

.payment-status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.status-paid { background: #d1fae5; color: #065f46; }
.status-pending { background: #fef3c7; color: #d97706; }

.actions-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    padding: 1.5rem;
    margin-top: 2rem;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    border: 2px solid #3b82f6;
    border-radius: 8px;
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    background: white;
}

.action-btn:hover {
    background: #3b82f6;
    color: white;
}

.action-btn.primary {
    background: #3b82f6;
    color: white;
}

.action-btn.primary:hover {
    background: #2563eb;
    border-color: #2563eb;
}

.action-btn.secondary {
    border-color: #6b7280;
    color: #6b7280;
}

.action-btn.secondary:hover {
    background: #6b7280;
    color: white;
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: #6b7280;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    margin-bottom: 2rem;
    transition: background 0.2s ease;
}

.back-btn:hover {
    background: #4b5563;
    color: white;
}

@media (max-width: 1024px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .order-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .order-total {
        text-align: left;
    }
}

@media (max-width: 768px) {
    .order-item {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
    }
    
    .actions-grid {
        grid-template-columns: 1fr;
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
        <div class="order-details-container">
            <a href="order-history.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Order History
            </a>
            
            <!-- Order Header -->
            <div class="order-header-card">
                <div class="order-header">
                    <div class="order-meta">
                        <h1 class="order-number">Order #<?php echo $order['id']; ?></h1>
                        <p class="order-customer">
                            <i class="fas fa-user"></i> <?php echo $order['username'] ?? 'Guest'; ?> • 
                            <i class="fas fa-envelope"></i> <?php echo $order['user_email'] ?? $order['email']; ?>
                        </p>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i>
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-total">
                        <p class="order-amount">$<?php echo number_format($order['total'], 2); ?></p>
                        <p class="order-date">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Order Details Grid -->
            <div class="details-grid">
                <!-- Order Items -->
                <div class="details-card">
                    <div class="card-header">
                        <h3><i class="fas fa-box"></i> Order Items</h3>
                        <span style="color: #6b7280; font-weight: 600;">
                            <?php echo count($order_items); ?> items
                        </span>
                    </div>
                    <div class="card-body">
                        <ul class="order-items-list">
                            <?php foreach($order_items as $item): ?>
                                <li class="order-item">
                                    <img src="../<?php echo $item['image_url'] ?? 'images/placeholder.jpg'; ?>" 
                                         alt="<?php echo $item['product_name']; ?>" 
                                         class="item-image">
                                    <div class="item-details">
                                        <h4 class="item-name"><?php echo $item['product_name']; ?></h4>
                                        <p class="item-price">
                                            $<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?>
                                        </p>
                                    </div>
                                    <div class="item-total">
                                        $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="details-card">
                    <div class="card-header">
                        <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <span class="info-label">Subtotal</span>
                                <span class="info-value">$<?php echo number_format($order['subtotal'], 2); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Shipping</span>
                                <span class="info-value">$<?php echo number_format($order['shipping'], 2); ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Tax</span>
                                <span class="info-value">$<?php echo number_format($order['tax'], 2); ?></span>
                            </div>
                            <div class="info-group" style="border-top: 2px solid #e5e7eb; padding-top: 1rem;">
                                <span class="info-label" style="font-size: 1.1rem; color: #1f2937;">Total</span>
                                <span class="info-value" style="font-size: 1.1rem; color: #059669;">
                                    $<?php echo number_format($order['total'], 2); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="details-card">
                    <div class="card-header">
                        <h3><i class="fas fa-user-circle"></i> Customer Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <span class="info-label">Full Name</span>
                                <span class="info-value"><?php echo $order['name']; ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo $order['email']; ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo $order['phone']; ?></span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Address</span>
                                <span class="info-value">
                                    <?php echo $order['address']; ?>, <?php echo $order['city']; ?>, <?php echo $order['zip_code']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="details-card">
                    <div class="card-header">
                        <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-group">
                                <span class="info-label">Payment Method</span>
                                <span class="info-value" style="text-transform: uppercase;">
                                    <?php echo $order['payment_method']; ?>
                                </span>
                            </div>
                            <?php if($order['transaction_id']): ?>
                            <div class="info-group">
                                <span class="info-label">Transaction ID</span>
                                <span class="info-value"><?php echo $order['transaction_id']; ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="info-group">
                                <span class="info-label">Payment Status</span>
                                <span class="info-value">
                                    <span class="payment-status status-paid">
                                        <i class="fas fa-check-circle"></i> Paid
                                    </span>
                                </span>
                            </div>
                            <div class="info-group">
                                <span class="info-label">Order Date</span>
                                <span class="info-value">
                                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="actions-section">
                <div class="actions-grid">
                    <a href="../order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="action-btn primary" target="_blank">
                        <i class="fas fa-receipt"></i> View Receipt
                    </a>
                    <a href="order-history.php" class="action-btn secondary">
                        <i class="fas fa-arrow-left"></i> Back to Orders
                    </a>
                    <a href="orders.php" class="action-btn">
                        <i class="fas fa-shopping-cart"></i> Manage Orders
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>