<?php
// Flow: This is the main admin header file that's included at the top of all admin pages
// 1. Start session and include database connection
session_start();
require_once '../config/database.php';

// 2. Authentication check - redirects to login if not admin
// Flow: If not authenticated, redirects to login.php in root directory
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// 3. Get current page name for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// 4. HTML header starts here with meta tags and CSS includes
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drunkies Admin - Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Admin Dashboard CSS -->
    <style>
        :root {
            --admin-primary: #2C3E50;
            --admin-secondary: #34495E;
            --admin-accent: #E74C3C;
            --admin-text: #ECF0F1;
            --admin-highlight: #3498DB;
        }

        body {
            background-color: #f8f9fa;
        }

        /* Sidebar Styles */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: var(--admin-primary);
            color: var(--admin-text);
            padding-top: 1rem;
            transition: all 0.3s ease;
        }

        .admin-sidebar .nav-link {
            color: var(--admin-text);
            padding: 0.8rem 1rem;
            margin: 0.2rem 0;
            border-radius: 0.25rem;
            transition: all 0.3s ease;
        }

        .admin-sidebar .nav-link:hover {
            background: var(--admin-secondary);
            padding-left: 1.5rem;
        }

        .admin-sidebar .nav-link.active {
            background: var(--admin-accent);
            color: white;
        }

        /* Main Content Area */
        .admin-main {
            margin-left: 250px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        /* Header Styles */
        .admin-header {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Card Styles */
        .admin-card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .admin-card:hover {
            transform: translateY(-5px);
        }

        /* Stats Cards */
        .stats-card {
            padding: 1.5rem;
            border-radius: 0.5rem;
            color: white;
            margin-bottom: 1rem;
        }

        .stats-card.primary { background: var(--admin-primary); }
        .stats-card.secondary { background: var(--admin-secondary); }
        .stats-card.accent { background: var(--admin-accent); }
        .stats-card.highlight { background: var(--admin-highlight); }

        /* Utilities */
        .icon-lg {
            font-size: 2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 70px;
            }
            .admin-sidebar .nav-link span {
                display: none;
            }
            .admin-main {
                margin-left: 70px;
            }
        }
    </style>
</head>
<body>

<!-- 5. Sidebar Navigation -->
<div class="admin-sidebar">
    <!-- Flow: Each nav link checks current page to highlight active section -->
    <div class="px-3 mb-4">
        <h4 class="text-center">Drunkies</h4>
        <p class="text-center mb-0"><small>Admin Panel</small></p>
    </div>
    <nav class="nav flex-column">
        <!-- Flow: Each link checks against current page and sets active class -->
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
            <i class="fas fa-tachometer-alt me-2"></i>
            <span>Dashboard</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
            <i class="fas fa-beer me-2"></i>
            <span>Products</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'brands.php' ? 'active' : ''; ?>" href="brands.php">
            <i class="fas fa-building me-2"></i>
            <span>Brands</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
            <i class="fas fa-tags me-2"></i>
            <span>Categories</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
            <i class="fas fa-shopping-cart me-2"></i>
            <span>Orders</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
            <i class="fas fa-users me-2"></i>
            <span>Users</span>
        </a>
        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
            <i class="fas fa-cog me-2"></i>
            <span>Settings</span>
        </a>
        <div class="dropdown-divider my-3"></div>
        <a class="nav-link" href="../index.php">
            <i class="fas fa-home me-2"></i>
            <span>View Site</span>
        </a>
        <a class="nav-link" href="../logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>

<!-- 6. Main Content Area -->
<!-- Flow: This section appears on every admin page and contains the page title and alerts -->
<div class="admin-main">
    <!-- 7. Header with page title and user info -->
    <div class="admin-header d-flex justify-content-between align-items-center">
        <!-- Flow: Dynamically shows current page name in title -->
        <h1 class="h3 mb-0">
            <?php
            $page = basename($_SERVER['PHP_SELF'], '.php');
            echo ucfirst($page == 'index' ? 'Dashboard' : $page);
            ?>
        </h1>
        <div class="d-flex align-items-center">
            <span class="me-3"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <!-- 8. Alert Messages Container -->
    <!-- Flow: Shows success/error messages from session variables -->
    <div class="container-fluid py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <!-- Flow: Success message is displayed and then cleared from session -->
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- 9. Include Bootstrap JS at the end -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 