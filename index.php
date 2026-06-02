<?php
$page_title = "Home - Grow Green, Live Clean";
include 'includes/header.php';

// Get featured products
$database = new Database();
$db = $database->getConnection();

$featured_query = "SELECT p.*, c.name as category_name, pi.image_url 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                   WHERE p.featured = 1 
                   LIMIT 6";
$featured_stmt = $db->prepare($featured_query);
$featured_stmt->execute();
$featured_products = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent blog posts
$blog_query = "SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 3";
$blog_stmt = $db->prepare($blog_query);
$blog_stmt->execute();
$blog_posts = $blog_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get testimonials
$testimonial_query = "SELECT t.*, u.username 
                      FROM testimonials t 
                      LEFT JOIN users u ON t.user_id = u.id 
                      WHERE t.status = 'approved' 
                      ORDER BY created_at DESC 
                      LIMIT 3";
$testimonial_stmt = $db->prepare($testimonial_query);
$testimonial_stmt->execute();
$testimonials = $testimonial_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Image Slider -->
<section class="slider-container">
    <div class="slider">
        <div class="slide active">
            <img src="images/slide1.jpg" alt="Beautiful Plants Collection">
            <div class="slide-content">
                <h2>Premium Quality Plants</h2>
                <p>Discover our exclusive collection of healthy, vibrant plants for your home and garden</p>
                <a href="products.php" class="btn">Shop Now</a>
            </div>
        </div>
        <div class="slide">
            <img src="images/slide2.jpg" alt="Gardening Tools">
            <div class="slide-content">
                <h2>Complete Gardening Solutions</h2>
                <p>Everything you need for your gardening journey in one place</p>
                <a href="products.php" class="btn">Explore Products</a>
            </div>
        </div>
        <div class="slide">
            <img src="images/slide3.jpg" alt="Plant Care">
            <div class="slide-content">
                <h2>Expert Plant Care Guidance</h2>
                <p>Learn how to keep your plants thriving with our expert tips</p>
                <a href="blog.php" class="btn">Learn More</a>
            </div>
        </div>
    </div>
    
    <button class="slider-btn prev-btn">❮</button>
    <button class="slider-btn next-btn">❯</button>
    
    <div class="slider-nav">
        <div class="slider-dot active"></div>
        <div class="slider-dot"></div>
        <div class="slider-dot"></div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Featured Plants</h2>
            <p>Our carefully selected plants that will transform your space</p>
        </div>
        <div class="products-grid">
            <?php foreach($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $product['image_url'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $product['name']; ?></h3>
                        <p class="product-price">$<?php echo $product['price']; ?></p>
                        <p class="product-stock">In stock: <?php echo $product['stock_quantity']; ?></p>
                        <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Rest of the existing index.php content remains the same -->
<section class="section" style="background: var(--light-green);">
    <div class="container">
        <div class="section-title">
            <h2>Eco Tips & Blog</h2>
            <p>Learn how to care for your plants and live sustainably</p>
        </div>
        <div class="products-grid">
            <?php foreach($blog_posts as $post): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="<?php echo $post['image_url'] ?? 'images/blog-placeholder.jpg'; ?>" alt="<?php echo $post['title']; ?>">
                    </div>
                    <div class="product-info">
                        <h3><?php echo $post['title']; ?></h3>
                        <p><?php echo $post['excerpt']; ?></p>
                        <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline">Read More</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>What Our Customers Say</h2>
            <p>Real experiences from our plant-loving community</p>
        </div>
        <div class="products-grid">
            <?php foreach($testimonials as $testimonial): ?>
                <div class="product-card">
                    <div class="product-info">
                        <div style="color: gold; margin-bottom: 1rem;">
                            <?php for($i = 0; $i < $testimonial['rating']; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <p>"<?php echo $testimonial['content']; ?>"</p>
                        <p style="margin-top: 1rem; font-weight: bold;">
                            - <?php echo $testimonial['username'] ?? 'Anonymous'; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
// Slider functionality
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.slider');
    const slides = document.querySelectorAll('.slide');
    const dots = document.querySelectorAll('.slider-dot');
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    let currentSlide = 0;
    
    function showSlide(n) {
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        currentSlide = (n + slides.length) % slides.length;
        
        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
        slider.style.transform = `translateX(-${currentSlide * 100}%)`;
    }
    
    function nextSlide() {
        showSlide(currentSlide + 1);
    }
    
    function prevSlide() {
        showSlide(currentSlide - 1);
    }
    
    // Auto slide every 5 seconds
    let slideInterval = setInterval(nextSlide, 5000);
    
    // Event listeners
    nextBtn.addEventListener('click', () => {
        clearInterval(slideInterval);
        nextSlide();
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    prevBtn.addEventListener('click', () => {
        clearInterval(slideInterval);
        prevSlide();
        slideInterval = setInterval(nextSlide, 5000);
    });
    
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            clearInterval(slideInterval);
            showSlide(index);
            slideInterval = setInterval(nextSlide, 5000);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>