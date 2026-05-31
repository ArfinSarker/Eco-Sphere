<?php
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Manage Blog Posts";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle form actions
if($_POST) {
    if(isset($_POST['add_post'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $excerpt = $_POST['excerpt'];
        $image_url = $_POST['image_url'];
        
        $insert_query = "INSERT INTO blog_posts (title, content, excerpt, image_url, author_id) 
                         VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if($insert_stmt->execute([$title, $content, $excerpt, $image_url, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Blog post added successfully!";
        } else {
            $error = "Failed to add blog post.";
        }
    }
    
    if(isset($_POST['update_post'])) {
        $id = $_POST['post_id'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        $excerpt = $_POST['excerpt'];
        $image_url = $_POST['image_url'];
        
        $update_query = "UPDATE blog_posts SET title = ?, content = ?, excerpt = ?, image_url = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if($update_stmt->execute([$title, $content, $excerpt, $image_url, $id])) {
            $_SESSION['success'] = "Blog post updated successfully!";
        } else {
            $error = "Failed to update blog post.";
        }
    }
}

// Handle delete action
if(isset($_GET['delete'])) {
    $delete_query = "DELETE FROM blog_posts WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if($delete_stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = "Blog post deleted successfully!";
    } else {
        $error = "Failed to delete blog post.";
    }
    header("Location: blog.php");
    exit;
}

// Get blog posts
$blog_query = "SELECT bp.*, u.username as author_name 
               FROM blog_posts bp 
               LEFT JOIN users u ON bp.author_id = u.id 
               ORDER BY bp.created_at DESC";
$blog_stmt = $db->prepare($blog_query);
$blog_stmt->execute();
$blog_posts = $blog_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get specific post for editing
$edit_post = null;
if(isset($_GET['edit'])) {
    $edit_query = "SELECT * FROM blog_posts WHERE id = ?";
    $edit_stmt = $db->prepare($edit_query);
    $edit_stmt->execute([$_GET['edit']]);
    $edit_post = $edit_stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <h3 style="padding: 0 1.5rem; margin-bottom: 1rem;">Admin Panel</h3>
        <ul>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php" class="active"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
            <h1>Manage Blog Posts</h1>
            <button onclick="document.getElementById('addPostModal').style.display='block'" class="btn">
                <i class="fas fa-plus"></i> Add New Post
            </button>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error">
                <p><?php echo $error; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="table-container" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-green); color: white;">
                        <th style="padding: 1rem; text-align: left;">Title</th>
                        <th style="padding: 1rem; text-align: left;">Author</th>
                        <th style="padding: 1rem; text-align: left;">Date</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($blog_posts)): ?>
                        <tr>
                            <td colspan="4" style="padding: 2rem; text-align: center;">No blog posts found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($blog_posts as $post): ?>
                            <tr style="border-bottom: 1px solid var(--medium-gray);">
                                <td style="padding: 1rem;"><?php echo $post['title']; ?></td>
                                <td style="padding: 1rem;"><?php echo $post['author_name']; ?></td>
                                <td style="padding: 1rem;"><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <a href="blog.php?edit=<?php echo $post['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; margin: 0 0.2rem;">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="blog.php?delete=<?php echo $post['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; margin: 0 0.2rem; background: #dc3545; border-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to delete this post?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add/Edit Post Modal -->
<div id="addPostModal" class="modal" style="display: <?php echo $edit_post ? 'block' : 'none'; ?>;">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; max-width: 800px; margin: 2rem auto; position: relative;">
        <span class="close" onclick="document.getElementById('addPostModal').style.display='none'" style="position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; cursor: pointer;">&times;</span>
        
        <h2><?php echo $edit_post ? 'Edit Blog Post' : 'Add New Blog Post'; ?></h2>
        
        <form method="post">
            <?php if($edit_post): ?>
                <input type="hidden" name="update_post" value="1">
                <input type="hidden" name="post_id" value="<?php echo $edit_post['id']; ?>">
            <?php else: ?>
                <input type="hidden" name="add_post" value="1">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="title">Post Title *</label>
                <input type="text" id="title" name="title" class="form-control" 
                       value="<?php echo $edit_post ? $edit_post['title'] : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="excerpt">Excerpt</label>
                <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php echo $edit_post ? $edit_post['excerpt'] : ''; ?></textarea>
                <small>Brief description that appears on blog listing</small>
            </div>
            
            <div class="form-group">
                <label for="content">Content *</label>
                <textarea id="content" name="content" class="form-control" rows="10" required><?php echo $edit_post ? $edit_post['content'] : ''; ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="image_url">Featured Image URL</label>
                <input type="text" id="image_url" name="image_url" class="form-control" 
                       value="<?php echo $edit_post ? $edit_post['image_url'] : ''; ?>" 
                       placeholder="https://example.com/image.jpg">
            </div>
            
            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn"><?php echo $edit_post ? 'Update Post' : 'Add Post'; ?></button>
                <button type="button" class="btn btn-outline" onclick="document.getElementById('addPostModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    overflow-y: auto;
}

.table-container {
    overflow-x: auto;
}

@media (max-width: 768px) {
    .admin-container {
        grid-template-columns: 1fr;
    }
    
    .admin-sidebar {
        display: none;
    }
    
    .table-container {
        font-size: 0.8rem;
    }
}
</style>

<script>
// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('addPostModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// If editing, scroll to modal
<?php if($edit_post): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('addPostModal').scrollIntoView({ behavior: 'smooth' });
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>