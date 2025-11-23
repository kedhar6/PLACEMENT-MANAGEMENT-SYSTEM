<?php
require_once 'includes/config.php';

echo "Starting database migration...\n";

// Check if database exists, create if not
$conn->query("CREATE DATABASE IF NOT EXISTS cipmsdb");
$conn->select_db('cipmsdb');

// Check if applications table exists, create if not
$result = $conn->query("SHOW TABLES LIKE 'applications'");
if ($result->num_rows === 0) {
    echo "Creating applications table...\n";
    $sql = "CREATE TABLE `applications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `opportunity_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `cover_letter` text DEFAULT NULL,
        `status` enum('applied','shortlisted','rejected','selected','withdrawn') NOT NULL DEFAULT 'applied',
        `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `updated_by` int(11) DEFAULT NULL,
        `notes` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `opportunity_id` (`opportunity_id`),
        KEY `status` (`status`),
        KEY `updated_by` (`updated_by`),
        CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`opportunity_id`) REFERENCES `opportunities` (`id`) ON DELETE CASCADE,
        CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✓ Created applications table\n";
    } else {
        die("✗ Error creating applications table: " . $conn->error . "\n");
    }
}

// Create application_status_history table if not exists
$result = $conn->query("SHOW TABLES LIKE 'application_status_history'");
if ($result->num_rows === 0) {
    echo "Creating application_status_history table...\n";
    $sql = "CREATE TABLE `application_status_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `application_id` int(11) NOT NULL,
        `status` enum('applied','shortlisted','interview','selected','rejected','withdrawn') NOT NULL,
        `notes` text DEFAULT NULL,
        `changed_by` int(11) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `application_id` (`application_id`),
        KEY `status` (`status`),
        KEY `changed_by` (`changed_by`),
        CONSTRAINT `application_status_history_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE CASCADE,
        CONSTRAINT `application_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql)) {
        echo "✓ Created application_status_history table\n";
    } else {
        die("✗ Error creating application_status_history table: " . $conn->error . "\n");
    }
}

// Add notes column if not exists
$result = $conn->query("SHOW COLUMNS FROM `applications` LIKE 'notes'");
if ($result->num_rows === 0) {
    echo "Adding notes column to applications table...\n";
    if ($conn->query("ALTER TABLE `applications` ADD COLUMN `notes` TEXT NULL AFTER `cover_letter`")) {
        echo "✓ Added notes column to applications table\n";
    } else {
        echo "✗ Error adding notes column: " . $conn->error . "\n";
    }
}

// Add updated_by column if not exists
$result = $conn->query("SHOW COLUMNS FROM `applications` LIKE 'updated_by'");
if ($result->num_rows === 0) {
    echo "Adding updated_by column to applications table...\n";
    if ($conn->query("ALTER TABLE `applications` ADD COLUMN `updated_by` INT NULL AFTER `updated_at`")) {
        echo "✓ Added updated_by column to applications table\n";
        
        // Add foreign key constraint
        $conn->query("ALTER TABLE `applications` ADD CONSTRAINT `fk_applications_updated_by` 
                     FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL");
    } else {
        echo "✗ Error adding updated_by column: " . $conn->error . "\n";
    }
}

// Create triggers
function createTrigger($conn, $name, $sql) {
    // Drop trigger if exists
    $conn->query("DROP TRIGGER IF EXISTS `$name`");
    
    // Create trigger
    if ($conn->multi_query($sql)) {
        echo "✓ Created trigger: $name\n";
        // Clear any remaining results
        while ($conn->more_results() && $conn->next_result()) {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        }
    } else {
        echo "✗ Error creating trigger $name: " . $conn->error . "\n";
    }
}

// Create after_application_insert trigger
$triggerSql = "
CREATE TRIGGER after_application_insert
AFTER INSERT ON applications
FOR EACH ROW
BEGIN
    INSERT INTO application_status_history (application_id, status, changed_by, notes)
    VALUES (NEW.id, NEW.status, NEW.student_id, 'Application submitted');
END;";

createTrigger($conn, 'after_application_insert', $triggerSql);

// Create after_application_update trigger
$triggerSql = "
CREATE TRIGGER after_application_update
AFTER UPDATE ON applications
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO application_status_history (application_id, status, changed_by, notes)
        VALUES (NEW.id, NEW.status, 
               CASE 
                   WHEN @current_user_id IS NOT NULL THEN @current_user_id 
                   WHEN NEW.updated_by IS NOT NULL THEN NEW.updated_by 
                   ELSE NULL 
               END,
               CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
    
    IF OLD.notes != NEW.notes AND (OLD.status = NEW.status) THEN
        INSERT INTO application_status_history (application_id, status, changed_by, notes)
        VALUES (NEW.id, NEW.status, 
               CASE 
                   WHEN @current_user_id IS NOT NULL THEN @current_user_id 
                   WHEN NEW.updated_by IS NOT NULL THEN NEW.updated_by 
                   ELSE NULL 
               END,
               'Notes updated');
    END IF;
END;";

createTrigger($conn, 'after_application_update', $triggerSql);

echo "\nMigration completed successfully!\n";
?>
