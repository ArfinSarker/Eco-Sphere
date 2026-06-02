<?php
$page_title = "Checkout";
include 'includes/header.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please login to checkout";
    header("Location: login.php");
    exit;
}

// Check if cart is empty
if(!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty";
    header("Location: cart.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle cart updates from checkout page
if($_POST && isset($_POST['update_cart'])) {
    foreach($_POST['quantities'] as $product_id => $quantity) {
        $quantity = intval($quantity);
        
        // Check stock availability
        $stock_query = "SELECT stock_quantity FROM products WHERE id = ?";
        $stock_stmt = $db->prepare($stock_query);
        $stock_stmt->execute([$product_id]);
        $stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($stock && $quantity > $stock['stock_quantity']) {
            $_SESSION['error'] = "Only " . $stock['stock_quantity'] . " items available for this product!";
            $quantity = $stock['stock_quantity'];
        }
        
        if($quantity == 0) {
            // Remove item
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
                return $item['product_id'] != $product_id;
            });
        } else {
            // Update quantity
            foreach($_SESSION['cart'] as &$item) {
                if($item['product_id'] == $product_id) {
                    $item['quantity'] = $quantity;
                    break;
                }
            }
        }
    }
    if(!isset($_SESSION['error'])) {
        $_SESSION['success'] = "Cart updated successfully!";
    }
    
    header("Location: checkout.php");
    exit;
}

// Handle item removal from checkout page
if($_POST && isset($_POST['remove_item'])) {
    $product_id = $_POST['product_id'];
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] != $product_id;
    });
    $_SESSION['success'] = "Item removed from cart!";
    header("Location: checkout.php");
    exit;
}

// Get user info
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $db->prepare($user_query);
$user_stmt->execute([$_SESSION['user_id']]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate totals from current cart
$cart = $_SESSION['cart'];
$subtotal = 0;
$total_items = 0;

foreach($cart as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
}

$shipping = 10.00;
$tax = $subtotal * 0.1;
$total = $subtotal + $shipping + $tax;

// Handle order placement
if($_POST && isset($_POST['place_order'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $zip_code = trim($_POST['zip_code']);
    $payment_method = $_POST['payment_method'];
    
    // Get transaction ID based on payment method
    $transaction_id = '';
    if($payment_method == 'bkash') {
        $transaction_id = trim($_POST['bkash_transaction'] ?? '');
    } elseif($payment_method == 'nagad') {
        $transaction_id = trim($_POST['nagad_transaction'] ?? '');
    } elseif($payment_method == 'card') {
        $transaction_id = trim($_POST['card_transaction'] ?? '');
    }
    
    // Check stock availability before placing order
    $stock_errors = [];
    foreach($cart as $item) {
        $stock_query = "SELECT stock_quantity, name FROM products WHERE id = ?";
        $stock_stmt = $db->prepare($stock_query);
        $stock_stmt->execute([$item['product_id']]);
        $product = $stock_stmt->fetch(PDO::FETCH_ASSOC);
        
        if($product && $item['quantity'] > $product['stock_quantity']) {
            $stock_errors[] = "Only " . $product['stock_quantity'] . " items available for " . $product['name'];
        }
    }
    
    if(!empty($stock_errors)) {
        $error = implode("<br>", $stock_errors);
    } else {
        try {
            $db->beginTransaction();
            
            // Insert order
            $order_query = "INSERT INTO orders (user_id, name, email, phone, address, city, zip_code, payment_method, transaction_id, subtotal, shipping, tax, total, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $order_stmt = $db->prepare($order_query);
            
            if($order_stmt->execute([
                $_SESSION['user_id'], 
                $name, 
                $email, 
                $phone, 
                $address, 
                $city, 
                $zip_code, 
                $payment_method, 
                $transaction_id, 
                $subtotal, 
                $shipping, 
                $tax, 
                $total
            ])) {
                $order_id = $db->lastInsertId();
                
                // Insert order items
                $order_items_query = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)";
                $order_items_stmt = $db->prepare($order_items_query);
                
                foreach($cart as $item) {
                    $order_items_stmt->execute([$order_id, $item['product_id'], $item['name'], $item['quantity'], $item['price']]);
                    
                    // Update product stock
                    $update_stock_query = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                    $update_stock_stmt = $db->prepare($update_stock_query);
                    $update_stock_stmt->execute([$item['quantity'], $item['product_id']]);
                }
                
                $db->commit();
                
                // Clear cart
                unset($_SESSION['cart']);
                
                $_SESSION['success'] = "Order placed successfully! Order ID: #" . $order_id;
                header("Location: order-confirmation.php?order_id=" . $order_id);
                exit;
            } else {
                $db->rollBack();
                $error = "Failed to place order. Please try again.";
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 2rem;">Checkout</h1>
        
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
        
        <!-- Order Summary with Cart Update Form -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <!-- Shipping Information -->
            <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                <h3 style="margin-bottom: 1.5rem;">Shipping Information</h3>
                
                <!-- Order Placement Form -->
                <form method="post" id="checkout-form">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?php echo $user['first_name'] . ' ' . $user['last_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="text" id="phone" name="phone" class="form-control" 
                               value="<?php echo $user['phone'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Shipping Address *</label>
                        <textarea id="address" name="address" class="form-control" rows="3" required><?php echo $user['address'] ?? ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="city">City *</label>
                            <input type="text" id="city" name="city" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="zip_code">ZIP Code *</label>
                            <input type="text" id="zip_code" name="zip_code" class="form-control" required>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group">
                        <label for="payment_method">Payment Method *</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="">Select Payment Method</option>
                            <option value="cash">Cash on Delivery</option>
                            <option value="bkash">bKash</option>
                            <option value="nagad">Nagad</option>
                            <option value="card">Credit/Debit Card</option>
                        </select>
                    </div>
                    
                    <!-- bKash Instructions -->
                    <div id="bkash_instructions" style="display: none; background: var(--light-green); padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">bKash Payment Instructions:</h4>
                        <ol style="margin: 0; padding-left: 1.2rem;">
                            <li>Open your bKash app</li>
                            <li>Go to "Send Money"</li>
                            <li>Enter this number: <strong>01823117060</strong></li>
                            <li>Enter amount: <strong>$<span class="payment-amount"><?php echo number_format($total, 2); ?></span></strong></li>
                            <li>Enter your bKash PIN</li>
                            <li>Enter the transaction ID below</li>
                        </ol>
                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="bkash_transaction">bKash Transaction ID *</label>
                            <input type="text" id="bkash_transaction" name="bkash_transaction" class="form-control" placeholder="Enter bKash transaction ID">
                        </div>
                    </div>
                    
                    <!-- Nagad Instructions -->
                    <div id="nagad_instructions" style="display: none; background: var(--light-green); padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Nagad Payment Instructions:</h4>
                        <ol style="margin: 0; padding-left: 1.2rem;">
                            <li>Open your Nagad app</li>
                            <li>Go to "Send Money"</li>
                            <li>Enter this number: <strong>01823117060</strong></li>
                            <li>Enter amount: <strong>$<span class="payment-amount"><?php echo number_format($total, 2); ?></span></strong></li>
                            <li>Enter your Nagad PIN</li>
                            <li>Enter the transaction ID below</li>
                        </ol>
                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="nagad_transaction">Nagad Transaction ID *</label>
                            <input type="text" id="nagad_transaction" name="nagad_transaction" class="form-control" placeholder="Enter Nagad transaction ID">
                        </div>
                    </div>

                    <!-- Card Instructions -->
                    <div id="card_instructions" style="display: none; background: var(--light-green); padding: 1rem; border-radius: 4px; margin-top: 1rem;">
                        <h4 style="color: var(--primary-green); margin-bottom: 0.5rem;">Card Payment Instructions:</h4>
                        <ol style="margin: 0; padding-left: 1.2rem;">
                            <li>Complete the payment using your credit/debit card</li>
                            <li>Enter the transaction ID provided by your bank</li>
                        </ol>
                        <div class="form-group" style="margin-top: 1rem;">
                            <label for="card_transaction">Card Transaction ID *</label>
                            <input type="text" id="card_transaction" name="card_transaction" class="form-control" placeholder="Enter card transaction ID">
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" class="btn" style="width: 100%; margin-top: 1.5rem;">
                        Place Order
                    </button>
                </form>
            </div>
            
            <!-- Order & Payment -->
            <div>
                <!-- Order Summary -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow); margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3>Order Summary (<?php echo $total_items; ?> items)</h3>
                        <a href="cart.php" class="btn btn-outline" style="padding: 0.5rem 1rem;">Edit Cart</a>
                    </div>
                    
                    <!-- Cart Update Form -->
                    <form method="post" id="cart-update-form">
                        <input type="hidden" name="update_cart" value="1">
                        
                        <?php foreach($cart as $item): 
                            // Get current stock for each product
                            $stock_query = "SELECT stock_quantity FROM products WHERE id = ?";
                            $stock_stmt = $db->prepare($stock_query);
                            $stock_stmt->execute([$item['product_id']]);
                            $current_stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
                            $max_quantity = $current_stock ? $current_stock['stock_quantity'] : 0;
                            
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--medium-gray);">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <div>
                                        <p style="margin: 0; font-weight: bold;"><?php echo $item['name']; ?></p>
                                        <p style="margin: 0; color: var(--text-color); font-size: 0.9rem;">
                                            Qty: 
                                            <input type="number" 
                                                   name="quantities[<?php echo $item['product_id']; ?>]" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $max_quantity; ?>"
                                                   style="width: 60px; padding: 0.2rem;"
                                                   class="checkout-quantity"
                                                   data-product-id="<?php echo $item['product_id']; ?>"
                                                   data-price="<?php echo $item['price']; ?>">
                                        </p>
                                        <p style="margin: 0; color: var(--primary-green); font-size: 0.9rem;">
                                            $<?php echo number_format($item['price'], 2); ?> each
                                        </p>
                                        <p style="margin: 0; color: var(--text-light); font-size: 0.8rem;">
                                            Available: <?php echo $max_quantity; ?>
                                        </p>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <p style="margin: 0; font-weight: bold; margin-bottom: 0.5rem;">
                                        $<span class="checkout-item-total" data-product-id="<?php echo $item['product_id']; ?>">
                                            <?php echo number_format($item_total, 2); ?>
                                        </span>
                                    </p>
                                    <!-- Separate remove form -->
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="remove_item" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.6rem; background: #dc3545; border-color: #dc3545; color: white; font-size: 0.8rem;">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 1rem;">
                            <button type="submit" class="btn" style="padding: 0.5rem 1rem;">Update Quantities</button>
                        </div>
                    </form>
                    
                    <div style="margin-top: 1rem; border-top: 1px solid var(--medium-gray); padding-top: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Subtotal:</span>
                            <span>$<span id="checkout-subtotal"><?php echo number_format($subtotal, 2); ?></span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Shipping:</span>
                            <span>$<span id="checkout-shipping"><?php echo number_format($shipping, 2); ?></span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Tax:</span>
                            <span>$<span id="checkout-tax"><?php echo number_format($tax, 2); ?></span></span>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total:</span>
                            <span>$<span id="checkout-total"><?php echo number_format($total, 2); ?></span></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Update item total when quantity changes
function updateCheckoutItemTotal(productId, price) {
    const quantityInput = document.querySelector(`.checkout-quantity[data-product-id="${productId}"]`);
    const quantity = parseInt(quantityInput.value);
    const maxQuantity = parseInt(quantityInput.getAttribute('max'));
    
    // Validate quantity against stock
    if (quantity > maxQuantity) {
        alert('Only ' + maxQuantity + ' items available in stock!');
        quantityInput.value = maxQuantity;
        return;
    }
    
    if (quantity < 1) {
        quantityInput.value = 1;
        return;
    }
    
    const itemTotal = price * quantity;
    document.querySelector(`.checkout-item-total[data-product-id="${productId}"]`).textContent = itemTotal.toFixed(2);
    
    // Update all totals
    updateCheckoutTotals();
}

function updateCheckoutTotals() {
    let subtotal = 0;
    const itemTotals = document.querySelectorAll('.checkout-item-total');
    
    itemTotals.forEach(element => {
        subtotal += parseFloat(element.textContent);
    });
    
    const shipping = 10.00;
    const tax = subtotal * 0.1;
    const total = subtotal + shipping + tax;
    
    document.getElementById('checkout-subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('checkout-tax').textContent = tax.toFixed(2);
    document.getElementById('checkout-total').textContent = total.toFixed(2);
    
    // Update payment amounts
    document.querySelectorAll('.payment-amount').forEach(element => {
        element.textContent = total.toFixed(2);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all quantity inputs in checkout
    const quantityInputs = document.querySelectorAll('.checkout-quantity');
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.getAttribute('data-product-id');
            const price = parseFloat(this.getAttribute('data-price'));
            updateCheckoutItemTotal(productId, price);
        });
        
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const price = parseFloat(this.getAttribute('data-price'));
            updateCheckoutItemTotal(productId, price);
        });
    });
    
    // Payment method change handler
    document.getElementById('payment_method').addEventListener('change', function() {
        const bkashDiv = document.getElementById('bkash_instructions');
        const nagadDiv = document.getElementById('nagad_instructions');
        const cardDiv = document.getElementById('card_instructions');
        const bkashInput = document.getElementById('bkash_transaction');
        const nagadInput = document.getElementById('nagad_transaction');
        const cardInput = document.getElementById('card_transaction');
        
        // Hide all first
        bkashDiv.style.display = 'none';
        nagadDiv.style.display = 'none';
        cardDiv.style.display = 'none';
        bkashInput.required = false;
        nagadInput.required = false;
        cardInput.required = false;
        
        // Clear all transaction fields
        bkashInput.value = '';
        nagadInput.value = '';
        cardInput.value = '';
        
        // Show selected
        if(this.value === 'bkash') {
            bkashDiv.style.display = 'block';
            bkashInput.required = true;
        } else if(this.value === 'nagad') {
            nagadDiv.style.display = 'block';
            nagadInput.required = true;
        } else if(this.value === 'card') {
            cardDiv.style.display = 'block';
            cardInput.required = true;
        }
    });
    
    // Validate checkout form before submission
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            let hasErrors = false;
            const quantityInputs = document.querySelectorAll('.checkout-quantity');
            
            quantityInputs.forEach(input => {
                const quantity = parseInt(input.value);
                const maxQuantity = parseInt(input.getAttribute('max'));
                
                if (quantity > maxQuantity) {
                    alert('Quantity for one or more items exceeds available stock! Please update quantities before placing order.');
                    hasErrors = true;
                    input.focus();
                }
            });
            
            // Validate transaction ID for online payments
            const paymentMethod = document.getElementById('payment_method').value;
            if (['bkash', 'nagad', 'card'].includes(paymentMethod)) {
                let transactionId = '';
                if (paymentMethod === 'bkash') {
                    transactionId = document.getElementById('bkash_transaction').value.trim();
                } else if (paymentMethod === 'nagad') {
                    transactionId = document.getElementById('nagad_transaction').value.trim();
                } else if (paymentMethod === 'card') {
                    transactionId = document.getElementById('card_transaction').value.trim();
                }
                
                if (!transactionId) {
                    alert('Transaction ID is required for ' + paymentMethod.toUpperCase() + ' payments.');
                    hasErrors = true;
                }
            }
            
            if (hasErrors) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>