<?php
require_once 'includes/config.php';

// Read the SQL file
$sqlFile = __DIR__ . '/database/create_tables.sql';

if (!file_exists($sqlFile)) {
    die("Error: SQL file not found at $sqlFile\n");
}

$sql = file_get_contents($sqlFile);

if (empty($sql)) {
    die("Error: SQL file is empty\n");
}

// Split the SQL into individual queries
$queries = explode(';', $sql);
$successCount = 0;
$errorCount = 0;

// Execute each query
echo "Starting database setup...\n";

foreach ($queries as $query) {
    $query = trim($query);
    
    if (empty($query)) {
        continue;
    }
    
    echo "Executing: " . substr($query, 0, 100) . "...\n";
    
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
            echo "✓ Success\n";
        } else {
            throw new Exception($conn->error);
        }
    } catch (Exception $e) {
        $errorCount++;
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nDatabase setup completed.\n";
echo "Successful queries: $successCount\n";
echo "Failed queries: $errorCount\n";

if ($errorCount > 0) {
    echo "\nSome queries failed to execute. Please check the errors above.\n";
    exit(1);
}

echo "\nDatabase setup completed successfully!\n";
?>
