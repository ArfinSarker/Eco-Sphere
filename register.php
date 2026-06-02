<?php
$page_title = "Register";
include 'includes/header.php';

if($_POST) {
    $database = new Database();
    $db = $database->getConnection();
    
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $role_request = $_POST['role_request'] ?? 'customer';
    
    // Validate inputs
    $errors = [];
    
    if(empty($username)) $errors[] = "Username is required";
    if(empty($email)) $errors[] = "Email is required";
    if(empty($password)) $errors[] = "Password is required";
    if($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$username, $email]);
    if($check_stmt->rowCount() > 0) {
        $errors[] = "Username or email already exists";
    }
    
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set role based on request and approval system
        $role = 'customer';
        $needs_approval = false;
        
        if($role_request == 'admin') {
            $role = 'pending_admin';
            $needs_approval = true;
        }
        
        $insert_query = "INSERT INTO users (username, email, password, first_name, last_name, role) 
                         VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $db->prepare($insert_query);
        
        if($insert_stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $role])) {
            if($needs_approval) {
                $_SESSION['success'] = "Registration successful! Your admin account request has been sent for approval.";
            } else {
                $_SESSION['success'] = "Registration successful! Please login.";
            }
            header("Location: login.php");
            exit;
        } else {
            $errors[] = "Something went wrong. Please try again.";
        }
    }
}
?>

<style>
    /* Eco-friendly Color Scheme */
    :root {
        --primary-green: #2ecc71;
        --dark-green: #27ae60;
        --light-green: #a3e4a3;
        --leaf-green: #58d68d;
        --eco-brown: #8d6e63;
        --eco-tan: #d7ccc8;
        --white: #ffffff;
        --light-gray: #f5f5f5;
        --text-dark: #2c3e50;
        --error-red: #e74c3c;
        --success-green: #2ecc71;
    }
    
    .register-hero {
        background: linear-gradient(135deg, var(--light-green) 0%, var(--primary-green) 100%);
        min-height: 100vh;
        padding: 40px 0;
    }
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .register-content {
        display: flex;
        background: var(--white);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .form-container {
        flex: 1;
        padding: 40px;
    }
    
    .register-header {
        text-align: center;
        margin-bottom: 30px;
    }
    
    .register-header h2 {
        color: var(--text-dark);
        margin-bottom: 10px;
        font-size: 32px;
    }
    
    .register-header p {
        color: #666;
        line-height: 1.6;
    }
    
    .alert {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .alert-error {
        background-color: rgba(231, 76, 60, 0.1);
        border-left: 4px solid var(--error-red);
        color: var(--error-red);
    }
    
    .alert-content {
        display: flex;
        align-items: flex-start;
    }
    
    .alert-content i {
        margin-right: 10px;
        font-size: 18px;
        margin-top: 2px;
    }
    
    .register-form {
        width: 100%;
    }
    
    .form-row {
        display: flex;
        gap: 15px;
    }
    
    .form-group {
        margin-bottom: 20px;
        flex: 1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--text-dark);
        font-weight: 500;
    }
    
    .input-with-icon {
        position: relative;
        display: flex;
        align-items: center;
    }
    
    .input-with-icon i {
        position: absolute;
        left: 15px;
        color: var(--primary-green);
        font-size: 18px;
    }
    
    .form-control {
        width: 100%;
        padding: 15px 15px 15px 50px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 16px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus {
        border-color: var(--primary-green);
        outline: none;
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
    }
    
    select.form-control {
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%232ecc71' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 16px;
        padding-right: 40px;
    }
    
    .form-help {
        display: block;
        margin-top: 5px;
        color: #777;
        font-size: 14px;
    }
    
    .password-toggle {
        position: absolute;
        right: 15px;
        background: none;
        border: none;
        color: #777;
        cursor: pointer;
        font-size: 16px;
    }
    
    .checkbox-container {
        display: flex;
        align-items: flex-start;
        position: relative;
        padding-left: 35px;
        margin-bottom: 12px;
        cursor: pointer;
        font-size: 16px;
        color: var(--text-dark);
    }
    
    .checkbox-container input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
        height: 0;
        width: 0;
    }
    
    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 25px;
        width: 25px;
        background-color: #eee;
        border-radius: 5px;
        transition: all 0.3s ease;
    }
    
    .checkbox-container:hover input ~ .checkmark {
        background-color: #ccc;
    }
    
    .checkbox-container input:checked ~ .checkmark {
        background-color: var(--primary-green);
    }
    
    .checkmark:after {
        content: "";
        position: absolute;
        display: none;
    }
    
    .checkbox-container input:checked ~ .checkmark:after {
        display: block;
    }
    
    .checkbox-container .checkmark:after {
        left: 9px;
        top: 5px;
        width: 7px;
        height: 12px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }
    
    .btn {
        padding: 15px 20px;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
    }
    
    .btn-register {
        background-color: var(--primary-green);
        color: white;
    }
    
    .btn-register:hover {
        background-color: var(--dark-green);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
    }
    
    .register-footer {
        margin-top: 20px;
        text-align: center;
    }
    
    .register-footer p {
        color: #666;
    }
    
    .login-link {
        color: var(--primary-green);
        text-decoration: none;
        font-weight: 500;
    }
    
    .login-link:hover {
        text-decoration: underline;
    }
    
    .register-benefits {
        flex: 1;
        background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
        padding: 40px;
        color: white;
    }
    
    .benefits-card {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    
    .benefits-card h3 {
        font-size: 28px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .benefit-item {
        display: flex;
        margin-bottom: 25px;
        align-items: flex-start;
    }
    
    .benefit-icon {
        background: rgba(255, 255, 255, 0.2);
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        flex-shrink: 0;
    }
    
    .benefit-icon i {
        font-size: 20px;
    }
    
    .benefit-content h4 {
        margin-bottom: 5px;
        font-size: 18px;
    }
    
    .benefit-content p {
        opacity: 0.9;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .register-content {
            flex-direction: column;
        }
        
        .register-benefits {
            order: -1;
        }
        
        .form-row {
            flex-direction: column;
            gap: 0;
        }
    }
</style>

<div class="register-hero">
    <div class="container">
        <div class="register-content">
            <div class="form-container register-form-container">
                <div class="register-header">
                    <h2>Join Eco-Sphere</h2>
                    <p>Create your account to start your sustainable shopping journey</p>
                </div>
                
                <?php if(isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-error">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                <?php foreach($errors as $error): ?>
                                    <p><?php echo $error; ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="register-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="first_name" name="first_name" class="form-control" 
                                       placeholder="Enter your first name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <div class="input-with-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" id="last_name" name="last_name" class="form-control" 
                                       placeholder="Enter your last name">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-at"></i>
                            <input type="text" id="username" name="username" class="form-control" required 
                                   placeholder="Choose a username">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <div class="input-with-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   placeholder="Enter your email address">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role_request">Account Type</label>
                        <div class="input-with-icon">
                            <i class="fas fa-user-tag"></i>
                            <select id="role_request" name="role_request" class="form-control">
                                <option value="customer">Customer Account</option>
                                <option value="admin">Admin Account (Requires Approval)</option>
                            </select>
                        </div>
                        <small class="form-help">
                            Admin accounts require approval from existing administrators.
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="password" name="password" class="form-control" required 
                                       placeholder="Create a password">
                                <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <div class="input-with-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                                       placeholder="Confirm your password">
                                <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="checkbox-container">
                            <input type="checkbox" id="terms" name="terms" required>
                            <span class="checkmark"></span>
                            I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-register">Create Account</button>
                </form>
                
                <div class="register-footer">
                    <p>Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                </div>
            </div>
            
            <div class="register-benefits">
                <div class="benefits-card">
                    <h3>Why Join Eco-Sphere?</h3>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Fast & Sustainable Delivery</h4>
                            <p>Get your eco-friendly products delivered with carbon-neutral shipping options</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-seedling"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Exclusive Eco Products</h4>
                            <p>Access our curated selection of sustainable and environmentally friendly products</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Member Discounts</h4>
                            <p>Enjoy special discounts and early access to new sustainable products</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div class="benefit-content">
                            <h4>Join Our Community</h4>
                            <p>Be part of a community dedicated to making the world a better place</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleIcon = passwordField.nextElementSibling.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php include 'includes/footer.php'; ?>