<?php
require_once 'includes/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update profile based on role
        if ($user_role === 'student') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $contact_no = trim($_POST['contact_no'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $skills = trim($_POST['skills'] ?? '');
            
            // Handle resume upload
            $resume_path = $profile['resume_path'] ?? null;
            if (isset($_FILES['resume_upload']) && $_FILES['resume_upload']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'uploads/resumes/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_name = $_FILES['resume_upload']['name'];
                $file_tmp = $_FILES['resume_upload']['tmp_name'];
                $file_size = $_FILES['resume_upload']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if ($file_ext === 'pdf' && $file_size <= 5242880) { // 5MB max
                    $new_file_name = 'resume_' . $user_id . '_' . time() . '.pdf';
                    $upload_path = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        // Delete old resume if exists
                        if ($resume_path && file_exists($resume_path)) {
                            unlink($resume_path);
                        }
                        $resume_path = $upload_path;
                    } else {
                        $error = 'Failed to upload resume. Please try again.';
                    }
                } else {
                    $error = 'Invalid file. Please upload a PDF file under 5MB.';
                }
            }
            
            $sql = "UPDATE students SET first_name = ?, last_name = ?, contact_no = ?, address = ?, skills = ?";
            $params = [$first_name, $last_name, $contact_no, $address, $skills];
            $types = "sssss";
            
            if ($resume_path !== null) {
                $sql .= ", resume_path = ?";
                $params[] = $resume_path;
                $types .= "s";
            }
            
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        } elseif ($user_role === 'teacher') {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $contact_no = trim($_POST['contact_no'] ?? '');
            $designation = trim($_POST['designation'] ?? '');
            
            $sql = "UPDATE teachers SET first_name = ?, last_name = ?, contact_no = ?, designation = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $first_name, $last_name, $contact_no, $designation, $user_id);
            
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user && password_verify($current_password, $user['password'])) {
                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                $error = 'Current password is incorrect.';
            }
            $stmt->close();
        }
    }
}

// Get user profile data
$profile = [];
if ($user_role === 'student') {
    $sql = "SELECT s.*, u.email, u.username FROM students s JOIN users u ON s.user_id = u.id WHERE s.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
} elseif ($user_role === 'teacher') {
    $sql = "SELECT t.*, u.email, u.username FROM teachers t JOIN users u ON t.user_id = u.id WHERE t.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
} else {
    // Admin - just get user info
    $sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $profile = $result->fetch_assoc();
}

$pageTitle = "My Profile";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i>My Profile</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Information -->
                    <div class="mb-4">
                        <h5 class="mb-3">Profile Information</h5>
                        <form method="post" action="" enctype="multipart/form-data">
                            <input type="hidden" name="update_profile" value="1">
                            
                            <?php if ($user_role === 'student'): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="enrollment_no" class="form-label">Enrollment Number</label>
                                    <input type="text" class="form-control" id="enrollment_no" value="<?php echo htmlspecialchars($profile['enrollment_no'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester</label>
                                    <input type="text" class="form-control" id="semester" value="<?php echo htmlspecialchars($profile['semester'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_no" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_no" name="contact_no" 
                                           value="<?php echo htmlspecialchars($profile['contact_no'] ?? ''); ?>" 
                                           placeholder="Enter your contact number">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($profile['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="mb-3" id="resume">
                                    <label for="resume_upload" class="form-label">Resume (PDF)</label>
                                    <?php if (!empty($profile['resume_path']) && file_exists($profile['resume_path'])): ?>
                                        <div class="mb-2">
                                            <a href="<?php echo $base_url . '/' . htmlspecialchars($profile['resume_path']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-pdf me-1"></i>View Current Resume
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="resume_upload" name="resume_upload" accept=".pdf">
                                    <small class="form-text text-muted">Upload PDF file (Max 5MB)</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="skills" class="form-label">Skills (comma-separated)</label>
                                    <input type="text" class="form-control" id="skills" name="skills" 
                                           value="<?php echo htmlspecialchars($profile['skills'] ?? ''); ?>" 
                                           placeholder="e.g., PHP, MySQL, JavaScript, HTML, CSS">
                                    <small class="form-text text-muted">Separate multiple skills with commas</small>
                                </div>
                                
                            <?php elseif ($user_role === 'teacher'): ?>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <input type="text" class="form-control" id="department" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input type="text" class="form-control" id="designation" name="designation" 
                                           value="<?php echo htmlspecialchars($profile['designation'] ?? ''); ?>" 
                                           placeholder="e.g., Professor, Assistant Professor">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="contact_no" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_no" name="contact_no" 
                                           value="<?php echo htmlspecialchars($profile['contact_no'] ?? ''); ?>" 
                                           placeholder="Enter your contact number">
                                </div>
                                
                            <?php else: ?>
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>" disabled>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" disabled>
                                </div>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <!-- Change Password -->
                    <div class="mb-4">
                        <h5 class="mb-3">Change Password</h5>
                        <form method="post" action="">
                            <input type="hidden" name="change_password" value="1">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <small class="form-text text-muted">Password must be at least 8 characters long</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

