<?php
require_once 'config/config.php';

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: products.php");
    exit;
}

// Get product details
$product_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = ?";
$product_stmt = $db->prepare($product_query);
$product_stmt->execute([$_GET['id']]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);

if(!$product) {
    header("Location: products.php");
    exit;
}

// Get product images
$images_query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
$images_stmt = $db->prepare($images_query);
$images_stmt->execute([$_GET['id']]);
$images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get similar products
$similar_query = "SELECT p.*, pi.image_url 
                  FROM products p 
                  LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                  WHERE p.category_id = ? AND p.id != ? 
                  LIMIT 4";
$similar_stmt = $db->prepare($similar_query);
$similar_stmt->execute([$product['category_id'], $_GET['id']]);
$similar_products = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = $product['name'] . " - Product Details";
include 'includes/header.php';

// Handle add to cart
if($_POST && isset($_POST['add_to_cart'])) {
    if(!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    
    // Check if product already in cart
    $found = false;
    foreach($_SESSION['cart'] as &$item) {
        if($item['product_id'] == $product_id) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $images[0]['image_url'] ?? 'images/placeholder.jpg'
        ];
    }
    
    $_SESSION['success'] = "Product added to cart successfully!";
    header("Location: cart.php");
    exit;
}

// Handle inquiry form submission
if($_POST && isset($_POST['send_inquiry'])) {
    $inquiry_name = $_POST['inquiry_name'];
    $inquiry_email = $_POST['inquiry_email'];
    $inquiry_message = $_POST['inquiry_message'];
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    
    $subject = "Inquiry about: " . $product['name'];
    $message = "Product: " . $product['name'] . "\n\n" . $inquiry_message;
    
    $insert_query = "INSERT INTO inquiries (user_id, name, email, subject, message) 
                     VALUES (?, ?, ?, ?, ?)";
    $insert_stmt = $db->prepare($insert_query);
    
    if($insert_stmt->execute([$user_id, $inquiry_name, $inquiry_email, $subject, $message])) {
        $inquiry_success = "Your inquiry has been sent! We'll get back to you soon.";
    } else {
        $inquiry_error = "Failed to send inquiry. Please try again.";
    }
}
?>

<section class="section">
    <div class="container">
        <!-- Breadcrumb -->
        <nav style="margin-bottom: 2rem;">
            <a href="index.php">Home</a> &gt; 
            <a href="products.php">Products</a> &gt; 
            <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a> &gt; 
            <span><?php echo $product['name']; ?></span>
        </nav>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; margin-bottom: 4rem;">
            <!-- Product Images -->
            <div>
                <div class="main-product-image" style="margin-bottom: 1rem;">
                    <img src="<?php echo $images[0]['image_url'] ?? 'images/placeholder.jpg'; ?>" 
                         alt="<?php echo $product['name']; ?>" 
                         style="width: 100%; border-radius: 8px; box-shadow: var(--shadow);">
                </div>
                
                <?php if(count($images) > 1): ?>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem;">
                        <?php foreach($images as $image): ?>
                            <img src="<?php echo $image['image_url']; ?>" 
                                 alt="<?php echo $product['name']; ?>" 
                                 class="thumbnail <?php echo $image['is_primary'] ? 'active' : ''; ?>"
                                 style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; cursor: pointer; border: 2px solid <?php echo $image['is_primary'] ? 'var(--primary-green)' : 'transparent'; ?>;">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div>
                <span style="background: var(--primary-green); color: white; padding: 0.3rem 0.8rem; border-radius: 3px; font-size: 0.8rem;">
                    <?php echo $product['category_name']; ?>
                </span>
                
                <h1 style="font-size: 2.2rem; margin: 1rem 0;"><?php echo $product['name']; ?></h1>
                
                <p class="product-price" style="font-size: 1.8rem; margin-bottom: 1.5rem;">$<?php echo $product['price']; ?></p>
                
                <div style="margin-bottom: 1.5rem;">
                    <p><strong>Availability:</strong> 
                        <?php if($product['stock_quantity'] > 0): ?>
                            <span style="color: var(--primary-green);">In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                        <?php else: ?>
                            <span style="color: red;">Out of Stock</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Add to Cart Form -->
                <?php if($product['stock_quantity'] > 0): ?>
                <div style="background: var(--light-green); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Add to Cart</h3>
                    
                    <form method="post">
                        <input type="hidden" name="add_to_cart" value="1">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="form-group">
                            <label for="quantity">Quantity:</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>" class="form-control" style="width: 100px;">
                        </div>
                        
                        <button type="submit" class="btn">
                            <i class="fas fa-shopping-cart"></i> Add to Cart
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <div style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem;">Description</h3>
                    <p style="line-height: 1.6;"><?php echo nl2br($product['description']); ?></p>
                </div>
                
                <!-- Inquiry Form -->
                <div style="background: var(--light-green); padding: 1.5rem; border-radius: 8px;">
                    <h3 style="margin-bottom: 1rem;">Interested in this plant?</h3>
                    
                    <?php if(isset($inquiry_success)): ?>
                        <div class="alert alert-success">
                            <p><?php echo $inquiry_success; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($inquiry_error)): ?>
                        <div class="alert alert-error">
                            <p><?php echo $inquiry_error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <input type="hidden" name="send_inquiry" value="1">
                        
                        <div class="form-group">
                            <label for="inquiry_name">Your Name *</label>
                            <input type="text" id="inquiry_name" name="inquiry_name" class="form-control" 
                                   value="<?php echo isset($_SESSION['user_id']) ? $_SESSION['username'] : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="inquiry_email">Your Email *</label>
                            <input type="email" id="inquiry_email" name="inquiry_email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="inquiry_message">Your Message *</label>
                            <textarea id="inquiry_message" name="inquiry_message" class="form-control" rows="4" required
                                      placeholder="I'm interested in this plant. Please contact me with more information..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn">Send Inquiry</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Similar Products -->
        <?php if(!empty($similar_products)): ?>
            <section>
                <h2 style="text-align: center; margin-bottom: 2rem;">Similar Products</h2>
                <div class="products-grid">
                    <?php foreach($similar_products as $similar): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo $similar['image_url'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $similar['name']; ?>">
                            </div>
                            <div class="product-info">
                                <h3><?php echo $similar['name']; ?></h3>
                                <p class="product-price">$<?php echo $similar['price']; ?></p>
                                <p class="product-stock">In stock: <?php echo $similar['stock_quantity']; ?></p>
                                <a href="product-details.php?id=<?php echo $similar['id']; ?>" class="btn btn-outline">View Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<script>
// Image gallery functionality
document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.querySelector('.main-product-image img');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            // Update main image
            mainImage.src = this.src;
            
            // Update active thumbnail
            thumbnails.forEach(t => t.style.borderColor = 'transparent');
            this.style.borderColor = 'var(--primary-green)';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>