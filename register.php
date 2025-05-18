<?php
require_once 'config/database.php';
require_once 'includes/header.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['is_admin'] ? 'admin/index.php' : 'index.php'));
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postal_code = $_POST['postal_code'] ?? '';
    $country = $_POST['country'] ?? '';

    // Validate required fields
    if (empty($username) || empty($first_name) || empty($last_name) || empty($email) || 
        empty($password) || empty($birthdate) || empty($street) || empty($city) || 
        empty($state) || empty($postal_code) || empty($country)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email already registered';
        } else {
            try {
                $pdo->beginTransaction();

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, first_name, last_name, email, password, birthdate) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $username,
                    $first_name,
                    $last_name,
                    $email,
                    password_hash($password, PASSWORD_DEFAULT),
                    $birthdate
                ]);
                
                $user_id = $pdo->lastInsertId();

                // Insert address
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, street, city, state, postal_code, country) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user_id,
                    $street,
                    $city,
                    $state,
                    $postal_code,
                    $country
                ]);

                // Assign customer role
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) 
                                     SELECT ?, role_id FROM roles WHERE role_name = 'customer'");
                $stmt->execute([$user_id]);

                $pdo->commit();
                $success = 'Registration successful! You can now login.';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body p-5">
                    <h2 class="text-center mb-4">Create an Account</h2>
                    <form id="registerForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="invalid-feedback" id="usernameFeedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback" id="emailFeedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-requirements mt-2">
                                    <div class="requirement" data-requirement="length">
                                        <i class="fas fa-circle"></i> At least 8 characters
                                    </div>
                                    <div class="requirement" data-requirement="uppercase">
                                        <i class="fas fa-circle"></i> One uppercase letter
                                    </div>
                                    <div class="requirement" data-requirement="lowercase">
                                        <i class="fas fa-circle"></i> One lowercase letter
                                    </div>
                                    <div class="requirement" data-requirement="number">
                                        <i class="fas fa-circle"></i> One number
                                    </div>
                                    <div class="requirement" data-requirement="special">
                                        <i class="fas fa-circle"></i> One special character
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="confirmPasswordFeedback"></div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="terms.php">Terms and Conditions</a>
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    togglePasswordVisibility(password, icon);
});

document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
    const password = document.getElementById('confirm_password');
    const icon = this.querySelector('i');
    togglePasswordVisibility(password, icon);
});

function togglePasswordVisibility(input, icon) {
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

// Password validation
const password = document.getElementById('password');
const requirements = {
    length: str => str.length >= 8,
    uppercase: str => /[A-Z]/.test(str),
    lowercase: str => /[a-z]/.test(str),
    number: str => /[0-9]/.test(str),
    special: str => /[^A-Za-z0-9]/.test(str)
};

password.addEventListener('input', function() {
    const value = this.value;
    let valid = true;
    
    Object.keys(requirements).forEach(req => {
        const element = document.querySelector(`[data-requirement="${req}"]`);
        const icon = element.querySelector('i');
        
        if (requirements[req](value)) {
            element.classList.remove('invalid');
            element.classList.add('valid');
            icon.className = 'fas fa-check text-success';
        } else {
            element.classList.remove('valid');
            element.classList.add('invalid');
            icon.className = 'fas fa-circle';
            valid = false;
        }
    });
    
    this.setCustomValidity(valid ? '' : 'Please meet all password requirements');
});

// Form validation
function validateForm(form) {
    let valid = true;
    const password = form.querySelector('#password');
    const confirmPassword = form.querySelector('#confirm_password');
    
    // Check password requirements
    if (!Object.keys(requirements).every(req => requirements[req](password.value))) {
        valid = false;
    }
    
    // Check password match
    if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('Passwords do not match');
        valid = false;
    } else {
        confirmPassword.setCustomValidity('');
    }
    
    return valid && form.checkValidity();
}
</script>

<?php require_once 'includes/footer.php'; ?> 