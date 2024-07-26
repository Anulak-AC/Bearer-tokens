<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load configuration
$config = require 'config.php';
$dbPath = __DIR__ . '/database.db'; // Path to SQLite database file

// Create a SQLite3 instance
try {
    $db = new SQLite3($dbPath);
} catch (Exception $e) {
    sendJsonResponse(500, 'Database connection failed: ' . $e->getMessage());
}

$secretKey = getenv('SECRET_KEY') ?: $config['secretKey'];

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
    } elseif (!isset($input['email']) || !isset($input['password'])) {
        sendJsonResponse(400, 'Missing required fields');
    }

    $email = $input['email'];
    $password = $input['password'];

    if (empty($email) || empty($password)) {
        sendJsonResponse(400, 'All fields are required');
    }

    // Fetch user from the database
    $sql = 'SELECT id, username, password FROM users WHERE email = :email LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':email', $email, SQLITE3_TEXT);

    $result = $stmt->execute();
    if ($result === false) {
        sendJsonResponse(500, 'Failed to execute query');
    }

    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $payload = [
            'iss' => 'http://localhost',
            'aud' => 'http://localhost',
            'iat' => time(),
            'exp' => time() + 3600,
            'userId' => $user['id']
        ];

        try {
            // Correct usage of JWT::encode()
            $jwt = JWT::encode($payload, $secretKey, 'HS512'); // Include the algorithm
            sendJsonResponse(200, 'Login successful', ['token' => $jwt]);
        } catch (Exception $e) {
            sendJsonResponse(500, 'Failed to generate token: ' . $e->getMessage());
        }
    } else {
        sendJsonResponse(401, 'Invalid email or password');
    }
} else {
    sendJsonResponse(405, 'Method Not Allowed');
}
?>
