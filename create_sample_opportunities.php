<?php
require_once 'includes/config.php';

// Sample opportunities data
$sample_opportunities = [
    [
        'title' => 'Web Development Intern',
        'company_name' => 'Tech Solutions Inc.',
        'description' => 'Join our dynamic team to develop modern web applications using cutting-edge technologies. You will work on real projects and gain hands-on experience in full-stack development.',
        'requirements' => 'Basic knowledge of HTML, CSS, JavaScript. Familiarity with PHP or Node.js is a plus. Strong problem-solving skills and willingness to learn.',
        'location' => 'Bangalore',
        'type' => 'internship',
        'stipend_salary' => '₹15,000/month',
        'application_deadline' => date('Y-m-d', strtotime('+30 days')),
        'status' => 'approved'
    ],
    [
        'title' => 'Data Science Intern',
        'company_name' => 'Analytics Pro',
        'description' => 'Work with large datasets to extract meaningful insights. Learn machine learning algorithms and data visualization techniques from industry experts.',
        'requirements' => 'Strong foundation in statistics and mathematics. Knowledge of Python and data analysis libraries. Experience with SQL is required.',
        'location' => 'Mumbai',
        'type' => 'internship',
        'stipend_salary' => '₹20,000/month',
        'application_deadline' => date('Y-m-d', strtotime('+45 days')),
        'status' => 'approved'
    ],
    [
        'title' => 'Mobile App Developer',
        'company_name' => 'MobileFirst Technologies',
        'description' => 'Design and develop mobile applications for iOS and Android platforms. Work in an agile environment and learn best practices in mobile development.',
        'requirements' => 'Experience with React Native or Flutter. Understanding of mobile UI/UX principles. Knowledge of REST APIs and mobile app architecture.',
        'location' => 'Pune',
        'type' => 'internship',
        'stipend_salary' => '₹18,000/month',
        'application_deadline' => date('Y-m-d', strtotime('+25 days')),
        'status' => 'approved'
    ],
    [
        'title' => 'Digital Marketing Intern',
        'company_name' => 'Growth Marketing Agency',
        'description' => 'Help create and execute digital marketing campaigns. Learn SEO, social media marketing, and content strategy from experienced marketers.',
        'requirements' => 'Creative mindset with good communication skills. Basic understanding of social media platforms. Knowledge of SEO and Google Analytics is a plus.',
        'location' => 'Delhi',
        'type' => 'internship',
        'stipend_salary' => '₹12,000/month',
        'application_deadline' => date('Y-m-d', strtotime('+20 days')),
        'status' => 'approved'
    ],
    [
        'title' => 'Software Engineer Trainee',
        'company_name' => 'Innovation Labs',
        'description' => 'Join our engineering team to work on innovative software solutions. Participate in the complete software development lifecycle.',
        'requirements' => 'Strong programming skills in Java or Python. Understanding of data structures and algorithms. Experience with version control systems.',
        'location' => 'Hyderabad',
        'type' => 'job',
        'stipend_salary' => '₹25,000/month',
        'application_deadline' => date('Y-m-d', strtotime('+60 days')),
        'status' => 'approved'
    ]
];

// Get admin user ID for posting
$admin_id = 1; // Assuming admin has ID 1
$sql = "SELECT id FROM users WHERE role = 'admin' LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $admin_id = $result->fetch_assoc()['id'];
}

// Insert sample opportunities
echo "<h2>Creating Sample Opportunities</h2>";
echo "<table border='1'>";
echo "<tr><th>Title</th><th>Company</th><th>Status</th></tr>";

foreach ($sample_opportunities as $opp) {
    // Check if opportunity already exists
    $check_sql = "SELECT id FROM opportunities WHERE title = ? AND company_name = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ss", $opp['title'], $opp['company_name']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<tr><td>" . $opp['title'] . "</td><td>" . $opp['company_name'] . "</td><td>Already exists</td></tr>";
    } else {
        // Insert new opportunity
        $sql = "INSERT INTO opportunities (title, company_name, description, requirements, location, type, stipend_salary, application_deadline, posted_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssis", 
            $opp['title'], 
            $opp['company_name'], 
            $opp['description'], 
            $opp['requirements'], 
            $opp['location'], 
            $opp['type'], 
            $opp['stipend_salary'], 
            $opp['application_deadline'], 
            $admin_id, 
            $opp['status']
        );
        
        if ($stmt->execute()) {
            echo "<tr><td>" . $opp['title'] . "</td><td>" . $opp['company_name'] . "</td><td>Created successfully</td></tr>";
        } else {
            echo "<tr><td>" . $opp['title'] . "</td><td>" . $opp['company_name'] . "</td><td>Error: " . $stmt->error . "</td></tr>";
        }
    }
    $stmt->close();
}

echo "</table>";
echo "<p><a href='opportunities.php'>View Opportunities Page</a> | <a href='check_opportunities.php'>Debug Opportunities</a></p>";
?>
