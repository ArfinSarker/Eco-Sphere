<?php
// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$page_title = "Manage Inquiries";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle status update
if(isset($_POST['update_status'])) {
    $inquiry_id = $_POST['inquiry_id'];
    $status = $_POST['status'];
    
    $update_query = "UPDATE inquiries SET status = ? WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$status, $inquiry_id])) {
        $_SESSION['success'] = "Inquiry status updated successfully!";
    } else {
        $error = "Failed to update inquiry status.";
    }
}

// Handle delete action
if(isset($_GET['delete'])) {
    $delete_query = "DELETE FROM inquiries WHERE id = ?";
    $delete_stmt = $db->prepare($delete_query);
    
    if($delete_stmt->execute([$_GET['delete']])) {
        $_SESSION['success'] = "Inquiry deleted successfully!";
    } else {
        $error = "Failed to delete inquiry.";
    }
    header("Location: inquiries.php");
    exit;
}

// Filter by status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build query with filter
$where_conditions = [];
$params = [];

if($status_filter != 'all') {
    $where_conditions[] = "i.status = ?";
    $params[] = $status_filter;
}

$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get inquiries
$inquiries_query = "SELECT i.*, u.username, u.email as user_email 
                    FROM inquiries i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    $where_clause 
                    ORDER BY i.created_at DESC";
$inquiries_stmt = $db->prepare($inquiries_query);
$inquiries_stmt->execute($params);
$inquiries = $inquiries_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filter
$counts_query = "SELECT status, COUNT(*) as count FROM inquiries GROUP BY status";
$counts_stmt = $db->prepare($counts_query);
$counts_stmt->execute();
$status_counts = [
    'all' => 0,
    'new' => 0,
    'in_progress' => 0,
    'resolved' => 0
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
            <li><a href="inquiries.php" class="active"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <h1>Manage Inquiries</h1>
        
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
                <a href="inquiries.php?status=all" class="btn <?php echo $status_filter == 'all' ? '' : 'btn-outline'; ?>">
                    All (<?php echo $status_counts['all']; ?>)
                </a>
                <a href="inquiries.php?status=new" class="btn <?php echo $status_filter == 'new' ? '' : 'btn-outline'; ?>" style="background: <?php echo $status_filter == 'new' ? '#e3f2fd' : ''; ?>; color: #1565c0;">
                    New (<?php echo $status_counts['new']; ?>)
                </a>
                <a href="inquiries.php?status=in_progress" class="btn <?php echo $status_filter == 'in_progress' ? '' : 'btn-outline'; ?>" style="background: <?php echo $status_filter == 'in_progress' ? '#fff3e0' : ''; ?>; color: #ef6c00;">
                    In Progress (<?php echo $status_counts['in_progress']; ?>)
                </a>
                <a href="inquiries.php?status=resolved" class="btn <?php echo $status_filter == 'resolved' ? '' : 'btn-outline'; ?>" style="background: <?php echo $status_filter == 'resolved' ? '#e8f5e8' : ''; ?>; color: #2e7d32;">
                    Resolved (<?php echo $status_counts['resolved']; ?>)
                </a>
            </div>
        </div>
        
        <div class="table-container" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: var(--shadow);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-green); color: white;">
                        <th style="padding: 1rem; text-align: left;">Subject</th>
                        <th style="padding: 1rem; text-align: left;">From</th>
                        <th style="padding: 1rem; text-align: left;">Date</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                        <th style="padding: 1rem; text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($inquiries)): ?>
                        <tr>
                            <td colspan="5" style="padding: 2rem; text-align: center;">No inquiries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($inquiries as $inquiry): ?>
                            <tr style="border-bottom: 1px solid var(--medium-gray);">
                                <td style="padding: 1rem;">
                                    <strong><?php echo $inquiry['subject']; ?></strong>
                                    <?php if(strlen($inquiry['message']) > 100): ?>
                                        <br><small><?php echo substr($inquiry['message'], 0, 100) . '...'; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?php echo $inquiry['name']; ?><br>
                                    <small><?php echo $inquiry['email']; ?></small>
                                    <?php if($inquiry['user_id']): ?>
                                        <br><small style="color: var(--primary-green);">Registered User</small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem;"><?php echo date('M j, Y g:i A', strtotime($inquiry['created_at'])); ?></td>
                                <td style="padding: 1rem; text-align: center;">
                                    <form method="post" style="display: inline-block;">
                                        <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" style="padding: 0.3rem; border-radius: 3px; border: 1px solid var(--medium-gray);">
                                            <option value="new" <?php echo $inquiry['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                            <option value="in_progress" <?php echo $inquiry['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                            <option value="resolved" <?php echo $inquiry['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td style="padding: 1rem; text-align: center;">
                                    <button onclick="viewInquiry(<?php echo $inquiry['id']; ?>)" class="btn btn-outline" style="padding: 0.3rem 0.8rem; margin: 0 0.2rem;">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="inquiries.php?delete=<?php echo $inquiry['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; margin: 0 0.2rem; background: #dc3545; border-color: #dc3545; color: white;" onclick="return confirm('Are you sure you want to delete this inquiry?')">
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

<!-- View Inquiry Modal -->
<div id="viewInquiryModal" class="modal">
    <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; max-width: 800px; margin: 2rem auto; position: relative;">
        <span class="close" onclick="document.getElementById('viewInquiryModal').style.display='none'" style="position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; cursor: pointer;">&times;</span>
        
        <h2>Inquiry Details</h2>
        <div id="inquiryDetails"></div>
    </div>
</div>

<script>
function viewInquiry(inquiryId) {
    // In a real application, you would fetch the inquiry details via AJAX
    // For this example, we'll redirect to a details page or show a modal with existing data
    
    // Since we don't have AJAX implemented, let's show a message
    document.getElementById('inquiryDetails').innerHTML = `
        <p><strong>Loading inquiry details...</strong></p>
        <p>In a complete implementation, this would show the full inquiry message and allow you to reply directly.</p>
        <p>For now, you can view the inquiry in the table above.</p>
    `;
    document.getElementById('viewInquiryModal').style.display = 'block';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('viewInquiryModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

<?php include '../includes/footer.php'; ?>