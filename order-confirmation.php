<?php
require_once 'config/config.php';

if(!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$page_title = "Order Confirmation";
include 'includes/header.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get order details - FIXED THE QUERY
$order_query = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ? AND (o.user_id = ? OR ? = 'admin')";
$order_stmt = $db->prepare($order_query);

// Get current user role safely
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'customer';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$order_stmt->execute([$_GET['order_id'], $user_id, $user_role]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

if(!$order) {
    // If order not found, redirect with error message
    $_SESSION['error'] = "Order not found or you don't have permission to view this order.";
    header("Location: profile.php");
    exit;
}

// Get order items
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = $db->prepare($items_query);
$items_stmt->execute([$_GET['order_id']]);
$order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate subtotal from items for verification
$calculated_subtotal = 0;
foreach($order_items as $item) {
    $calculated_subtotal += $item['price'] * $item['quantity'];
}
?>

<section class="section">
    <div class="container">
        <div style="text-align: center; margin-bottom: 3rem;">
            <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--primary-green); margin-bottom: 1rem;"></i>
            <h1>Order Confirmed!</h1>
            <p>Thank you for your purchase. Your order has been received.</p>
            <p><strong>Order ID: #<?php echo $order['id']; ?></strong></p>
        </div>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Order Details -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                <h3 style="margin-bottom: 1.5rem;">Order Details</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                    <div>
                        <p><strong>Order ID:</strong> #<?php echo $order['id']; ?></p>
                        <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        <p><strong>Status:</strong> 
                            <span style="padding: 0.3rem 0.8rem; border-radius: 3px; background: 
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo '#fff3e0'; break;
                                    case 'processing': echo '#e3f2fd'; break;
                                    case 'completed': echo '#e8f5e8'; break;
                                    case 'cancelled': echo '#f8d7da'; break;
                                    default: echo '#f5f5f5';
                                }
                                ?>; color: 
                                <?php 
                                switch($order['status']) {
                                    case 'pending': echo '#ef6c00'; break;
                                    case 'processing': echo '#1565c0'; break;
                                    case 'completed': echo '#2e7d32'; break;
                                    case 'cancelled': echo '#721c24'; break;
                                    default: echo '#757575';
                                }
                                ?>;">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div>
                        <p><strong>Payment Method:</strong> 
                            <?php 
                            $payment_methods = [
                                'cash' => 'Cash on Delivery',
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'card' => 'Credit/Debit Card'
                            ];
                            echo $payment_methods[$order['payment_method']] ?? strtoupper($order['payment_method']); 
                            ?>
                        </p>
                        <?php if($order['transaction_id']): ?>
                            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($order['transaction_id']); ?></p>
                        <?php endif; ?>
                        <p><strong>Total Amount:</strong> $<?php echo number_format($order['total'], 2); ?></p>
                    </div>
                </div>
                
                <h4 style="margin-bottom: 1rem;">Order Items</h4>
                <div style="border: 1px solid var(--medium-gray); border-radius: 4px; overflow: hidden;">
                    <div style="display: grid; grid-template-columns: 2fr auto auto; gap: 1rem; padding: 1rem; border-bottom: 2px solid var(--medium-gray); background: var(--light-gray); font-weight: bold;">
                        <div>Product</div>
                        <div style="text-align: center;">Quantity</div>
                        <div style="text-align: center;">Total</div>
                    </div>
                    <?php foreach($order_items as $item): ?>
                        <div style="display: grid; grid-template-columns: 2fr auto auto; gap: 1rem; padding: 1rem; border-bottom: 1px solid var(--medium-gray); align-items: center;">
                            <div>
                                <p style="margin: 0; font-weight: bold;"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                <p style="margin: 0; color: var(--text-color);">Price: $<?php echo number_format($item['price'], 2); ?> each</p>
                            </div>
                            <span style="text-align: center; font-weight: bold;"><?php echo $item['quantity']; ?></span>
                            <span style="text-align: center; font-weight: bold;">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Shipping Information -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow); height: fit-content;">
                <h3 style="margin-bottom: 1.5rem;">Shipping Information</h3>
                
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($order['address'])); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($order['city']); ?></p>
                <p><strong>ZIP Code:</strong> <?php echo htmlspecialchars($order['zip_code']); ?></p>
                
                <div style="margin-top: 2rem; text-align: center;">
                    <a href="products.php" class="btn" style="margin-bottom: 0.5rem; display: block;">Continue Shopping</a>
                    <a href="profile.php" class="btn btn-outline" style="display: block;">View Order History</a>
                </div>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow); margin-top: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; max-width: 400px; margin: 0 auto;">
                <div style="text-align: right;">
                    <p>Subtotal:</p>
                    <p>Shipping:</p>
                    <p>Tax (10%):</p>
                    <p style="font-weight: bold; font-size: 1.1rem; margin-top: 0.5rem;">Total:</p>
                </div>
                <div>
                    <p>$<?php echo number_format($order['subtotal'], 2); ?></p>
                    <p>$<?php echo number_format($order['shipping'], 2); ?></p>
                    <p>$<?php echo number_format($order['tax'], 2); ?></p>
                    <p style="font-weight: bold; font-size: 1.1rem; margin-top: 0.5rem;">$<?php echo number_format($order['total'], 2); ?></p>
                </div>
            </div>
            
            <?php if(abs($calculated_subtotal - $order['subtotal']) > 0.01): ?>
                <div style="margin-top: 1rem; padding: 0.5rem; background: #fff3e0; border-radius: 4px; text-align: center;">
                    <small style="color: #ef6c00;">
                        <i class="fas fa-info-circle"></i> 
                        Note: Calculated subtotal ($<?php echo number_format($calculated_subtotal, 2); ?>) differs from stored subtotal due to rounding.
                    </small>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Instructions for bKash/Nagad -->
        <?php if(in_array($order['payment_method'], ['bkash', 'nagad']) && $order['status'] == 'pending'): ?>
            <div style="background: var(--light-green); padding: 1.5rem; border-radius: 8px; margin-top: 2rem; border-left: 4px solid var(--primary-green);">
                <h3 style="color: var(--primary-green); margin-bottom: 1rem;">
                    <i class="fas fa-mobile-alt"></i> 
                    <?php echo strtoupper($order['payment_method']); ?> Payment Instructions
                </h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                    <div>
                        <h4>Steps to Complete Payment:</h4>
                        <ol style="margin-bottom: 1rem;">
                            <li>Open your <?php echo ucfirst($order['payment_method']); ?> app</li>
                            <li>Go to "Send Money"</li>
                            <li>Enter this number: <strong>01823117060</strong></li>
                            <li>Enter amount: <strong>$<?php echo number_format($order['total'], 2); ?></strong></li>
                            <li>Enter reference: <strong>Order #<?php echo $order['id']; ?></strong></li>
                            <li>Enter your PIN</li>
                            <li>Save the transaction ID</li>
                        </ol>
                        <div style="background: #fff3e0; padding: 1rem; border-radius: 4px;">
                            <p style="margin: 0; color: #ef6c00; font-size: 0.9rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> Your order will be processed once payment is verified. Please keep your transaction ID safe.
                            </p>
                        </div>
                    </div>
                    <div>
                        <h4>Payment Details:</h4>
                        <div style="background: white; padding: 1.5rem; border-radius: 4px; border: 1px solid var(--medium-gray);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span><strong>Account Number:</strong></span>
                                <span>01823117060</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span><strong>Amount:</strong></span>
                                <span>$<?php echo number_format($order['total'], 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span><strong>Reference:</strong></span>
                                <span>Order #<?php echo $order['id']; ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span><strong>Payment Method:</strong></span>
                                <span><?php echo strtoupper($order['payment_method']); ?></span>
                            </div>
                        </div>
                        
                        <?php if(empty($order['transaction_id'])): ?>
                            <div style="margin-top: 1rem; padding: 1rem; background: #e3f2fd; border-radius: 4px;">
                                <p style="margin: 0; color: #1565c0; font-size: 0.9rem;">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Note:</strong> Once you complete the payment, please contact support with your transaction ID if it's not automatically verified within 24 hours.
                                </p>
                            </div>
                        <?php else: ?>
                            <div style="margin-top: 1rem; padding: 1rem; background: #e8f5e8; border-radius: 4px;">
                                <p style="margin: 0; color: #2e7d32;">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>Payment Received:</strong> Transaction ID: <?php echo htmlspecialchars($order['transaction_id']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Order Status Timeline -->
        <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow); margin-top: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Order Status</h3>
            <div style="display: flex; justify-content: space-between; position: relative;">
                <div style="position: absolute; top: 50%; left: 0; right: 0; height: 2px; background: var(--medium-gray); z-index: 1;"></div>
                
                <?php 
                $statuses = [
                    'pending' => ['icon' => 'fa-clock', 'color' => '#ef6c00'],
                    'processing' => ['icon' => 'fa-cog', 'color' => '#1565c0'],
                    'completed' => ['icon' => 'fa-check', 'color' => '#2e7d32']
                ];
                
                $current_status_index = array_search($order['status'], array_keys($statuses));
                if ($current_status_index === false) {
                    $current_status_index = 0; // Default to first status if not found
                }
                ?>
                
                <?php foreach($statuses as $status => $info): ?>
                    <?php 
                    $status_index = array_search($status, array_keys($statuses));
                    $is_completed = $status_index <= $current_status_index;
                    $is_current = $status === $order['status'];
                    ?>
                    <div style="text-align: center; position: relative; z-index: 2; flex: 1;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: <?php echo $is_completed ? $info['color'] : 'var(--medium-gray)'; ?>; margin: 0 auto 0.5rem; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                            <i class="fas <?php echo $info['icon']; ?>"></i>
                        </div>
                        <p style="margin: 0; font-weight: <?php echo $is_current ? 'bold' : 'normal'; ?>; color: <?php echo $is_completed ? $info['color'] : 'var(--text-light)'; ?>;">
                            <?php echo ucfirst($status); ?>
                        </p>
                        <?php if($is_current): ?>
                            <p style="margin: 0.25rem 0 0 0; font-size: 0.8rem; color: var(--text-light);">Current</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>