<?php
require_once 'config/config.php';

$page_title = "Eco Blog & Tips";
include 'includes/header.php';

// Get blog posts with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$posts_per_page = 6;
$offset = ($page - 1) * $posts_per_page;

// Count total posts
$count_query = "SELECT COUNT(*) FROM blog_posts";
$total_posts = $db->query($count_query)->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

// Get posts for current page
$blog_query = "SELECT bp.*, u.username as author_name 
               FROM blog_posts bp 
               LEFT JOIN users u ON bp.author_id = u.id 
               ORDER BY bp.created_at DESC 
               LIMIT :limit OFFSET :offset";
$blog_stmt = $db->prepare($blog_query);
$blog_stmt->bindValue(':limit', $posts_per_page, PDO::PARAM_INT);
$blog_stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$blog_stmt->execute();
$blog_posts = $blog_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>Eco Blog & Tips</h2>
            <p>Learn how to care for your plants and live more sustainably</p>
        </div>

        <?php if(empty($blog_posts)): ?>
            <div style="text-align: center; padding: 3rem;">
                <h3>No blog posts yet</h3>
                <p>Check back soon for helpful plant care tips and eco-advice!</p>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach($blog_posts as $post): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <img src="<?php echo $post['image_url'] ?? 'images/blog-placeholder.jpg'; ?>" alt="<?php echo $post['title']; ?>" style="height: 200px; object-fit: cover;">
                        </div>
                        <div class="product-info">
                            <h3><?php echo $post['title']; ?></h3>
                            <p style="min-height: 60px;"><?php echo $post['excerpt'] ?? substr(strip_tags($post['content']), 0, 150) . '...'; ?></p>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                                <span style="font-size: 0.9rem; color: var(--text-color);">
                                    By <?php echo $post['author_name'] ?? 'Eco-Sphere Team'; ?>
                                </span>
                                <span style="font-size: 0.9rem; color: var(--text-color);">
                                    <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                                </span>
                            </div>
                            <a href="blog-post.php?id=<?php echo $post['id']; ?>" class="btn btn-outline" style="margin-top: 1rem; display: block; text-align: center;">Read More</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 3rem;">
                    <div class="pagination">
                        <?php if($page > 1): ?>
                            <a href="blog.php?page=<?php echo $page - 1; ?>" class="btn btn-outline">Previous</a>
                        <?php endif; ?>
                        
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="blog.php?page=<?php echo $i; ?>" class="btn <?php echo $i == $page ? '' : 'btn-outline'; ?>" style="margin: 0 0.25rem;">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($page < $total_pages): ?>
                            <a href="blog.php?page=<?php echo $page + 1; ?>" class="btn btn-outline">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>