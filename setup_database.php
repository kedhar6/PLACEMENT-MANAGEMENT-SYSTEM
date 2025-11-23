<?php
// Disable error reporting for production
// error_reporting(0);
// For development, show all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config file
require_once 'includes/config.php';

// Function to execute SQL file
function executeSQLFile($conn, $file) {
    // Check if file exists
    if (!file_exists($file)) {
        die("Error: SQL file not found: $file\n");
    }
    
    // Read the SQL file
    $sql = file_get_contents($file);
    
    if (empty($sql)) {
        die("Error: SQL file is empty: $file\n");
    }
    
    // Remove comments and split into individual queries
    $sql = preg_replace("/--.*$/m", "", $sql);
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    echo "Executing SQL file: " . basename($file) . "\n";
    echo str_repeat("=", 50) . "\n";
    
    // Execute each query
    foreach ($queries as $query) {
        $query = trim($query);
        
        if (empty($query)) {
            continue;
        }
        
        $queryType = strtoupper(substr(trim($query), 0, strpos(trim($query), ' ')));
        echo "[{$queryType}] " . substr($query, 0, 100) . (strlen($query) > 100 ? '...' : '') . "\n";
        
        try {
            if ($conn->multi_query($query)) {
                do {
                    // Store first result set
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());
                
                if ($conn->error) {
                    throw new Exception($conn->error);
                }
                
                $successCount++;
                echo "  ✓ Success\n";
            } else {
                throw new Exception($conn->error);
            }
        } catch (Exception $e) {
            $errorCount++;
            echo "  ✗ Error: " . $e->getMessage() . "\n";
            
            // If it's a table already exists error, we can continue
            if (strpos($e->getMessage(), 'already exists') !== false) {
                $successCount++;
                $errorCount--;
                echo "  ✓ Table already exists, continuing...\n";
            }
        }
        
        echo "\n";
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Completed: " . basename($file) . "\n";
    echo "Successful queries: $successCount\n";
    echo "Failed queries: $errorCount\n\n";
    
    return $errorCount === 0;
}

// Main execution
echo "CPMS Database Setup\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    // Execute the schema file
    $schemaFile = __DIR__ . '/database/create_complete_schema.sql';
    $success = executeSQLFile($conn, $schemaFile);
    
    if ($success) {
        echo "\nDatabase setup completed successfully!\n";
        echo "You can now access the system with the following credentials:\n";
        echo "Username: admin\n";
        echo "Password: admin123\n\n";
        echo "IMPORTANT: Change the default admin password after first login!\n";
    } else {
        echo "\nDatabase setup completed with some errors. Please check the output above for details.\n";
    }
    
} catch (Exception $e) {
    echo "\nAn error occurred: " . $e->getMessage() . "\n";
}

// Close connection
$conn->close();
?>
