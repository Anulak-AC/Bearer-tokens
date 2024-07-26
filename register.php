<?php
// Load configuration
$config = require 'config.php';

// Database configuration
$dbPath = __DIR__ . '/database.db'; // Path to SQLite database file

// Create a SQLite3 instance
try {
    $db = new SQLite3($dbPath);
} catch (Exception $e) {
    sendJsonResponse(500, 'Database connection failed');
}

// Function to send a JSON response
function sendJsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(400, 'Invalid JSON input');
    } elseif (!isset($input['username']) || !isset($input['email']) || !isset($input['password'])) {
        sendJsonResponse(400, 'Missing required fields');
    }
    
    $username = $input['username'];
    $email = $input['email'];
    $password = $input['password'];
    
    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        sendJsonResponse(400, 'All fields are required');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse(400, 'Invalid email format');
    }
    
    // Hash the password before storing it
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Prepare SQL query
    $sql = 'INSERT INTO users (username, email, password) VALUES (:username, :email, :password)';
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);
    $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
    
    try {
        // Execute query
        $result = $stmt->execute();
        sendJsonResponse(200, 'Registration successful', $result  );
    } catch (Exception $e) {
        sendJsonResponse(500, 'Database error');
    }
} else {
    sendJsonResponse(405, 'Method Not Allowed');
}
?>
