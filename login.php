<?php
require_once 'config/config.php';

$page_title = "Login";
include 'includes/header.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    if($_SESSION['user_role'] == 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

if($_POST) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['success'] = "Welcome back, " . $user['username'] . "!";
        
        // Redirect based on role
        if($user['role'] == 'admin') {
            header("Location: admin/index.php");
            exit;
        } else {
            header("Location: index.php");
            exit;
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<div class="login-hero">
    <div class="container">
        <div class="login-content">
            <div class="form-container">
                <div class="login-header">
                    <h2>Login to Your Account</h2>
                    <p>Access your Eco-Sphere account to continue shopping</p>
                </div>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-error">
                        <p><?php echo $error; ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="login-form">
                    <div class="form-group">
                        <label for="username">Username or Email</label>
                        <input type="text" id="username" name="username" class="form-control" required 
                               value="<?php echo isset($_POST['username']) ? $_POST['username'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-login">Login</button>
                </form>
                
                <div class="login-footer">
                    <p>Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                </div>
            </div>
            
            <div class="login-features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Fast Delivery</h3>
                    <p>Get your eco-friendly products delivered quickly</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Eco-Friendly</h3>
                    <p>All our products are sustainable and environmentally friendly</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Shopping</h3>
                    <p>Your personal information is always protected</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.login-hero {
    background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('../images/login-bg.jpg') no-repeat center center/cover;
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 6rem 0 2rem;
    margin-top: 60px;
}

.login-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    align-items: center;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-header h2 {
    color: var(--dark-gray);
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.login-header p {
    color: var(--text-light);
    font-size: 1rem;
}

.login-form {
    margin-top: 1.5rem;
}

.btn-login {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.checkbox-container {
    display: flex;
    align-items: center;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-color);
}

.checkbox-container input {
    margin-right: 0.5rem;
}

.forgot-password {
    color: var(--primary-green);
    text-decoration: none;
    font-size: 0.9rem;
    transition: var(--transition);
}

.forgot-password:hover {
    color: var(--dark-green);
    text-decoration: underline;
}

.login-footer {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--medium-gray);
}

.register-link {
    color: var(--primary-green);
    text-decoration: none;
    font-weight: 500;
    transition: var(--transition);
}

.register-link:hover {
    color: var(--dark-green);
    text-decoration: underline;
}

.login-features {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.feature-card {
    background: var(--white);
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: var(--shadow);
    text-align: center;
    transition: var(--transition);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: var(--light-green);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: var(--primary-green);
    font-size: 1.5rem;
}

.feature-card h3 {
    color: var(--dark-gray);
    margin-bottom: 0.5rem;
}

.feature-card p {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .login-content {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .login-hero {
        padding: 5rem 0 2rem;
    }
}

@media (max-width: 480px) {
    .form-options {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .login-header h2 {
        font-size: 1.5rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>