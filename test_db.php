<?php
// Include the config file
require_once 'includes/config.php';

// Test database connection
echo "<h2>Testing Database Connection</h2>";

if ($conn->connect_error) {
    die("<p style='color: red;'>Connection failed: " . $conn->connect_error . "</p>");
} else {
    echo "<p style='color: green;'>✅ Successfully connected to the database!</p>";
    
    // Test if the database exists and is accessible
    $result = $conn->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    echo "<p>Current database: " . $row[0] . "</p>";
    
    // List all tables in the database
    $tables = $conn->query("SHOW TABLES");
    if ($tables->num_rows > 0) {
        echo "<h3>Tables in the database:</h3>";
        echo "<ul>";
        while($table = $tables->fetch_array()) {
            echo "<li>" . $table[0] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No tables found in the database.</p>";
    }
}
?>
