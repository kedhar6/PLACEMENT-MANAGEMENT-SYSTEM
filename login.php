<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'] ?? 'student';
    $redirect = 'modules/student/dashboard.php';
    if ($role === 'admin') {
        $redirect = 'modules/admin/dashboard.php';
    } elseif ($role === 'teacher') {
        $redirect = 'modules/teacher/dashboard.php';
    }
    header("Location: " . $base_url . "/" . $redirect);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $selectedRole = $_POST['role'] ?? ''; // Get the selected role
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Allow login by email OR username to tolerate different default emails/usernames
        $sql = "SELECT id, username, password, role FROM users WHERE email = ? OR username = ? LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            // use the same input for email or username lookup
            $identifier = $email;
            $stmt->bind_param("ss", $identifier, $identifier);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $dbRole);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username;
                            $_SESSION['user_role'] = $dbRole;
                            
                            // Verify if the user's role matches the selected role (if any)
                            if (!empty($selectedRole) && $selectedRole !== $_SESSION['user_role']) {
                                // Role selected by user does not match stored role
                                session_destroy();
                                $error = 'Please log in with the correct account type.';
                                include 'includes/header.php';
                                exit();
                            }
                            
                            // Redirect based on user role
                            $redirect = 'index.php';
                            if ($_SESSION['user_role'] === 'admin') {
                                $redirect = 'modules/admin/dashboard.php';
                            } elseif ($_SESSION['user_role'] === 'teacher') {
                                $redirect = 'modules/teacher/dashboard.php';
                            } elseif ($_SESSION['user_role'] === 'student') {
                                $redirect = 'modules/student/dashboard.php';
                            } elseif ($_SESSION['user_role'] === 'company') {
                                $redirect = 'modules/company/dashboard.php';
                            }
                            
                            // Redirect to appropriate dashboard
                            header("Location: " . $base_url . "/" . $redirect);
                            exit();
                        } else {
                            $error = 'Invalid email/username or password.';
                        }
                    }
                } else {
                    $error = 'No account found with that email or username.';
                }
            } else {
                $error = 'Oops! Something went wrong. Please try again later.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg my-5">
                <div class="card-body p-4">
                                    <div class="text-center mb-4">
                                        <h2 class="fw-bold text-primary">Login to CPMS</h2>
                                        <p class="text-muted">Enter your credentials to access your account</p>
                                    </div>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php 
                    // Get role from URL parameter
                    $selectedRole = isset($_GET['role']) ? $_GET['role'] : '';
                    $roleOptions = [
                        'student' => 'Student',
                        'teacher' => 'Teacher',
                        'company' => 'Company'
                    ];
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <?php if (empty($selectedRole)): ?>
                        <div class="mb-3">
                            <label for="role" class="form-label">Login As</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="" disabled selected>Select your role</option>
                                <?php foreach ($roleOptions as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo ($selectedRole === $value) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($selectedRole); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" required 
                                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between">
                                <label for="password" class="form-label">Password</label>
                                <a href="forgot-password.php" class="text-decoration-none small">Forgot password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Login
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
const togglePassword = document.querySelector('.toggle-password');
const password = document.querySelector('#password');

togglePassword.addEventListener('click', function() {
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});
</script>

<?php include 'includes/footer.php'; ?>
