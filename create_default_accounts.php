<?php
require_once 'includes/config.php';

// Function to create a new user
function createUser($conn, $username, $email, $password, $role) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if ($stmt->execute()) {
            return true;
        } else {
            return false;
        }
        
        $stmt->close();
    }
    
    return false;
}

// Default accounts data
$accounts = [
    [
        'username' => 'admin',
        'email' => 'admin@cipms.com',
        'password' => 'Admin@123',
        'role' => 'admin'
    ],
    [
        'username' => 'teacher1',
        'email' => 'teacher1@cipms.com',
        'password' => 'Teacher@123',
        'role' => 'teacher'
    ],
    [
        'username' => 'student1',
        'email' => 'student1@cipms.com',
        'password' => 'Student@123',
        'role' => 'student'
    ]
];

// Create accounts
echo "Creating default accounts...\n";
echo str_repeat("=", 40) . "\n";

foreach ($accounts as $account) {
    // Check if user already exists
    $check_sql = "SELECT id FROM users WHERE email = ? OR username = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $account['email'], $account['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "Account already exists - Username: {$account['username']}, Email: {$account['email']}, Role: {$account['role']}\n";
    } else {
        if (createUser($conn, $account['username'], $account['email'], $account['password'], $account['role'])) {
            echo "Created - Username: {$account['username']}, Email: {$account['email']}, Role: {$account['role']}, Password: {$account['password']}\n";
        } else {
            echo "Failed to create - Username: {$account['username']}\n";
        }
    }
    
    $stmt->close();
}

echo "\nAccount creation process completed.\n";
?>

<!-- HTML for easy access -->
<!DOCTYPE html>
<html>
<head>
    <title>Default Accounts</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .account { background: #f4f4f4; margin: 10px 0; padding: 15px; border-radius: 5px; }
        .admin { border-left: 5px solid #dc3545; }
        .teacher { border-left: 5px solid #28a745; }
        .student { border-left: 5px solid #007bff; }
        .credentials { background: #fff; padding: 10px; margin: 5px 0; border-radius: 3px; }
    </style>
</head>
<body>
    <h2>Default Accounts</h2>
    <p>These accounts have been created in the database. Please change the passwords after first login.</p>
    
    <div class="account admin">
        <h3>System Account</h3>
        <div class="credentials">
            <p><strong>Email:</strong> admin@cipms.com</p>
            <p><strong>Password:</strong> Admin@123</p>
            <p><a href="login.php?role=admin" class="btn">Login as Admin</a></p>
        </div>
    </div>
    
    <div class="account teacher">
        <h3>Teacher Account</h3>
        <div class="credentials">
            <p><strong>Email:</strong> teacher1@cipms.com</p>
            <p><strong>Password:</strong> Teacher@123</p>
            <p><a href="login.php?role=teacher" class="btn">Login as Teacher</a></p>
        </div>
    </div>
    
    <div class="account student">
        <h3>Student Account</h3>
        <div class="credentials">
            <p><strong>Email:</strong> student1@cipms.com</p>
            <p><strong>Password:</strong> Student@123</p>
            <p><a href="login.php?role=student" class="btn">Login as Student</a></p>
        </div>
    </div>
    
    <p><strong>Note:</strong> For security reasons, please change these default passwords after your first login.</p>
</body>
</html>
