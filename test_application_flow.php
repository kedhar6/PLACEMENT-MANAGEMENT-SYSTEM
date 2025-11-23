<?php
require_once 'includes/config.php';

// Create a test user (if not exists)
$testEmail = 'test_student@example.com';
$testPassword = password_hash('test123', PASSWORD_DEFAULT);
$testUsername = 'test_student';

// Check if test user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $testEmail);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create test user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->bind_param("sss", $testUsername, $testEmail, $testPassword);
    if ($stmt->execute()) {
        $studentId = $conn->insert_id;
        echo "Created test user with ID: $studentId\n";
        
        // Create student profile
        $stmt = $conn->prepare("INSERT INTO students (user_id, first_name, last_name, enrollment_no, department, semester) 
                               VALUES (?, 'Test', 'Student', 'ENR$studentId', 'Computer Science', 6)");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        
        echo "Created student profile\n";
    } else {
        die("Failed to create test user: " . $conn->error . "\n");
    }
} else {
    $user = $result->fetch_assoc();
    $studentId = $user['id'];
    echo "Using existing test user with ID: $studentId\n";
}

// Create a test opportunity (if not exists)
$testTitle = "Test Internship Position";
$testCompany = "Test Company Inc.";

$stmt = $conn->prepare("SELECT id FROM opportunities WHERE title = ? AND company_name = ?");
$stmt->bind_param("ss", $testTitle, $testCompany);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create test opportunity
    $stmt = $conn->prepare("INSERT INTO opportunities 
                           (title, company_name, description, requirements, location, type, stipend_salary, application_deadline, posted_by, status) 
                           VALUES (?, ?, 'Test internship description', 'Test requirements', 'Remote', 'internship', '10000', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'approved')");
    $stmt->bind_param("ss", $testTitle, $testCompany);
    
    if ($stmt->execute()) {
        $opportunityId = $conn->insert_id;
        echo "Created test opportunity with ID: $opportunityId\n";
    } else {
        die("Failed to create test opportunity: " . $conn->error . "\n");
    }
} else {
    $opportunity = $result->fetch_assoc();
    $opportunityId = $opportunity['id'];
    echo "Using existing test opportunity with ID: $opportunityId\n";
}

// Test 1: Submit a new application
echo "\n=== Test 1: Submitting a new application ===\n";

// Set the current user ID for the trigger
$conn->query("SET @current_user_id = $studentId");

$coverLetter = "This is a test application for the internship position.";
$stmt = $conn->prepare("INSERT INTO applications (opportunity_id, student_id, cover_letter, status) VALUES (?, ?, ?, 'applied')");
$stmt->bind_param("iis", $opportunityId, $studentId, $coverLetter);

if ($stmt->execute()) {
    $applicationId = $conn->insert_id;
    echo "✓ Application submitted successfully. Application ID: $applicationId\n";
    
    // Test 2: Check if status history was created
    echo "\n=== Test 2: Checking status history ===\n";
    $stmt = $conn->prepare("SELECT * FROM application_status_history WHERE application_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $applicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "✓ Status history found. Total entries: " . $result->num_rows . "\n";
        while ($row = $result->fetch_assoc()) {
            echo "- Status: " . $row['status'] . ", Notes: " . $row['notes'] . ", Changed by: " . ($row['changed_by'] ?? 'system') . "\n";
        }
    } else {
        echo "✗ No status history found for the application\n";
    }
    
    // Test 3: Update application status
    echo "\n=== Test 3: Updating application status ===\n";
    $newStatus = 'shortlisted';
    $notes = 'Candidate has been shortlisted for the next round.';
    
    // Update status using our function
    require_once 'includes/functions/application_functions.php';
    
    if (updateApplicationStatus($conn, $applicationId, $newStatus, 1, $notes)) { // Using admin ID 1
        echo "✓ Application status updated to '$newStatus'\n";
        
        // Check updated status history
        $stmt = $conn->prepare("SELECT * FROM application_status_history WHERE application_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "\nUpdated status history:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- Status: " . $row['status'] . ", Notes: " . $row['notes'] . ", Changed by: " . ($row['changed_by'] ?? 'system') . "\n";
        }
    } else {
        echo "✗ Failed to update application status\n";
    }
    
    // Test 4: Student withdraws application
    echo "\n=== Test 4: Student withdraws application ===\n";
    $withdrawStatus = 'withdrawn';
    $withdrawNotes = 'I would like to withdraw my application.';
    
    // Set the current user ID for the trigger
    $conn->query("SET @current_user_id = $studentId");
    
    if (updateApplicationStatus($conn, $applicationId, $withdrawStatus, $studentId, $withdrawNotes)) {
        echo "✓ Application withdrawn successfully\n";
        
        // Check final status
        $stmt = $conn->prepare("SELECT status FROM applications WHERE id = ?");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $app = $result->fetch_assoc();
        
        echo "Final application status: " . $app['status'] . "\n";
        
        // Display full history
        echo "\n=== Final Status History ===\n";
        $stmt = $conn->prepare("SELECT h.*, u.username as changed_by_name FROM application_status_history h 
                               LEFT JOIN users u ON h.changed_by = u.id 
                               WHERE h.application_id = ? 
                               ORDER BY h.created_at ASC");
        $stmt->bind_param("i", $applicationId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $changedBy = $row['changed_by_name'] ?: ($row['changed_by'] ? 'User ' . $row['changed_by'] : 'system');
            echo "[{$row['created_at']}] {$row['status']} - {$row['notes']} (By: $changedBy)\n";
        }
    } else {
        echo "✗ Failed to withdraw application\n";
    }
    
} else {
    echo "✗ Failed to submit application: " . $conn->error . "\n";
}

// Clean up (comment out if you want to keep the test data)
echo "\n=== Cleaning up test data ===\n";
$tables = ['applications', 'application_status_history'];
foreach ($tables as $table) {
    $conn->query("DELETE FROM $table WHERE id > 0");
    echo "Cleaned up $table\n";
}

echo "\n=== Test completed ===\n";
?>
