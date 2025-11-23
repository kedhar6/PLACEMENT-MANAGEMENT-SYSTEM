<?php
require_once 'includes/config.php';

// Safety: ensure DB has companies table and users.role supports 'company'
if (function_exists('ensureCompanySchema')) {
    try {
        ensureCompanySchema();
    } catch (Exception $e) {
        error_log('[register.php] ensureCompanySchema error: ' . $e->getMessage());
    }
}

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

// Initialize variables
$username = $email = $password = $confirm_password = '';
$username_err = $email_err = $password_err = $confirm_password_err = $role_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate username
    if (empty(trim($_POST['username']))) {
        $username_err = 'Please enter a username.';
    } else {
        $username = trim($_POST['username']);
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $username_err = 'This username is already taken.';
                }
            } else {
                $error = 'Oops! Something went wrong. Please try again later.';
            }
            $stmt->close();
        }
    }

    // Validate email
    if (empty(trim($_POST['email']))) {
        $email_err = 'Please enter an email address.';
    } elseif (!filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL)) {
        $email_err = 'Please enter a valid email address.';
    } else {
        $email = trim($_POST['email']);
        // Check if email already exists
        $sql = "SELECT id FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $email;
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $email_err = 'This email is already registered.';
                }
            } else {
                $error = 'Oops! Something went wrong. Please try again later.';
            }
            $stmt->close();
        }
    }

    // Validate password
    if (empty(trim($_POST['password']))) {
        $password_err = 'Please enter a password.';
    } elseif (strlen(trim($_POST['password'])) < 8) {
        $password_err = 'Password must have at least 8 characters.';
    } else {
        $password = trim($_POST['password']);
    }

    // Validate confirm password
    if (empty(trim($_POST['confirm_password']))) {
        $confirm_password_err = 'Please confirm password.';
    } else {
        $confirm_password = trim($_POST['confirm_password']);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = 'Passwords did not match.';
        }
    }

    // Validate role
    if (empty(trim($_POST['role']))) {
        $role_err = 'Please select a role.';
    } else {
        $role = trim($_POST['role']);
        // Only allow student and teacher roles for self-registration
        if (!in_array($role, ['student', 'teacher', 'company'])) {
            $role_err = 'Invalid role selected.';
        }
    }

    // Validate college name for students and teachers
    $college_err = '';
    if (in_array($role, ['student', 'teacher'])) {
        if (empty(trim($_POST['college_name']))) {
            $college_err = 'Please enter your College Name.';
        }
    }

    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) && empty($college_err)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert into users table
            $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssss", $param_username, $param_email, $param_password, $param_role);
                
                $param_username = $username;
                $param_email = $email;
                $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                $param_role = $role;
                
                if ($stmt->execute()) {
                    $user_id = $conn->insert_id;
                    
                    // Insert into respective role table
                    if ($role === 'student') {
                        $sql = "INSERT INTO students (user_id, first_name, last_name, enrollment_no, department, semester, college_name) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("issssis", $user_id, $first_name, $last_name, $enrollment_no, $department, $semester, $college_name);
                            
                            // Set student parameters
                            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
                            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
                            $enrollment_no = isset($_POST['enrollment_no']) ? trim($_POST['enrollment_no']) : '';
                            $department = isset($_POST['department']) ? trim($_POST['department']) : '';
                            $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 1;
                            $college_name = isset($_POST['college_name']) ? trim($_POST['college_name']) : '';
                            
                            $stmt->execute();
                        }
                    } elseif ($role === 'teacher') {
                        $sql = "INSERT INTO teachers (user_id, first_name, last_name, department, designation, college_name) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("isssss", $user_id, $first_name, $last_name, $department, $designation, $college_name);
                            
                            // Set teacher parameters
                            $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
                            $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
                            $department = isset($_POST['department']) ? trim($_POST['department']) : '';
                            $designation = isset($_POST['designation']) ? trim($_POST['designation']) : 'Professor'; // Default designation
                            $college_name = isset($_POST['college_name']) ? trim($_POST['college_name']) : '';
                            
                            $stmt->execute();
                        }
                    } elseif ($role === 'company') {
                        // Insert company profile
                        $sql = "INSERT INTO companies (user_id, company_name, industry_type, logo_path) VALUES (?, ?, ?, ?)";
                        if ($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("isss", $user_id, $company_name, $industry_type, $logo_path);

                            $company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
                            $industry_type = isset($_POST['industry_type']) ? trim($_POST['industry_type']) : '';
                            $logo_path = null;

                            // Handle logo upload if provided
                            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                                $upload_dir = 'uploads/logos/';
                                if (!file_exists($upload_dir)) {
                                    mkdir($upload_dir, 0777, true);
                                }
                                $file_name = $_FILES['company_logo']['name'];
                                $file_tmp = $_FILES['company_logo']['tmp_name'];
                                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $allowed = ['jpg','jpeg','png','svg'];
                                if (in_array($file_ext, $allowed)) {
                                    $new_name = 'logo_' . $user_id . '_' . time() . '.' . $file_ext;
                                    $upload_path = $upload_dir . $new_name;
                                    if (move_uploaded_file($file_tmp, $upload_path)) {
                                        $logo_path = $upload_path;
                                    }
                                }
                            }

                            $stmt->execute();
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();

                    // For company accounts, auto-login and redirect to company dashboard
                    if ($role === 'company') {
                        // Set session and redirect to company dashboard
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['user_role'] = 'company';

                        header("Location: " . $base_url . "/modules/company/dashboard.php");
                        exit();
                    }

                    // Otherwise redirect to login page with success message
                    $_SESSION['success_message'] = 'Registration successful! Please login.';
                    header("location: login.php");
                    exit();
                } else {
                    throw new Exception("Error inserting user data.");
                }
            }
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Create an Account</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="registrationForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required 
                                       value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required
                                       value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" 
                                       id="username" name="username" required
                                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                <div class="invalid-feedback"><?php echo $username_err; ?></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" 
                                       id="email" name="email" required
                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                <div class="invalid-feedback"><?php echo $email_err; ?></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" 
                                           id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback"><?php echo $password_err; ?></div>
                                </div>
                                <div class="form-text">At least 8 characters</div>
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" 
                                           id="confirm_password" name="confirm_password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="role" class="form-label">I am a <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo (!empty($role_err)) ? 'is-invalid' : ''; ?>" 
                                    id="role" name="role" required>
                                <option value="" disabled selected>Select your role</option>
                                <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] === 'student') ? 'selected' : ''; ?>>Student</option>
                                <option value="teacher" <?php echo (isset($_POST['role']) && $_POST['role'] === 'teacher') ? 'selected' : ''; ?>>Teacher</option>
                                <option value="company" <?php echo (isset($_POST['role']) && $_POST['role'] === 'company') ? 'selected' : ''; ?>>Company</option>
                            </select>
                            <div class="invalid-feedback"><?php echo $role_err; ?></div>
                        </div>

                        <!-- Student Specific Fields -->
                        <div id="studentFields" class="mb-4" style="display: none;">
                            <h5 class="mb-3">Student Information</h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="college_name" class="form-label">College Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="college_name" name="college_name"
                                           value="<?php echo isset($_POST['college_name']) ? htmlspecialchars($_POST['college_name']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="enrollment_no" class="form-label">Enrollment Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="enrollment_no" name="enrollment_no"
                                           value="<?php echo isset($_POST['enrollment_no']) ? htmlspecialchars($_POST['enrollment_no']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="department" name="department">
                                        <option value="" disabled selected>Select Department</option>
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Information Technology">Information Technology</option>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Mechanical">Mechanical</option>
                                        <option value="Civil">Civil</option>
                                        <option value="Electrical">Electrical</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" id="semester" name="semester">
                                        <option value="1">1st Semester</option>
                                        <option value="2">2nd Semester</option>
                                        <option value="3">3rd Semester</option>
                                        <option value="4">4th Semester</option>
                                        <option value="5">5th Semester</option>
                                        <option value="6">6th Semester</option>
                                        <option value="7">7th Semester</option>
                                        <option value="8">8th Semester</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Teacher Specific Fields -->
                        <div id="teacherFields" class="mb-4" style="display: none;">
                            <h5 class="mb-3">Teacher Information</h5>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="college_name_teacher" class="form-label">College Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="college_name_teacher" name="college_name"
                                           value="<?php echo isset($_POST['college_name']) ? htmlspecialchars($_POST['college_name']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="teacher_department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="teacher_department" name="department">
                                        <option value="" disabled selected>Select Department</option>
                                        <option value="Computer Science">Computer Science</option>
                                        <option value="Information Technology">Information Technology</option>
                                        <option value="Electronics">Electronics</option>
                                        <option value="Mechanical">Mechanical</option>
                                        <option value="Civil">Civil</option>
                                        <option value="Electrical">Electrical</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input type="text" class="form-control" id="designation" name="designation"
                                           value="<?php echo isset($_POST['designation']) ? htmlspecialchars($_POST['designation']) : 'Professor'; ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Company Specific Fields -->
                        <div id="companyFields" class="mb-4" style="display: none;">
                            <h5 class="mb-3">Company Information</h5>
                            <div class="mb-3">
                                <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="company_name" name="company_name"
                                       value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="industry_type" class="form-label">Industry Type</label>
                                <input type="text" class="form-control" id="industry_type" name="industry_type"
                                       value="<?php echo isset($_POST['industry_type']) ? htmlspecialchars($_POST['industry_type']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label for="company_logo" class="form-label">Company Logo (optional)</label>
                                <input type="file" class="form-control" id="company_logo" name="company_logo" accept="image/*">
                                <div class="mt-2" id="companyLogoPreview"></div>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a>
                            </label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2"></i> Create Account
                            </button>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="mb-0">Already have an account? <a href="login.php" class="text-decoration-none">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Terms & Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms & Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>1. Acceptance of Terms</h6>
                <p>By registering for an account on the College Placement Management System (CPMS), you agree to be bound by these Terms and Conditions.</p>
                
                <h6>2. User Responsibilities</h6>
                <p>You are responsible for maintaining the confidentiality of your account and password. All activities that occur under your account are your responsibility.</p>
                
                <h6>3. Privacy Policy</h6>
                <p>Your personal information will be used in accordance with our Privacy Policy. By using this system, you consent to such processing.</p>
                
                <h6>4. Code of Conduct</h6>
                <p>You agree not to use the service for any unlawful purpose or in any way that might harm, damage, or disparage any other party.</p>
                
                <h6>5. Changes to Terms</h6>
                <p>We reserve the right to modify these terms at any time. Your continued use of the service constitutes acceptance of those changes.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
const togglePassword = document.querySelectorAll('.toggle-password');
togglePassword.forEach(button => {
    button.addEventListener('click', function() {
        const input = this.previousElementSibling;
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
});

// Show/hide role-specific fields
const roleSelect = document.getElementById('role');
const studentFields = document.getElementById('studentFields');
const teacherFields = document.getElementById('teacherFields');
const companyFields = document.getElementById('companyFields');

function toggleRoleFields() {
    const selectedRole = roleSelect.value;
    
    if (selectedRole === 'student') {
        studentFields.style.display = 'block';
        teacherFields.style.display = 'none';
        companyFields.style.display = 'none';
        // Set required attributes for student fields
        document.getElementById('enrollment_no').setAttribute('required', 'required');
        document.getElementById('department').setAttribute('required', 'required');
        if (document.getElementById('college_name')) document.getElementById('college_name').setAttribute('required', 'required');
        if (document.getElementById('teacher_department')) document.getElementById('teacher_department').removeAttribute('required');
    } else if (selectedRole === 'teacher') {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'block';
        companyFields.style.display = 'none';
        // Set required attributes for teacher fields
        document.getElementById('teacher_department').setAttribute('required', 'required');
        document.getElementById('enrollment_no').removeAttribute('required');
        document.getElementById('department').removeAttribute('required');
        if (document.getElementById('college_name_teacher')) document.getElementById('college_name_teacher').setAttribute('required', 'required');
    } else if (selectedRole === 'company') {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'none';
        companyFields.style.display = 'block';
        // remove student/teacher required attributes
        document.getElementById('enrollment_no').removeAttribute('required');
        document.getElementById('department').removeAttribute('required');
        if (document.getElementById('college_name')) document.getElementById('college_name').removeAttribute('required');
        if (document.getElementById('college_name_teacher')) document.getElementById('college_name_teacher').removeAttribute('required');
    } else {
        studentFields.style.display = 'none';
        teacherFields.style.display = 'none';
        companyFields.style.display = 'none';
        // Remove required attributes
        if (document.getElementById('enrollment_no')) document.getElementById('enrollment_no').removeAttribute('required');
        if (document.getElementById('department')) document.getElementById('department').removeAttribute('required');
        if (document.getElementById('teacher_department')) document.getElementById('teacher_department').removeAttribute('required');
        if (document.getElementById('college_name')) document.getElementById('college_name').removeAttribute('required');
        if (document.getElementById('college_name_teacher')) document.getElementById('college_name_teacher').removeAttribute('required');
    }
}

// Initial call to set the correct fields based on any pre-selected role
toggleRoleFields();

// Add event listener for role change
roleSelect.addEventListener('change', toggleRoleFields);

// Form validation
const form = document.getElementById('registrationForm');
form.addEventListener('submit', function(event) {
    // Client-side validation can be added here
    // For example, check if passwords match
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        event.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    // Check if terms are accepted
    if (!document.getElementById('terms').checked) {
        event.preventDefault();
        alert('You must accept the Terms & Conditions to register.');
        return false;
    }
    
    return true;
});

// Company logo preview
const companyLogoInput = document.getElementById('company_logo');
if (companyLogoInput) {
    companyLogoInput.addEventListener('change', function() {
        const preview = document.getElementById('companyLogoPreview');
        preview.innerHTML = '';
        const file = this.files[0];
        if (file) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.maxWidth = '120px';
            img.style.maxHeight = '80px';
            img.className = 'img-thumbnail';
            preview.appendChild(img);
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>
