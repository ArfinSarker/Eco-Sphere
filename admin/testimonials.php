<?php
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Manage Testimonials";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle status update
if(isset($_POST['update_status'])) {
    $testimonial_id = $_POST['testimonial_id'];
    $status = $_POST['status'];
    
    $update_query = "UPDATE testimonials SET status = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$status, $testimonial_id])) {
        $_SESSION['success'] = "Testimonial status updated successfully!";
    } else {
        $error = "Failed to update testimonial status.";
    }
}

// Handle delete action
if(isset($_GET['delete'])) {
    $delete_query = "DELETE FROM testimonials WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if($delete_stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = "Testimonial deleted successfully!";
    } else {
        $error = "Failed to delete testimonial.";
    }
    header("Location: testimonials.php");
    exit;
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';

// Build query with filter
$where_conditions = [];
$params = [];

if($status_filter != 'all') {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get testimonials
$testimonials_query = "SELECT t.*, u.username, u.email 
                       FROM testimonials t 
                       LEFT JOIN users u ON t.user_id = u.id 
                       $where_clause 
                       ORDER BY t.created_at DESC";
$testimonials_stmt = $db->prepare($testimonials_query);
$testimonials_stmt->execute($params);
$testimonials = $testimonials_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filter
$counts_query = "SELECT status, COUNT(*) as count FROM testimonials GROUP BY status";
$counts_stmt = $db->prepare($counts_query);
$counts_stmt->execute();
$status_counts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0
];

while($row = $counts_stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_counts[$row['status']] = $row['count'];
    $status_counts['all'] += $row['count'];
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
            <li><a href="testimonials.php" class="active"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <h1>Manage Testimonials</h1>
        
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
        
        <!-- Status Filter -->
        <div style="background: var(--light-green); padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">Filter by Status</h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="testimonials.php?status=all" class="btn <?php echo $status_filter == 'all' ? '' : 'btn-outline'; ?>">
                    All (<?php echo $status_counts['all']; ?>)
                </a>
                <a href="testimonials.php?status=pending" class="btn <?php echo $status_filter == 'pending' ? '' : 'btn-outline'; ?>" style="background: <?php echo $status_filter == 'pending' ? '#fff3e0' : ''; ?>; color: #ef6c00;">
                    Pending (<?php echo $status_counts['pending']; ?>)
                </a>
                <a href="testimonials.php?status=approved" class="btn <?php echo $status_filter == 'approved' ? '' : 'btn-outline'; ?>" style="background: <?php echo $status_filter == 'approved' ? '#e8f5e8' : ''; ?>; color: #2e7d32;">
                    Approved (<?php echo $status_counts['approved']; ?>)
                </a>
            </div>
        </div>
        
        <div class="table-container" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-green); color: white;">
                        <th style="padding: 1rem; text-align: left;">User</th>
                        <th style="padding: 1rem; text-align: left;">Testimonial</th>
                        <th style="padding: 1rem; text-align: center;">Rating</th>
                        <th style="padding: 1rem; text-align: center;">Date</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($testimonials)): ?>
                        <tr>
                            <td colspan="6" style="padding: 2rem; text-align: center;">No testimonials found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($testimonials as $testimonial): ?>
                            <tr style="border-bottom: 1px solid var(--medium-gray);">
                                <td style="padding: 1rem;">
                                    <?php if($testimonial['user_id']): ?>
                                        <strong><?php echo $testimonial['username']; ?></strong><br>
                                        <small><?php echo $testimonial['email']; ?></small>
                                    <?php else: ?>
                                        <strong>Anonymous User</strong>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo strlen($testimonial['content']) > 100 ? substr($testimonial['content'], 0, 100) . '...' : $testimonial['content']; ?>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <div style="color: gold;">
                                        <?php for($i = 0; $i < 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i < $testimonial['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td style="padding: 1rem; text-align: center;"><?php echo date('M j, Y', strtotime($testimonial['created_at'])); ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <form method="post" style="display: inline-block;">
                                        <input type="hidden" name="testimonial_id" value="<?php echo $testimonial['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 0.3rem; border-radius: 3px; border: 1px solid var(--medium-gray);">
                                            <option value="pending" <?php echo $testimonial['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="approved" <?php echo $testimonial['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <a href="testimonials.php?delete=<?php echo $testimonial['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; margin: 0 0.2rem; background: #dc3545; border-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to delete this testimonial?')">
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

<?php include '../includes/footer.php'; ?>