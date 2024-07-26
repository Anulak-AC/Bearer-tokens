<?php
require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

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

function sendJsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

function checkBearerToken() {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        sendJsonResponse(401, 'Authorization header not found');
    }
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
        try {
            return JWT::decode($jwt, new Key($GLOBALS['secretKey'], 'HS512'));
        } catch (Exception $e) {
            sendJsonResponse(401, 'Invalid Bearer Token');
        }
    } else {
        sendJsonResponse(401, 'Invalid Authorization Header');
    }
}

$userData = checkBearerToken();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve all users from the database
        $result = $db->query('SELECT id, username, email FROM users');
        $users = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }

        if ($users) {
            sendJsonResponse(200, 'Users retrieved successfully', $users);
        } else {
            sendJsonResponse(404, 'No users found');
        }
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            sendJsonResponse(400, 'Invalid input');
        }

        $id = $input['id'];
        $username = isset($input['username']) ? $input['username'] : null;
        $email = isset($input['email']) ? $input['email'] : null;
        $currentPassword = isset($input['currentPassword']) ? $input['currentPassword'] : null;
        $newPassword = isset($input['newPassword']) ? $input['newPassword'] : null;

        // Fetch current user data
        $stmt = $db->prepare('SELECT username, email, password FROM users WHERE id = :id');
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);

        if (!$user) {
            sendJsonResponse(404, 'User not found');
        }

        // Update user data
        if ($username || $email) {
            $stmt = $db->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id');
            $stmt->bindValue(':username', $username ?? $user['username'], SQLITE3_TEXT);
            $stmt->bindValue(':email', $email ?? $user['email'], SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            if (!$stmt->execute()) {
                sendJsonResponse(500, 'Failed to update user data');
            }
        }

        // Change password
        if ($currentPassword && $newPassword) {
            if (!password_verify($currentPassword, $user['password'])) {
                sendJsonResponse(400, 'Current password is incorrect');
            }

            $hashedNewPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
            $stmt->bindValue(':password', $hashedNewPassword, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);

            if (!$stmt->execute()) {
                sendJsonResponse(500, 'Failed to update password');
            }
        }

        sendJsonResponse(200, 'User data updated successfully');
        break;

    default:
        sendJsonResponse(405, 'Method Not Allowed');
}
?>
