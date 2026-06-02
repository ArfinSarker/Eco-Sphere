<?php
$page_title = "Order History";
include 'includes/header.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user orders
$orders_query = "SELECT o.*, 
                        COUNT(oi.id) as item_count,
                        SUM(oi.quantity) as total_quantity
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 WHERE o.user_id = ? 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 2rem;">Order History</h1>
        
        <?php if(empty($orders)): ?>
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-shopping-bag" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                <h3>No orders yet</h3>
                <p>Start shopping to see your order history here.</p>
                <a href="products.php" class="btn">Start Shopping</a>
            </div>
        <?php else: ?>
            <!-- Order Tracking Info -->
            <div style="background: var(--light-green); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                <h3 style="color: var(--primary-green); margin-bottom: 1rem;">Order Status Guide</h3>
                <div class="tracking-steps">
                    <div class="tracking-step completed">
                        <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                        <div class="step-label">Order Placed</div>
                    </div>
                    <div class="tracking-step <?php echo in_array('processing', array_column($orders, 'status')) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fas fa-cog"></i></div>
                        <div class="step-label">Processing</div>
                    </div>
                    <div class="tracking-step <?php echo in_array('completed', array_column($orders, 'status')) ? 'active' : ''; ?>">
                        <div class="step-icon"><i class="fas fa-check"></i></div>
                        <div class="step-label">Completed</div>
                    </div>
                </div>
            </div>
            
            <!-- Orders List -->
            <div class="history-filters">
                <h4>Filter Orders</h4>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="order-history.php" class="btn btn-outline">All Orders</a>
                    <a href="order-history.php?status=pending" class="btn btn-outline">Pending</a>
                    <a href="order-history.php?status=processing" class="btn btn-outline">Processing</a>
                    <a href="order-history.php?status=completed" class="btn btn-outline">Completed</a>
                </div>
            </div>
            
            <?php foreach($orders as $order): ?>
                <div class="history-item">
                    <div class="history-header">
                        <div>
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p style="color: var(--text-light); margin: 0;">Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        </div>
                        <div style="text-align: right;">
                            <span style="background: 
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo '#fff3e0'; break;
                                    case 'processing': echo '#e3f2fd'; break;
                                    case 'completed': echo '#e8f5e8'; break;
                                    case 'cancelled': echo '#f8d7da'; break;
                                    default: echo '#f5f5f5';
                                }
                                ?>; 
                                color: 
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo '#ef6c00'; break;
                                    case 'processing': echo '#1565c0'; break;
                                    case 'completed': echo '#2e7d32'; break;
                                    case 'cancelled': echo '#721c24'; break;
                                    default: echo '#757575';
                                }
                                ?>; 
                                padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; text-transform: uppercase;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                            <p style="font-size: 1.2rem; font-weight: bold; margin: 0.5rem 0 0;">$<?php echo number_format($order['total'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="history-details">
                        <div>
                            <h4>Items</h4>
                            <?php
                            $items_query = "SELECT * FROM order_items WHERE order_id = ?";
                            $items_stmt = $db->prepare($items_query);
                            $items_stmt->execute([$order['id']]);
                            $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            foreach($order_items as $item): ?>
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                    <span><?php echo $item['product_name']; ?> × <?php echo $item['quantity']; ?></span>
                                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div>
                            <h4>Payment</h4>
                            <p><strong>Method:</strong> <?php echo strtoupper($order['payment_method']); ?></p>
                            <?php if($order['transaction_id']): ?>
                                <p><strong>Transaction ID:</strong> <?php echo $order['transaction_id']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <h4>Shipping</h4>
                            <p><strong>To:</strong> <?php echo $order['name']; ?></p>
                            <p><strong>Address:</strong> <?php echo $order['address']; ?>, <?php echo $order['city']; ?></p>
                        </div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--medium-gray);">
                        <div>
                            <strong>Total Items:</strong> <?php echo $order['item_count']; ?> 
                            | <strong>Total Quantity:</strong> <?php echo $order['total_quantity']; ?>
                        </div>
                        <a href="order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>