<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $country = $_POST['country'] ?? '';

    // Validate required fields
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || 
        empty($password) || empty($confirm_password) || empty($birthdate)) {
        flashMessage("Please fill in all required fields", "danger");
    } elseif ($password !== $confirm_password) {
        flashMessage("Passwords do not match", "danger");
    } else {
        try {
            $conn->begin_transaction();

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email already registered");
            }

            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Username already taken");
            }

            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO users (username, first_name, last_name, email, password, birthdate) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssss", $username, $first_name, $last_name, $email, $hashed_password, $birthdate);
            $stmt->execute();
            $user_id = $conn->insert_id;

            // Insert address if provided
            if (!empty($street) && !empty($city) && !empty($state) && !empty($postal_code) && !empty($country)) {
                $stmt = $conn->prepare("
                    INSERT INTO addresses (user_id, street, city, state, postal_code, country) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("isssss", $user_id, $street, $city, $state, $postal_code, $country);
                $stmt->execute();
            }

            // Assign customer role
            $stmt = $conn->prepare("
                INSERT INTO user_roles (user_id, role_id) 
                SELECT ?, id FROM roles WHERE name = 'customer'
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            $conn->commit();
            flashMessage("Registration successful! You can now login.");
            redirect('login.php');

        } catch (Exception $e) {
            $conn->rollback();
            flashMessage($e->getMessage(), "danger");
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-dark text-light shadow-lg border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h2 class="fw-bold text-warning mb-2">Create an Account</h2>
                        <p class="text-light-emphasis">Join Drunkies and start shopping for your favorite drinks</p>
                    </div>

                    <?php if ($flash = getFlashMessage()): ?>
                        <div class="alert alert-<?php echo $flash['type']; ?> text-center">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="registerForm">
                        <!-- Account Information -->
                        <h5 class="text-warning mb-3 d-flex align-items-center">
                            <i class="fas fa-user-circle me-2"></i>Account Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label text-light-emphasis">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control bg-dark text-light border-secondary" 
                                           id="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
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
                                <div class="password-requirements mt-2">
                                    <small class="text-light-emphasis">Password must contain:</small>
                                    <div class="requirement" data-requirement="length">
                                        <i class="fas fa-circle text-secondary"></i> At least 8 characters
                                    </div>
                                    <div class="requirement" data-requirement="uppercase">
                                        <i class="fas fa-circle text-secondary"></i> One uppercase letter
                                    </div>
                                    <div class="requirement" data-requirement="lowercase">
                                        <i class="fas fa-circle text-secondary"></i> One lowercase letter
                                    </div>
                                    <div class="requirement" data-requirement="number">
                                        <i class="fas fa-circle text-secondary"></i> One number
                                    </div>
                                    <div class="requirement" data-requirement="special">
                                        <i class="fas fa-circle text-secondary"></i> One special character
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label text-light-emphasis">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" name="confirm_password" class="form-control bg-dark text-light border-secondary" 
                                           id="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Personal Information -->
                        <h5 class="text-warning mb-3 mt-4 d-flex align-items-center">
                            <i class="fas fa-id-card me-2"></i>Personal Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label text-light-emphasis">First Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="first_name" class="form-control bg-dark text-light border-secondary" 
                                           id="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label text-light-emphasis">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" name="last_name" class="form-control bg-dark text-light border-secondary" 
                                           id="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" 
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="birthdate" class="form-label text-light-emphasis">Birthdate</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-light">
                                    <i class="fas fa-calendar"></i>
                                </span>
                                <input type="date" name="birthdate" class="form-control bg-dark text-light border-secondary" 
                                       id="birthdate" value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>" 
                                       required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                            </div>
                            <small class="text-light-emphasis">You must be at least 18 years old to register</small>
                        </div>

                        <!-- Address Information -->
                        <h5 class="text-warning mb-3 mt-4 d-flex align-items-center">
                            <i class="fas fa-shipping-fast me-2"></i>Shipping Address (Optional)
                        </h5>
                        <div class="mb-3">
                            <label for="street" class="form-label text-light-emphasis">Street Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary text-light">
                                    <i class="fas fa-home"></i>
                                </span>
                                <input type="text" name="street" class="form-control bg-dark text-light border-secondary" 
                                       id="street" value="<?php echo isset($_POST['street']) ? htmlspecialchars($_POST['street']) : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label text-light-emphasis">City</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-city"></i>
                                    </span>
                                    <input type="text" name="city" class="form-control bg-dark text-light border-secondary" 
                                           id="city" value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="state" class="form-label text-light-emphasis">State/Province</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-map"></i>
                                    </span>
                                    <input type="text" name="state" class="form-control bg-dark text-light border-secondary" 
                                           id="state" value="<?php echo isset($_POST['state']) ? htmlspecialchars($_POST['state']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="postal_code" class="form-label text-light-emphasis">Postal Code</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-mail-bulk"></i>
                                    </span>
                                    <input type="text" name="postal_code" class="form-control bg-dark text-light border-secondary" 
                                           id="postal_code" value="<?php echo isset($_POST['postal_code']) ? htmlspecialchars($_POST['postal_code']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="country" class="form-label text-light-emphasis">Country</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-dark border-secondary text-light">
                                        <i class="fas fa-globe"></i>
                                    </span>
                                    <input type="text" name="country" class="form-control bg-dark text-light border-secondary" 
                                           id="country" value="<?php echo isset($_POST['country']) ? htmlspecialchars($_POST['country']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label text-light-emphasis" for="terms">
                                I agree to the <a href="terms.php" class="text-warning text-decoration-none fw-bold">Terms and Conditions</a>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-light-emphasis mb-0">
                            Already have an account? 
                            <a href="login.php" class="text-warning text-decoration-none fw-bold">Log in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle functions
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

document.getElementById('togglePassword').addEventListener('click', () => togglePasswordVisibility('password', 'togglePassword'));
document.getElementById('toggleConfirmPassword').addEventListener('click', () => togglePasswordVisibility('confirm_password', 'toggleConfirmPassword'));

// Password validation
const password = document.getElementById('password');
const requirements = {
    length: str => str.length >= 8,
    uppercase: str => /[A-Z]/.test(str),
    lowercase: str => /[a-z]/.test(str),
    number: str => /[0-9]/.test(str),
    special: str => /[!@#$%^&*(),.?":{}|<>]/.test(str)
};

password.addEventListener('input', function() {
    const value = this.value;
    Object.keys(requirements).forEach(req => {
        const element = document.querySelector(`[data-requirement="${req}"] i`);
        if (requirements[req](value)) {
            element.classList.remove('text-secondary');
            element.classList.add('text-warning');
        } else {
            element.classList.remove('text-warning');
            element.classList.add('text-secondary');
        }
    });
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

// Birthdate validation
const birthdate = document.getElementById('birthdate');
birthdate.addEventListener('change', function() {
    const selectedDate = new Date(this.value);
    const today = new Date();
    const age = today.getFullYear() - selectedDate.getFullYear();
    const monthDiff = today.getMonth() - selectedDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < selectedDate.getDate())) {
        age--;
    }
    
    if (age < 18) {
        this.setCustomValidity('You must be at least 18 years old to register');
    } else {
        this.setCustomValidity('');
    }
});
</script>

<?php require_once 'includes/footer.php'; ?> 