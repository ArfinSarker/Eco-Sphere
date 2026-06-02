<?php
$page_title = "Shopping Cart";
include 'includes/header.php';

// Handle cart actions
if($_POST) {
    if(isset($_POST['update_cart'])) {
        $database = new Database();
        $db = $database->getConnection();
        
        foreach($_POST['quantities'] as $product_id => $quantity) {
            $quantity = intval($quantity);
            
            // Check stock availability
            $stock_query = "SELECT stock_quantity, name FROM products WHERE id = ?";
            $stock_stmt = $db->prepare($stock_query);
            $stock_stmt->execute([$product_id]);
            $stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
            
            if($stock) {
                if($quantity > $stock['stock_quantity']) {
                    $_SESSION['error'] = "Only " . $stock['stock_quantity'] . " items available for " . $stock['name'] . "!";
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
        }
        if(!isset($_SESSION['error'])) {
            $_SESSION['success'] = "Cart updated successfully!";
        }
        
        header("Location: cart.php");
        exit;
    }
    
    if(isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
            return $item['product_id'] != $product_id;
        });
        $_SESSION['success'] = "Item removed from cart!";
        header("Location: cart.php");
        exit;
    }
    
    if(isset($_POST['clear_cart'])) {
        unset($_SESSION['cart']);
        $_SESSION['success'] = "Cart cleared successfully!";
        header("Location: cart.php");
        exit;
    }
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$total_items = 0;

// Calculate totals
foreach($cart as $item) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    $total_items += $item['quantity'];
}
$shipping = 10.00;
$tax = $subtotal * 0.1;
$total = $subtotal + $shipping + $tax;
?>

<section class="section">
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 2rem;">Shopping Cart</h1>
        
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
        
        <?php if(empty($cart)): ?>
            <div style="text-align: center; padding: 3rem;">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our products and add some items to your cart.</p>
                <a href="products.php" class="btn">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Cart Items -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                        <h3>Cart Items (<?php echo count($cart); ?>) - Total Items: <?php echo $total_items; ?></h3>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="clear_cart" value="1">
                            <button type="submit" class="btn btn-outline" style="background: #dc3545; border-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to clear your cart?')">
                                Clear Cart
                            </button>
                        </form>
                    </div>
                    
                    <!-- Main cart update form -->
                    <form method="post" id="cart-form">
                        <input type="hidden" name="update_cart" value="1">
                        
                        <?php 
                        $database = new Database();
                        $db = $database->getConnection();
                        
                        foreach($cart as $item): 
                            // Get current stock for each product
                            $stock_query = "SELECT stock_quantity FROM products WHERE id = ?";
                            $stock_stmt = $db->prepare($stock_query);
                            $stock_stmt->execute([$item['product_id']]);
                            $current_stock = $stock_stmt->fetch(PDO::FETCH_ASSOC);
                            $max_quantity = $current_stock ? $current_stock['stock_quantity'] : 0;
                            
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                            <div style="display: grid; grid-template-columns: 100px 1fr auto auto; gap: 1rem; align-items: center; padding: 1rem 0; border-bottom: 1px solid var(--medium-gray);">
                                <!-- Product Image -->
                                <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px;">
                                
                                <!-- Product Info -->
                                <div>
                                    <h4 style="margin-bottom: 0.5rem;"><?php echo $item['name']; ?></h4>
                                    <p style="color: var(--primary-green); font-weight: bold; margin: 0;">$<?php echo number_format($item['price'], 2); ?> each</p>
                                    <p style="color: var(--text-light); font-size: 0.9rem; margin: 0.25rem 0 0 0;">
                                        Available: <?php echo $max_quantity; ?>
                                    </p>
                                </div>
                                
                                <!-- Quantity -->
                                <div>
                                    <label for="quantity_<?php echo $item['product_id']; ?>" style="font-size: 0.9rem; display: block; margin-bottom: 0.5rem;">Qty:</label>
                                    <input type="number" id="quantity_<?php echo $item['product_id']; ?>" 
                                           name="quantities[<?php echo $item['product_id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $max_quantity; ?>"
                                           class="form-control quantity-input" 
                                           style="width: 80px;"
                                           data-product-id="<?php echo $item['product_id']; ?>"
                                           data-price="<?php echo $item['price']; ?>"
                                           data-max="<?php echo $max_quantity; ?>">
                                    <small style="color: var(--text-light); display: block; margin-top: 0.25rem;">
                                        Max: <?php echo $max_quantity; ?>
                                    </small>
                                </div>
                                
                                <!-- Item Total & Remove Button -->
                                <div style="text-align: center;">
                                    <p style="font-weight: bold; margin-bottom: 0.5rem; font-size: 1.1rem;">
                                        $<span id="item_total_<?php echo $item['product_id']; ?>" class="item-total">
                                            <?php echo number_format($item_total, 2); ?>
                                        </span>
                                    </p>
                                    <!-- Separate remove form for each item -->
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="remove_item" value="1">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <button type="submit" class="btn btn-outline" style="padding: 0.3rem 0.8rem; background: #dc3545; border-color: #dc3545; color: white;">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
                            <a href="products.php" class="btn btn-outline">Continue Shopping</a>
                            <button type="submit" class="btn">Update Cart</button>
                        </div>
                    </form>
                </div>
                
                <!-- Order Summary -->
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: var(--shadow); height: fit-content;">
                    <h3 style="margin-bottom: 1.5rem;">Order Summary</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Subtotal (<?php echo $total_items; ?> items):</span>
                            <span>$<span id="subtotal_display"><?php echo number_format($subtotal, 2); ?></span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Shipping:</span>
                            <span>$<span id="shipping_display"><?php echo number_format($shipping, 2); ?></span></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Tax:</span>
                            <span>$<span id="tax_display"><?php echo number_format($tax, 2); ?></span></span>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total:</span>
                            <span>$<span id="total_display"><?php echo number_format($total, 2); ?></span></span>
                        </div>
                    </div>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="checkout.php" class="btn" style="width: 100%; text-align: center; margin-top: 1rem;">
                            Proceed to Checkout
                        </a>
                    <?php else: ?>
                        <div style="text-align: center;">
                            <p style="margin-bottom: 1rem;">Please login to checkout</p>
                            <a href="login.php" class="btn" style="width: 100%; text-align: center;">
                                Login to Checkout
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
function updateItemTotal(productId, price) {
    const quantityInput = document.getElementById('quantity_' + productId);
    const quantity = parseInt(quantityInput.value);
    const maxQuantity = parseInt(quantityInput.getAttribute('data-max'));
    
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
    document.getElementById('item_total_' + productId).textContent = itemTotal.toFixed(2);
    
    // Update all totals
    updateCartTotals();
}

function updateCartTotals() {
    let subtotal = 0;
    const itemTotals = document.querySelectorAll('.item-total');
    
    itemTotals.forEach(element => {
        subtotal += parseFloat(element.textContent);
    });
    
    const shipping = 10.00;
    const tax = subtotal * 0.1;
    const total = subtotal + shipping + tax;
    
    document.getElementById('subtotal_display').textContent = subtotal.toFixed(2);
    document.getElementById('tax_display').textContent = tax.toFixed(2);
    document.getElementById('total_display').textContent = total.toFixed(2);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to all quantity inputs
    const quantityInputs = document.querySelectorAll('.quantity-input');
    quantityInputs.forEach(input => {
        input.addEventListener('input', function() {
            const productId = this.getAttribute('data-product-id');
            const price = parseFloat(this.getAttribute('data-price'));
            updateItemTotal(productId, price);
        });
        
        input.addEventListener('change', function() {
            const productId = this.getAttribute('data-product-id');
            const price = parseFloat(this.getAttribute('data-price'));
            updateItemTotal(productId, price);
        });
    });
    
    // Validate form before submission
    const cartForm = document.getElementById('cart-form');
    if (cartForm) {
        cartForm.addEventListener('submit', function(e) {
            let hasErrors = false;
            const quantityInputs = document.querySelectorAll('.quantity-input');
            
            quantityInputs.forEach(input => {
                const quantity = parseInt(input.value);
                const maxQuantity = parseInt(input.getAttribute('data-max'));
                
                if (quantity > maxQuantity) {
                    alert('Quantity for one or more items exceeds available stock!');
                    hasErrors = true;
                    input.focus();
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>