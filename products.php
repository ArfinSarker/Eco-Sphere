<?php
$page_title = "Our Products";
include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Get categories for filter
$categories_query = "SELECT * FROM categories";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build product query with filters
$where_conditions = [];
$params = [];

// Category filter
if(isset($_GET['category']) && !empty($_GET['category'])) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $_GET['category'];
}

// Search filter
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Price range filter
if(isset($_GET['min_price']) && !empty($_GET['min_price'])) {
    $where_conditions[] = "p.price >= ?";
    $params[] = $_GET['min_price'];
}

if(isset($_GET['max_price']) && !empty($_GET['max_price'])) {
    $where_conditions[] = "p.price <= ?";
    $params[] = $_GET['max_price'];
}

// Build final query
$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$products_query = "SELECT p.*, c.name as category_name, pi.image_url 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                   $where_clause 
                   ORDER BY p.created_at DESC";
$products_stmt = $db->prepare($products_query);
$products_stmt->execute($params);
$products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Our Trees & Plants</h2>
            <p>Discover our wide selection of eco-friendly plants</p>
        </div>
        
        <!-- Filters -->
        <div style="background: var(--light-green); padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;">
            <form method="get" class="filter-form">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo $_GET['search'] ?? ''; ?>" placeholder="Search products...">
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" class="form-control">
                            <option value="">All Categories</option>
                            <?php foreach($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo $category['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_price">Min Price</label>
                        <input type="number" id="min_price" name="min_price" class="form-control" 
                               value="<?php echo $_GET['min_price'] ?? ''; ?>" placeholder="0" min="0">
                    </div>
                    
                    <div class="form-group">
                        <label for="max_price">Max Price</label>
                        <input type="number" id="max_price" name="max_price" class="form-control" 
                               value="<?php echo $_GET['max_price'] ?? ''; ?>" placeholder="1000" min="0">
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn">Apply Filters</button>
                    <a href="products.php" class="btn btn-outline">Clear Filters</a>
                </div>
            </form>
        </div>
        
        <!-- Products Grid -->
        <?php if(empty($products)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3>No products found</h3>
                <p>Try adjusting your filters or search terms</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $product['image_url'] ?? 'images/placeholder.jpg'; ?>" alt="<?php echo $product['name']; ?>">
                        </div>
                        <div class="product-info">
                            <span style="background: var(--primary-green); color: white; padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem;">
                                <?php echo $product['category_name']; ?>
                            </span>
                            <h3><?php echo $product['name']; ?></h3>
                            <p class="product-price">$<?php echo $product['price']; ?></p>
                            <p class="product-stock">In stock: <?php echo $product['stock_quantity']; ?></p>
                            <a href="product-details.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>