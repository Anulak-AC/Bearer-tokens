<?php
// Set CORS headers to allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'vendor/autoload.php';
use \Firebase\JWT\JWT;

// Load configuration
$config = require 'config.php';

// Retrieve the secret key from the environment variable or config file
$secretKey = getenv('SECRET_KEY') ?: $config['secretKey']; 
if (!$secretKey) {
    die('Secret key not set');
}

// Get the HTTP request method
$requestMethod = $_SERVER["REQUEST_METHOD"];

// Read the incoming data
$data = file_get_contents("php://input");

// Decode the JSON data into an associative array
$result = json_decode($data, true);

// Check if the request method is POST
if ($requestMethod == 'POST') {
    // Check if the form data is provided
    if (!empty($result) && isset($result['username']) && isset($result['password'])) {
        // Retrieve and validate username and password from the form data
        $username = $result['username'];
        $password = $result['password'];

        // For demonstration, hardcoded user credentials
        $validUsername = 'admin';
        $validPassword = 'password123'; // In a real application, passwords should be hashed

        if ($username === $validUsername && $password === $validPassword) {
            // Set the expiration time for the token (1 hour from now)
            $expiration = time() + 3600;

            // Payload data to be included in the token
            $payload = [
                'iss' => 'http://localhost:82',  // Issuer
                'aud' => 'http://localhost:82',  // Audience
                'iat' => time(),              // Issued at
                'exp' => $expiration,         // Expiration time
                'username' => $username       // Custom data (e.g., username)
            ];

            // Encode the payload to generate the JWT token
            $jwt = JWT::encode($payload, $secretKey, 'HS512');

            // Return the token and expiration time in the JSON response
            echo json_encode([
                'status' => 'success',
                'Token' => $jwt,
                'Expiration_Date' => $expiration,
                'message' => 'Login successful'
            ]);
        } else {
            // Return an error message if credentials are invalid
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid credentials'
            ]);
        }
    } else {
        // Return an error message if the required data is missing
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required data'
        ]);
    }
} else {
    // Return a method not allowed response if the request method is not POST
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
}
?>
