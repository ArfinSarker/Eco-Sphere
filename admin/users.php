<?php
require_once '../config/config.php';

$page_title = "Manage Users";

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

include 'includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Handle role update and approval
if(isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $role = $_POST['role'];
    
    // Prevent admin from changing their own role
    if($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot change your own role.";
    } else {
        $update_query = "UPDATE users SET role = ? WHERE id = ?";
        $update_stmt = $db->prepare($update_query);
        
        if($update_stmt->execute([$role, $user_id])) {
            $_SESSION['success'] = "User role updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update user role.";
        }
    }
    header("Location: users.php");
    exit;
}

// Handle admin approval
if(isset($_POST['approve_admin'])) {
    $user_id = $_POST['user_id'];
    
    $update_query = "UPDATE users SET role = 'admin' WHERE id = ? AND role = 'pending_admin'";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$user_id])) {
        $_SESSION['success'] = "Admin account approved successfully!";
    } else {
        $_SESSION['error'] = "Failed to approve admin account.";
    }
    header("Location: users.php");
    exit;
}

// Handle admin rejection
if(isset($_POST['reject_admin'])) {
    $user_id = $_POST['user_id'];
    
    $update_query = "UPDATE users SET role = 'customer' WHERE id = ? AND role = 'pending_admin'";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$user_id])) {
        $_SESSION['success'] = "Admin request rejected. User set as customer.";
    } else {
        $_SESSION['error'] = "Failed to reject admin request.";
    }
    header("Location: users.php");
    exit;
}

// Handle delete action
if(isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    // Prevent admin from deleting themselves
    if($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
    } else {
        $delete_query = "DELETE FROM users WHERE id = ?";
        $delete_stmt = $db->prepare($delete_query);
        
        if($delete_stmt->execute([$user_id])) {
            $_SESSION['success'] = "User deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete user.";
        }
    }
    header("Location: users.php");
    exit;
}

// Filter by role
$role_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Build query with filter
$where_conditions = [];
$params = [];

if($role_filter != 'all') {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get users with order statistics
$users_query = "SELECT u.*, 
                       COUNT(o.id) as total_orders,
                       SUM(CASE WHEN o.status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                       SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                       SUM(CASE WHEN o.status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                       SUM(o.total) as total_spent
                FROM users u 
                LEFT JOIN orders o ON u.id = o.user_id 
                $where_clause 
                GROUP BY u.id 
                ORDER BY 
                    CASE 
                        WHEN u.role = 'pending_admin' THEN 0
                        WHEN u.role = 'admin' THEN 1
                        ELSE 2
                    END,
                    u.created_at DESC";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute($params);
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for filter
$counts_query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$counts_stmt = $db->prepare($counts_query);
$counts_stmt->execute();
$role_counts = [
    'all' => 0,
    'admin' => 0,
    'pending_admin' => 0,
    'customer' => 0
];

while($row = $counts_stmt->fetch(PDO::FETCH_ASSOC)) {
    $role_counts[$row['role']] = $row['count'];
    $role_counts['all'] += $row['count'];
}
?>

<div class="admin-container">
    <div class="admin-sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-leaf"></i>
                <span>Admin Panel</span>
            </div>
        </div>
        <ul class="sidebar-menu">
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="products.php"><i class="fas fa-tree"></i> Products</a></li>
            <li><a href="categories.php"><i class="fas fa-tags"></i> Categories</a></li>
            <li><a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="order-history.php"><i class="fas fa-history"></i> Order History</a></li>
            <li><a href="inquiries.php"><i class="fas fa-envelope"></i> Inquiries</a></li>
            <li><a href="testimonials.php"><i class="fas fa-star"></i> Testimonials</a></li>
            <li><a href="blog.php"><i class="fas fa-blog"></i> Blog Posts</a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i> Users</a></li>
            <li class="logout-link"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    
    <div class="admin-main">
        <div class="admin-header">
            <div class="header-content">
                <div class="page-info">
                    <h1>User Management</h1>
                    <p>Manage and monitor all system users</p>
                </div>
                <div class="header-actions">
                    <div class="user-count-badge">
                        <span class="count"><?php echo $role_counts['all']; ?></span>
                        <span class="label">Total Users</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <div class="alert-content">
                    <i class="fas fa-check-circle"></i>
                    <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                </div>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <div class="alert-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
                </div>
                <button class="alert-close">&times;</button>
            </div>
        <?php endif; ?>
        
        <!-- User Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total-users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $role_counts['all']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon admin-users">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $role_counts['admin']; ?></h3>
                    <p>Administrators</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-user-check"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon pending-users">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $role_counts['pending_admin']; ?></h3>
                    <p>Pending Approval</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon customer-users">
                    <i class="fas fa-user"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $role_counts['customer']; ?></h3>
                    <p>Customers</p>
                </div>
                <div class="stat-trend">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        
        <!-- Pending Admin Requests Alert -->
        <?php if($role_counts['pending_admin'] > 0): ?>
            <div class="alert alert-warning">
                <div class="alert-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <h4>Pending Admin Requests</h4>
                        <p>You have <?php echo $role_counts['pending_admin']; ?> pending admin approval request(s). Please review and take action.</p>
                    </div>
                </div>
                <a href="users.php?role=pending_admin" class="btn btn-outline">Review Requests</a>
            </div>
        <?php endif; ?>
        
        <!-- Role Filter -->
        <div class="filter-card">
            <h3>Filter by Role</h3>
            <div class="filter-buttons">
                <a href="users.php?role=all" class="filter-btn <?php echo $role_filter == 'all' ? 'active' : ''; ?>">
                    <span class="filter-count"><?php echo $role_counts['all']; ?></span>
                    <span class="filter-label">All Users</span>
                </a>
                <a href="users.php?role=admin" class="filter-btn <?php echo $role_filter == 'admin' ? 'active' : ''; ?>">
                    <span class="filter-count"><?php echo $role_counts['admin']; ?></span>
                    <span class="filter-label">Admins</span>
                </a>
                <a href="users.php?role=pending_admin" class="filter-btn <?php echo $role_filter == 'pending_admin' ? 'active' : ''; ?>">
                    <span class="filter-count"><?php echo $role_counts['pending_admin']; ?></span>
                    <span class="filter-label">Pending</span>
                </a>
                <a href="users.php?role=customer" class="filter-btn <?php echo $role_filter == 'customer' ? 'active' : ''; ?>">
                    <span class="filter-count"><?php echo $role_counts['customer']; ?></span>
                    <span class="filter-label">Customers</span>
                </a>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-card">
            <div class="table-header">
                <div class="table-title">
                    <h3>User Management</h3>
                    <p>Manage user roles, permissions and access</p>
                </div>
                <div class="table-actions">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchUsers" placeholder="Search users..." class="search-input">
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User Info</th>
                            <th>Role</th>
                            <th>Orders</th>
                            <th>Spending</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-users" style="font-size: 3rem; color: var(--medium-gray); margin-bottom: 1rem;"></i>
                                        <h3>No users found</h3>
                                        <p>No users match your current filter criteria</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($users as $user): ?>
                                <tr class="<?php echo $user['role'] == 'pending_admin' ? 'pending-user' : ''; ?>">
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar" style="background-color: <?php echo getAvatarColor($user['id']); ?>">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                            <div class="user-details">
                                                <h4>
                                                    <?php echo $user['username']; ?>
                                                    <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge badge-primary">You</span>
                                                    <?php endif; ?>
                                                </h4>
                                                <p class="user-email"><?php echo $user['email']; ?></p>
                                                <p class="user-name">
                                                    <?php echo $user['first_name'] ? $user['first_name'] . ' ' . $user['last_name'] : 'No name provided'; ?>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if($user['role'] == 'pending_admin'): ?>
                                            <span class="role-badge role-pending">
                                                <i class="fas fa-clock"></i> Pending Admin
                                            </span>
                                        <?php else: ?>
                                            <form method="post" class="role-form">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <select name="role" onchange="this.form.submit()" 
                                                        class="role-select role-<?php echo $user['role']; ?>"
                                                        <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                    <option value="customer" <?php echo $user['role'] == 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                </select>
                                                <input type="hidden" name="update_role" value="1">
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="order-stats">
                                            <div class="stat-item">
                                                <span class="stat-value"><?php echo $user['total_orders']; ?></span>
                                                <span class="stat-label">Total</span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-value text-success"><?php echo $user['completed_orders']; ?></span>
                                                <span class="stat-label">Completed</span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-value text-warning"><?php echo $user['pending_orders']; ?></span>
                                                <span class="stat-label">Pending</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="spending-info">
                                            <strong class="price">$<?php echo number_format($user['total_spent'] ?? 0, 2); ?></strong>
                                            <?php if($user['total_orders'] > 0): ?>
                                                <br><small class="text-muted">
                                                    $<?php echo number_format(($user['total_spent'] ?? 0) / $user['total_orders'], 2); ?> avg
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['total_orders'] > 0 ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['total_orders'] > 0 ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="date-info">
                                            <span class="date"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                                            <span class="time"><?php echo date('g:i A', strtotime($user['created_at'])); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($user['role'] == 'pending_admin'): ?>
                                                <div class="approval-actions">
                                                    <form method="post" class="inline-form">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="approve_admin" class="btn btn-icon btn-success" title="Approve Admin">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                    <form method="post" class="inline-form">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="reject_admin" class="btn btn-icon btn-danger" title="Reject Admin">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?delete=<?php echo $user['id']; ?>" class="btn btn-icon btn-danger" title="Delete User" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted text-small">Current User</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchUsers').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('.data-table tbody tr');
    
    rows.forEach(row => {
        const username = row.querySelector('.user-details h4').textContent.toLowerCase();
        const email = row.querySelector('.user-details .user-email').textContent.toLowerCase();
        const name = row.querySelector('.user-details .user-name').textContent.toLowerCase();
        
        if (username.includes(searchTerm) || email.includes(searchTerm) || name.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Alert close functionality
document.querySelectorAll('.alert-close').forEach(button => {
    button.addEventListener('click', function() {
        this.parentElement.style.display = 'none';
    });
});

// Function to generate consistent avatar colors
function getAvatarColor(userId) {
    const colors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
        '#9b59b6', '#1abc9c', '#d35400', '#c0392b'
    ];
    return colors[userId % colors.length];
}
</script>

<style>
:root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --success-color: #4cc9f0;
    --warning-color: #f72585;
    --danger-color: #e63946;
    --light-color: #f8f9fa;
    --dark-color: #212529;
    --gray-color: #6c757d;
    --border-color: #e0e0e0;
    --sidebar-bg: #1e293b;
    --sidebar-hover: #334155;
    --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fb;
    color: #333;
    line-height: 1.6;
}

.admin-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.admin-sidebar {
    width: 260px;
    background: var(--sidebar-bg);
    color: white;
    transition: var(--transition);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    z-index: 100;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
}

.logo i {
    font-size: 1.5rem;
    color: var(--success-color);
}

.sidebar-menu {
    list-style: none;
    padding: 1rem 0;
}

.sidebar-menu li {
    margin-bottom: 0.25rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.5rem;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: var(--transition);
}

.sidebar-menu a:hover {
    background: var(--sidebar-hover);
    color: white;
}

.sidebar-menu a.active {
    background: var(--primary-color);
    color: white;
    border-left: 4px solid var(--success-color);
}

.logout-link {
    margin-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 1rem;
}

.logout-link a {
    color: #f87171 !important;
}

/* Main Content Styles */
.admin-main {
    flex: 1;
    padding: 1.5rem;
    overflow-x: auto;
}

.admin-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-info h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark-color);
    margin-bottom: 0.25rem;
}

.page-info p {
    color: var(--gray-color);
}

.user-count-badge {
    background: var(--primary-color);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: 10px;
    text-align: center;
    box-shadow: var(--card-shadow);
}

.user-count-badge .count {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
}

.user-count-badge .label {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* Alert Styles */
.alert {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-left: 4px solid #10b981;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #f59e0b;
}

.alert-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.alert-content i {
    font-size: 1.25rem;
}

.alert-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    opacity: 0.7;
    transition: var(--transition);
}

.alert-close:hover {
    opacity: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    display: flex;
    align-items: center;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    color: white;
    font-size: 1.5rem;
}

.stat-icon.total-users {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.admin-users {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.pending-users {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-icon.customer-users {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
}

.stat-info h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.stat-info p {
    color: var(--gray-color);
    font-size: 0.875rem;
}

.stat-trend {
    margin-left: auto;
    color: var(--success-color);
    font-size: 1.25rem;
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: var(--card-shadow);
    margin-bottom: 2rem;
}

.filter-card h3 {
    margin-bottom: 1rem;
    font-weight: 600;
}

.filter-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem 1.5rem;
    background: var(--light-color);
    border-radius: 10px;
    text-decoration: none;
    color: var(--dark-color);
    transition: var(--transition);
    min-width: 100px;
}

.filter-btn:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.filter-btn.active {
    background: var(--primary-color);
    color: white;
}

.filter-count {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
}

.filter-label {
    font-size: 0.875rem;
}

/* Table Card */
.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: var(--card-shadow);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.table-title h3 {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.table-title p {
    color: var(--gray-color);
    font-size: 0.875rem;
}

.search-box {
    position: relative;
    width: 250px;
}

.search-box i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-color);
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    transition: var(--transition);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
}

/* Table Styles */
.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: #f8f9fa;
}

.data-table th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: var(--dark-color);
    border-bottom: 1px solid var(--border-color);
}

.data-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.data-table tbody tr {
    transition: var(--transition);
}

.data-table tbody tr:hover {
    background: #f8f9fa;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.pending-user {
    background: #fff9db !important;
}

.pending-user:hover {
    background: #fff3bf !important;
}

/* User Info Styles */
.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
}

.user-details h4 {
    margin: 0 0 0.25rem 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
}

.badge-primary {
    background: var(--primary-color);
    color: white;
}

.user-email {
    color: var(--gray-color);
    margin: 0 0 0.25rem 0;
    font-size: 0.875rem;
}

.user-name {
    font-size: 0.8rem;
    color: var(--text-light);
    margin: 0;
}

/* Role Badge Styles */
.role-badge {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: inline-block;
}

.role-pending {
    background: #fff3cd;
    color: #856404;
}

.role-form {
    margin: 0;
}

.role-select {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.8rem;
    width: 100%;
    transition: var(--transition);
}

.role-select:focus {
    outline: none;
    border-color: var(--primary-color);
}

.role-select.role-admin {
    border-color: #2196F3;
    background: #e3f2fd;
}

.role-select.role-customer {
    border-color: #4CAF50;
    background: #e8f5e8;
}

/* Order Stats */
.order-stats {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stat-value {
    font-weight: 600;
    font-size: 0.9rem;
}

.stat-label {
    font-size: 0.7rem;
    color: var(--text-light);
}

.text-success {
    color: #28a745 !important;
}

.text-warning {
    color: #ffc107 !important;
}

/* Spending Info */
.spending-info {
    text-align: center;
}

.price {
    font-size: 1rem;
    font-weight: 600;
}

/* Status Badge */
.status-badge {
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-inactive {
    background: #fef3c7;
    color: #92400e;
}

/* Date Info */
.date-info {
    display: flex;
    flex-direction: column;
}

.date {
    font-weight: 500;
}

.time {
    font-size: 0.75rem;
    color: var(--gray-color);
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    border: none;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
}

.btn-icon {
    width: 36px;
    height: 36px;
}

.btn-success {
    background: #10b981;
    color: white;
}

.btn-success:hover {
    background: #059669;
}

.btn-danger {
    background: #ef4444;
    color: white;
}

.btn-danger:hover {
    background: #dc2626;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
}

.approval-actions {
    display: flex;
    gap: 0.25rem;
}

.inline-form {
    display: inline;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 2rem;
}

.empty-state i {
    font-size: 3rem;
    color: var(--medium-gray);
    margin-bottom: 1rem;
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

.empty-state p {
    color: var(--gray-color);
}

.text-center {
    text-align: center;
}

.text-muted {
    color: var(--gray-color) !important;
}

.text-small {
    font-size: 0.75rem;
}

/* Responsive Styles */
@media (max-width: 1024px) {
    .admin-container {
        flex-direction: column;
    }
    
    .admin-sidebar {
        width: 100%;
        height: auto;
    }
    
    .sidebar-menu {
        display: flex;
        overflow-x: auto;
        padding: 0.5rem;
    }
    
    .sidebar-menu li {
        margin-bottom: 0;
        flex-shrink: 0;
    }
    
    .sidebar-menu a {
        padding: 0.75rem 1rem;
        white-space: nowrap;
    }
    
    .logout-link {
        margin-top: 0;
        border-top: none;
        padding-top: 0;
    }
}

@media (max-width: 768px) {
    .admin-main {
        padding: 1rem;
    }
    
    .header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-buttons {
        justify-content: center;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .search-box {
        width: 100%;
    }
    
    .user-info {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }
    
    .order-stats {
        flex-direction: row;
        justify-content: space-between;
    }
    
    .approval-actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .filter-buttons {
        flex-direction: column;
    }
    
    .filter-btn {
        flex-direction: row;
        justify-content: space-between;
    }
}
</style>

<?php 
// Helper function to generate consistent avatar colors
function getAvatarColor($userId) {
    $colors = [
        '#3498db', '#2ecc71', '#e74c3c', '#f39c12', 
        '#9b59b6', '#1abc9c', '#d35400', '#c0392b'
    ];
    return $colors[$userId % count($colors)];
}
?>

<?php include 'includes/footer.php'; ?>