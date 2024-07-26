<?php
// Define the path to the SQLite database file
$dbPath = __DIR__ . '/database.db'; // Change the path if necessary

try {
    // Create a new SQLite3 database instance
    $db = new SQLite3($dbPath);

    // SQL statement to create the users table if it doesn't exist
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    // Execute the SQL statement
    $db->exec($sql);

    echo "Database and table created successfully.";
} catch (Exception $e) {
    // Display error message if there is an issue with database creation
    die('Database error: ' . $e->getMessage());
}
?>
