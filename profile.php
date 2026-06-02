<?php
$page_title = "My Profile";
include 'includes/header.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Get user inquiries
$inquiries_query = "SELECT * FROM inquiries WHERE user_id = ? ORDER BY created_at DESC";
$inquiries_stmt = $db->prepare($inquiries_query);
$inquiries_stmt->execute([$_SESSION['user_id']]);
$inquiries = $inquiries_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user orders for tracking (recent 5 orders)
$orders_query = "SELECT o.*, COUNT(oi.id) as item_count 
                 FROM orders o 
                 LEFT JOIN order_items oi ON o.id = oi.order_id 
                 WHERE o.user_id = ? 
                 GROUP BY o.id 
                 ORDER BY o.created_at DESC 
                 LIMIT 5";
$orders_stmt = $db->prepare($orders_query);
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get ALL user orders for order history tab
$full_orders_query = "SELECT o.*, 
                             COUNT(oi.id) as item_count,
                             SUM(oi.quantity) as total_quantity
                      FROM orders o 
                      LEFT JOIN order_items oi ON o.id = oi.order_id 
                      WHERE o.user_id = ? 
                      GROUP BY o.id 
                      ORDER BY o.created_at DESC";
$full_orders_stmt = $db->prepare($full_orders_query);
$full_orders_stmt->execute([$_SESSION['user_id']]);
$full_orders = $full_orders_stmt->fetchAll(PDO::FETCH_ASSOC);

if($_POST && isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    
    $update_query = "UPDATE users SET first_name = ?, last_name = ?, email = ?, address = ?, phone = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$first_name, $last_name, $email, $address, $phone, $_SESSION['user_id']])) {
        $success = "Profile updated successfully!";
        // Update session data
        $_SESSION['user_email'] = $email;
    } else {
        $error = "Failed to update profile. Please try again.";
    }
}

if($_POST && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(password_verify($current_password, $user['password'])) {
        if($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $password_query = "UPDATE users SET password = ? WHERE id = ?";
            $password_stmt = $db->prepare($password_query);
            
            if($password_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}
?>

<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 3rem 0;
    margin-bottom: 2rem;
}

.profile-header h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 600;
}

.profile-header p {
    margin: 0.5rem 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.profile-content {
    display: flex;
    gap: 2rem;
    margin-bottom: 3rem;
}

.profile-sidebar {
    flex: 0 0 280px;
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: fit-content;
}

.profile-sidebar h3 {
    color: #2c3e50;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
}

.profile-sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.profile-sidebar li {
    margin-bottom: 0.5rem;
}

.profile-tab {
    display: block;
    padding: 0.875rem 1rem;
    color: #495057;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    border: 1px solid transparent;
}

.profile-tab:hover {
    background: #e9ecef;
    color: #2c3e50;
    text-decoration: none;
}

.profile-tab.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.profile-main {
    flex: 1;
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 2px 15px rgba(0,0,0,0.08);
}

.profile-tab-content {
    display: none;
}

.profile-tab-content.active {
    display: block;
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2c3e50;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}

.form-control:disabled {
    background: #f8f9fa;
    color: #6c757d;
}

.btn {
    background: #007bff;
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn:hover {
    background: #0056b3;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
}

.btn-outline {
    background: transparent;
    color: #007bff;
    border: 2px solid #007bff;
}

.btn-outline:hover {
    background: #007bff;
    color: white;
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-color: #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-color: #dc3545;
}

.tracking-steps {
    display: flex;
    justify-content: space-between;
    margin: 2rem 0;
    position: relative;
}

.tracking-steps::before {
    content: '';
    position: absolute;
    top: 25px;
    left: 0;
    right: 0;
    height: 3px;
    background: #e9ecef;
    z-index: 1;
}

.tracking-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
}

.step-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 0.5rem;
    border: 3px solid white;
    transition: all 0.3s ease;
}

.tracking-step.completed .step-icon {
    background: #28a745;
    color: white;
}

.tracking-step.active .step-icon {
    background: #007bff;
    color: white;
    transform: scale(1.1);
}

.step-label {
    font-weight: 600;
    color: #495057;
    text-align: center;
    font-size: 0.9rem;
}

.tracking-step.completed .step-label,
.tracking-step.active .step-label {
    color: #2c3e50;
}

.inquiry-item, .order-item, .history-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
}

.inquiry-item:hover, .order-item:hover, .history-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.inquiry-item h4, .order-item h4, .history-item h3 {
    margin: 0 0 0.5rem 0;
    color: #2c3e50;
    font-weight: 600;
}

.inquiry-item p, .order-item p, .history-item p {
    margin: 0.5rem 0;
    color: #495057;
    line-height: 1.5;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
    text-transform: uppercase;
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.history-details {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 1rem;
}

.history-details h4 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.history-filters {
    background: #e8f4fd;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.history-filters h4 {
    margin-bottom: 1rem;
    color: #2c3e50;
}

.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #adb5bd;
}

.empty-state h3 {
    color: #495057;
    margin-bottom: 0.5rem;
}

small {
    color: #6c757d;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .profile-content {
        flex-direction: column;
    }
    
    .profile-sidebar {
        flex: none;
    }
    
    .tracking-steps {
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .tracking-steps::before {
        display: none;
    }
    
    .history-details {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .history-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<div class="profile-header">
    <div class="container">
        <h1>Welcome, <?php echo $user['first_name'] ? $user['first_name'] : $user['username']; ?>!</h1>
        <p>Manage your profile and view your orders</p>
    </div>
</div>

<div class="container">
    <?php if(isset($success)): ?>
        <div class="alert alert-success">
            <p style="margin: 0;"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <p style="margin: 0;"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>
    
    <div class="profile-content">
        <div class="profile-sidebar">
            <h3>Profile Menu</h3>
            <ul>
                <li><a href="#personal-info" class="profile-tab active">Personal Information</a></li>
                <li><a href="#order-tracking" class="profile-tab">Recent Orders</a></li>
                <li><a href="#order-history" class="profile-tab">Order History</a></li>
                <li><a href="#change-password" class="profile-tab">Change Password</a></li>
                <li><a href="#my-inquiries" class="profile-tab">My Inquiries</a></li>
            </ul>
        </div>
        
        <div class="profile-main">
            <!-- Personal Information Tab -->
            <div id="personal-info" class="profile-tab-content active">
                <h2 style="color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef;">Personal Information</h2>
                <form method="post">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" class="form-control" value="<?php echo $user['username']; ?>" disabled>
                        <small>Username cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo $user['first_name']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo $user['last_name']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"><?php echo $user['address']; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo $user['phone']; ?>">
                    </div>
                    
                    <button type="submit" class="btn">Update Profile</button>
                </form>
            </div>
            
            <!-- Recent Orders Tab -->
            <div id="order-tracking" class="profile-tab-content">
                <h2 style="color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef;">Recent Orders</h2>
                
                <?php if(empty($orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your orders here.</p>
                        <a href="products.php" class="btn">Start Shopping</a>
                    </div>
                <?php else: ?>
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
                    
                    <?php foreach($orders as $order): ?>
                        <div class="order-item">
                            <h4>Order #<?php echo $order['id']; ?> - <?php echo $order['item_count']; ?> items - $<?php echo number_format($order['total'], 2); ?></h4>
                            <p>Placed on: <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <span>Status: 
                                    <span class="status-badge" style="
                                        background: 
                                            <?php 
                                            switch($order['status']) {
                                                case 'pending': echo '#fff3e0'; break;
                                                case 'processing': echo '#e3f2fd'; break;
                                                case 'completed': echo '#e8f5e8'; break;
                                                default: echo '#f5f5f5';
                                            }
                                            ?>; 
                                        color: 
                                            <?php 
                                            switch($order['status']) {
                                                case 'pending': echo '#ef6c00'; break;
                                                case 'processing': echo '#1565c0'; break;
                                                case 'completed': echo '#2e7d32'; break;
                                                default: echo '#757575';
                                            }
                                            ?>;
                                    ">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </span>
                                <span>
                                    <a href="order-confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem;">View Details</a>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <a href="#order-history" class="profile-tab btn">View Full Order History</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order History Tab -->
            <div id="order-history" class="profile-tab-content">
                <h2 style="color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef;">Order History</h2>
                
                <?php if(empty($full_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>No orders yet</h3>
                        <p>Start shopping to see your order history here.</p>
                        <a href="products.php" class="btn">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <!-- Order Status Guide -->
                    <div class="history-filters">
                        <h4>Order Status Guide</h4>
                        <div class="tracking-steps">
                            <div class="tracking-step completed">
                                <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                                <div class="step-label">Order Placed</div>
                            </div>
                            <div class="tracking-step <?php echo in_array('processing', array_column($full_orders, 'status')) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-cog"></i></div>
                                <div class="step-label">Processing</div>
                            </div>
                            <div class="tracking-step <?php echo in_array('completed', array_column($full_orders, 'status')) ? 'active' : ''; ?>">
                                <div class="step-icon"><i class="fas fa-check"></i></div>
                                <div class="step-label">Completed</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Orders List -->
                    <?php foreach($full_orders as $order): ?>
                        <div class="history-item">
                            <div class="history-header">
                                <div>
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p style="color: #6c757d; margin: 0;">Placed on <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></p>
                                </div>
                                <div style="text-align: right;">
                                    <span class="status-badge" style="
                                        background: 
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
                                    ">
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
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e9ecef;">
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
            
            <!-- Change Password Tab -->
            <div id="change-password" class="profile-tab-content">
                <h2 style="color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef;">Change Password</h2>
                <form method="post">
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                    
                    <button type="submit" class="btn">Change Password</button>
                </form>
            </div>
            
            <!-- My Inquiries Tab -->
            <div id="my-inquiries" class="profile-tab-content">
                <h2 style="color: #2c3e50; margin-bottom: 1.5rem; padding-bottom: 0.75rem; border-bottom: 2px solid #e9ecef;">My Inquiries</h2>
                
                <?php if(empty($inquiries)): ?>
                    <div class="empty-state">
                        <i class="fas fa-comments"></i>
                        <h3>No inquiries yet</h3>
                        <p>You haven't made any inquiries yet.</p>
                    </div>
                <?php else: ?>
                    <div class="inquiries-list">
                        <?php foreach($inquiries as $inquiry): ?>
                            <div class="inquiry-item">
                                <h4><?php echo $inquiry['subject']; ?></h4>
                                <p><?php echo $inquiry['message']; ?></p>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                    <span>Status: 
                                        <span class="status-badge" style="
                                            background: 
                                                <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo '#e3f2fd'; break;
                                                    case 'in_progress': echo '#fff3e0'; break;
                                                    case 'resolved': echo '#e8f5e8'; break;
                                                    default: echo '#f5f5f5';
                                                }
                                                ?>; 
                                            color: 
                                                <?php 
                                                switch($inquiry['status']) {
                                                    case 'new': echo '#1565c0'; break;
                                                    case 'in_progress': echo '#ef6c00'; break;
                                                    case 'resolved': echo '#2e7d32'; break;
                                                    default: echo '#757575';
                                                }
                                                ?>; 
                                        ">
                                            <?php echo ucfirst(str_replace('_', ' ', $inquiry['status'])); ?>
                                        </span>
                                    </span>
                                    <span style="color: #6c757d;">Date: <?php echo date('M j, Y', strtotime($inquiry['created_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.profile-tab');
    const tabContents = document.querySelectorAll('.profile-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Show corresponding content
            const target = this.getAttribute('href').substring(1);
            document.getElementById(target).classList.add('active');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>