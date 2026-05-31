<?php
// Use correct relative path for config from admin folder
require_once __DIR__ . '/../../config/config.php';

// Check if user is logged in and is admin
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if($_SESSION['user_role'] != 'admin') {
    $_SESSION['error'] = "Access denied. Admin privileges required.";
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .admin-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            background: var(--dark-gray);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .admin-sidebar li {
            margin-bottom: 0.5rem;
        }
        
        .admin-sidebar a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            color: white;
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }
        
        .admin-sidebar a:hover, .admin-sidebar a.active {
            background: var(--primary-green);
            border-left-color: white;
        }
        
        .admin-main {
            padding: 2rem;
            margin-left: 250px;
            width: calc(100% - 250px);
        }
        
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            
            .admin-sidebar {
                position: static;
                width: 100%;
                height: auto;
                display: none;
            }
            
            .admin-main {
                margin-left: 0;
                width: 100%;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <a href="../index.php"><i class="fas fa-leaf"></i> Eco-Sphere</a>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../index.php" class="nav-link">View Site</a>
                    </li>
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">Admin Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php" class="nav-link">Logout (<?php echo $_SESSION['username']; ?>)</a>
                    </li>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>
    <main>