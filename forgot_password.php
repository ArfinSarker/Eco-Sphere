<?php
require_once 'config/config.php';
$page_title = "Forgot Password";

// Initialize variables
$email = '';
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in database
        $query = "SELECT id, username FROM users WHERE email = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate unique token
            $token = bin2hex(random_bytes(50));
            
            // Delete any existing tokens for this email
            $delete_query = "DELETE FROM password_resets WHERE email = ?";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->execute([$email]);
            
            // Insert new token
            $insert_query = "INSERT INTO password_resets (email, token) VALUES (?, ?)";
            $insert_stmt = $db->prepare($insert_query);
            
            if ($insert_stmt->execute([$email, $token])) {
                // In a real application, you would send an email here
                // For this demo, we'll show the reset link on the page
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                
                $success = "Password reset instructions have been sent to your email.";
                
                // For demo purposes - show the link (remove this in production)
                $demo_message = "<div class='demo-info'>
                                <h4>Demo Information:</h4>
                                <p>In a real application, an email would be sent with this link:</p>
                                <p><a href='$reset_link' class='demo-link'>$reset_link</a></p>
                                </div>";
            } else {
                $error = "Failed to generate reset token. Please try again.";
            }
        } else {
            $error = "No account found with that email address.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - EcoSphere</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--light-green) 0%, var(--primary-green) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            display: flex;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            background: var(--white);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .auth-card {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            color: var(--primary-green);
            font-size: 24px;
            font-weight: 700;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .auth-header h1 {
            color: var(--text-dark);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .auth-header p {
            color: #666;
            line-height: 1.6;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid var(--error-red);
            color: var(--error-red);
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 4px solid var(--success-green);
            color: var(--success-green);
        }
        
        .alert-content {
            display: flex;
            align-items: center;
        }
        
        .alert-content i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .auth-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 20px;
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
        
        .input-with-icon input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .input-with-icon input:focus {
            border-color: var(--primary-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.2);
        }
        
        .btn {
            padding: 15px 20px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-green);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--dark-green);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        }
        
        .btn-full {
            width: 100%;
        }
        
        .auth-links {
            margin-top: 20px;
            text-align: center;
        }
        
        .auth-link {
            display: inline-flex;
            align-items: center;
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-link:hover {
            color: var(--dark-green);
        }
        
        .auth-link i {
            margin-right: 8px;
        }
        
        .auth-background {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--dark-green) 100%);
            position: relative;
            overflow: hidden;
        }
        
        .background-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%2327ae60' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
        }
        
        .floating-plants {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .plant {
            position: absolute;
            color: rgba(255, 255, 255, 0.7);
            font-size: 40px;
            animation: float 6s ease-in-out infinite;
        }
        
        .plant-1 {
            top: 20%;
            left: 20%;
            animation-delay: 0s;
        }
        
        .plant-2 {
            top: 60%;
            left: 70%;
            animation-delay: 2s;
        }
        
        .plant-3 {
            top: 40%;
            left: 50%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(5deg);
            }
            100% {
                transform: translateY(0) rotate(0deg);
            }
        }
        
        .demo-info {
            background-color: rgba(46, 204, 113, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            border-left: 4px solid var(--primary-green);
        }
        
        .demo-info h4 {
            color: var(--dark-green);
            margin-bottom: 10px;
        }
        
        .demo-info p {
            margin-bottom: 10px;
            color: #666;
        }
        
        .demo-link {
            color: var(--primary-green);
            word-break: break-all;
        }
        
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
            }
            
            .auth-background {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-leaf"></i>
                    <span>EcoSphere</span>
                </div>
                <h1>Reset Your Password</h1>
                <p>Enter your email address and we'll send you instructions to reset your password.</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <div class="alert-content">
                        <i class="fas fa-exclamation-circle"></i>
                        <p><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <div class="alert-content">
                        <i class="fas fa-check-circle"></i>
                        <p><?php echo $success; ?></p>
                    </div>
                </div>
                <?php if (isset($demo_message)) echo $demo_message; ?>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-with-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required placeholder="Enter your email address">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Send Reset Instructions</button>
            </form>
            
            <div class="auth-links">
                <a href="login.php" class="auth-link">
                    <i class="fas fa-arrow-left"></i>
                    Back to Login
                </a>
            </div>
        </div>
        
        <div class="auth-background">
            <div class="background-pattern"></div>
            <div class="floating-plants">
                <div class="plant plant-1">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="plant plant-2">
                    <i class="fas fa-leaf"></i>
                </div>
                <div class="plant plant-3">
                    <i class="fas fa-tree"></i>
                </div>
            </div>
        </div>
    </div>
</body>
</html>