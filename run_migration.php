<?php
require_once 'includes/config.php';

echo "Starting database migration...\n";

// Read the migration file
$migrationFile = __DIR__ . '/database/migrations/20231105_add_application_status_history.sql';

if (!file_exists($migrationFile)) {
    die("Error: Migration file not found at $migrationFile\n");
}

$sql = file_get_contents($migrationFile);

if (empty($sql)) {
    die("Error: Migration file is empty\n");
}

// Split the SQL into individual queries
$queries = explode(';', $sql);
$successCount = 0;
$errorCount = 0;

// Execute each query
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

echo "\nMigration completed.\n";
echo "Successful queries: $successCount\n";
echo "Failed queries: $errorCount\n";

if ($errorCount > 0) {
    echo "\nSome queries failed to execute. Please check the errors above.\n";
    exit(1);
}

echo "\nDatabase migration completed successfully!\n";
?>
