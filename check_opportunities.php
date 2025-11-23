<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Opportunities</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .status-pending { color: orange; }
        .status-approved { color: green; }
        .status-rejected { color: red; }
    </style>
</head>
<body>
    <h1>Opportunities Debug Information</h1>
    
    <h2>All Opportunities in Database</h2>
    <?php
    $sql = "SELECT o.*, u.username as posted_by_name 
            FROM opportunities o 
            JOIN users u ON o.posted_by = u.id 
            ORDER BY o.created_at DESC";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Company</th><th>Status</th><th>Deadline</th><th>Posted By</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
            echo "<td class='status-" . $row['status'] . "'>" . ucfirst($row['status']) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['application_deadline'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['posted_by_name']) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No opportunities found in the database.</p>";
    }
    
    echo "<h2>Approved Opportunities (Current Query)</h2>";
    $sql = "SELECT o.*, u.username as posted_by_name 
            FROM opportunities o 
            JOIN users u ON o.posted_by = u.id 
            WHERE o.status = 'approved' 
            AND o.application_deadline >= CURDATE()
            ORDER BY o.created_at DESC 
            LIMIT 10";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Title</th><th>Company</th><th>Status</th><th>Deadline</th><th>Posted By</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
            echo "<td>" . htmlspecialchars($row['company_name']) . "</td>";
            echo "<td class='status-" . $row['status'] . "'>" . ucfirst($row['status']) . "</td>";
            echo "<td>" . date('M d, Y', strtotime($row['application_deadline'])) . "</td>";
            echo "<td>" . htmlspecialchars($row['posted_by_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No approved opportunities found with current deadline criteria.</p>";
    }
    
    echo "<h2>Quick Actions</h2>";
    echo "<a href='create_sample_opportunities.php'>Create Sample Opportunities</a> | ";
    echo "<a href='approve_all_opportunities.php'>Approve All Opportunities</a> | ";
    echo "<a href='opportunities.php'>View Opportunities Page</a>";
    ?>
</body>
</html>
