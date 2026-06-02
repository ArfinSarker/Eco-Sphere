<?php
require_once 'config/config.php';

if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: blog.php");
    exit;
}

// Get blog post
$post_query = "SELECT bp.*, u.username as author_name 
               FROM blog_posts bp 
               LEFT JOIN users u ON bp.author_id = u.id 
               WHERE bp.id = ?";
$post_stmt = $db->prepare($post_query);
$post_stmt->execute([$_GET['id']]);
$post = $post_stmt->fetch(PDO::FETCH_ASSOC);

if(!$post) {
    header("Location: blog.php");
    exit;
}

$page_title = $post['title'] . " - Eco Blog";
include 'includes/header.php';

// Get related posts (excluding current post)
$related_query = "SELECT * FROM blog_posts 
                  WHERE id != ? 
                  ORDER BY created_at DESC 
                  LIMIT 3";
$related_stmt = $db->prepare($related_query);
$related_stmt->execute([$_GET['id']]);
$related_posts = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="section">
    <div class="container">
        <article style="max-width: 800px; margin: 0 auto;">
            <!-- Breadcrumb -->
            <nav style="margin-bottom: 2rem;">
                <a href="index.php">Home</a> &gt; 
                <a href="blog.php">Blog</a> &gt; 
                <span><?php echo $post['title']; ?></span>
            </nav>

            <!-- Blog Post Header -->
            <header style="text-align: center; margin-bottom: 3rem;">
                <h1 style="font-size: 2.5rem; margin-bottom: 1rem;"><?php echo $post['title']; ?></h1>
                <div style="display: flex; justify-content: center; gap: 2rem; color: var(--text-color); font-size: 0.9rem;">
                    <span>By <?php echo $post['author_name'] ?? 'Eco-Sphere Team'; ?></span>
                    <span>Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                </div>
            </header>

            <!-- Featured Image -->
            <?php if(!empty($post['image_url'])): ?>
                <div style="margin-bottom: 2rem;">
                    <img src="<?php echo $post['image_url']; ?>" alt="<?php echo $post['title']; ?>" style="width: 100%; border-radius: 8px;">
                </div>
            <?php else: ?>
                <div style="margin-bottom: 2rem;">
                    <img src="images/blog-placeholder.jpg" alt="<?php echo $post['title']; ?>" style="width: 100%; border-radius: 8px; height: 400px; object-fit: cover;">
                </div>
            <?php endif; ?>

            <!-- Blog Content -->
            <div style="line-height: 1.8; font-size: 1.1rem;">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <!-- Share Buttons -->
            <div style="margin: 3rem 0; padding: 2rem; background: var(--light-green); border-radius: 8px; text-align: center;">
                <h3 style="margin-bottom: 1rem;">Share This Post</h3>
                <div class="social-icons" style="justify-content: center;">
                    <a href="#" style="background: #3b5998;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" style="background: #1da1f2;"><i class="fab fa-twitter"></i></a>
                    <a href="#" style="background: #0077b5;"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" style="background: #e60023;"><i class="fab fa-pinterest"></i></a>
                </div>
            </div>
        </article>

        <!-- Related Posts -->
        <?php if(!empty($related_posts)): ?>
            <section style="margin-top: 4rem;">
                <h2 style="text-align: center; margin-bottom: 2rem;">You Might Also Like</h2>
                <div class="products-grid">
                    <?php foreach($related_posts as $related): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo $related['image_url'] ?? 'images/blog-placeholder.jpg'; ?>" alt="<?php echo $related['title']; ?>" style="height: 200px; object-fit: cover;">
                            </div>
                            <div class="product-info">
                                <h3><?php echo $related['title']; ?></h3>
                                <p><?php echo $related['excerpt'] ?? substr(strip_tags($related['content']), 0, 100) . '...'; ?></p>
                                <a href="blog-post.php?id=<?php echo $related['id']; ?>" class="btn btn-outline" style="margin-top: 1rem; display: block; text-align: center;">Read More</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>