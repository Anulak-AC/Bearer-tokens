<?php
require 'vendor/autoload.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

// Load configuration
$config = require 'config.php';

// Retrieve the secret key from the environment variable or config file
$secretKey = getenv('SECRET_KEY') ?: $config['secretKey'];
if (!$secretKey) {
    die('Secret key not set');
}

// Function to send a JSON response
function sendJsonResponse($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Function to check Bearer Token
function checkBearerToken($secretKey) {
    $headers = apache_request_headers();
    if (!isset($headers['Authorization'])) {
        sendJsonResponse(401, 'Authorization header not found');
    }
    
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];
        try {
            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS512'));
            return $decoded;
        } catch (Exception $e) {
            sendJsonResponse(401, 'Invalid Bearer Token: ' . $e->getMessage());
        }
    } else {
        sendJsonResponse(401, 'Invalid Authorization Header');
    }
}

// Function to handle POST request
function handlePostRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendJsonResponse(400, 'Invalid JSON input');
    } elseif (!isset($input['data'])) {
        sendJsonResponse(400, 'Data field is required');
    }
    sendJsonResponse(200, 'Data processed successfully', $input);
}

// Check the Bearer token
$userData = checkBearerToken($secretKey);

// Determine the HTTP method and handle accordingly
$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'POST':
        handlePostRequest();
        break;
    case 'GET':
        sendJsonResponse(200, 'Token is valid', ['userData' => $userData]);
        break;
    default:
        sendJsonResponse(405, 'Method Not Allowed');
}
?>
