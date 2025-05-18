<?php
// Flow: User Login Page
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        flashMessage("Please fill in all fields", "danger");
    } else {
        // Check credentials
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            
            // Redirect based on role
            redirect($user['is_admin'] ? 'admin/index.php' : 'index.php');
        } else {
            flashMessage("Invalid username or password", "danger");
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card bg-dark text-light shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-warning mb-2">Welcome Back!</h2>
                        <p class="text-light-emphasis mb-0">Enter your credentials to access your account</p>
                    </div>

                    <?php if ($flash = getFlashMessage()): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?> text-center">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label text-light-emphasis">Username</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-light">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input type="text" name="username" class="form-control bg-dark text-light border-secondary" 
                                       id="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                       required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label text-light-emphasis">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-light">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" name="password" class="form-control bg-dark text-light border-secondary" 
                                       id="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <button type="submit" class="btn btn-warning w-100 fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i>Log In
                            </button>
                        </div>
                    </form>

                    <div class="text-center">
                        <p class="text-light-emphasis mb-0">
                            Don't have an account? 
                            <a href="register.php" class="text-warning text-decoration-none fw-bold">Sign up</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Add hover effect to input groups
document.querySelectorAll('.input-group').forEach(group => {
    group.addEventListener('mouseenter', () => {
        group.querySelectorAll('.form-control, .input-group-text, .btn-outline-secondary').forEach(el => {
            el.style.borderColor = '#ffc107';
            el.style.transition = 'border-color 0.3s ease';
        });
    });
    
    group.addEventListener('mouseleave', () => {
        group.querySelectorAll('.form-control, .input-group-text, .btn-outline-secondary').forEach(el => {
            el.style.borderColor = '';
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 