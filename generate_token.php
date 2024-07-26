<?php
// Set CORS headers to allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require 'vendor/autoload.php';
use \Firebase\JWT\JWT;
// Load configuration
$config = require 'config.php';
// Retrieve the secret key from the configuration
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
    // Set the expiration time for the token (1 hour from now)
    $expiration = time() + 3600; 
    // Payload data to be included in the token
    $payload = [
        'iss' => 'http://localhost:82',  // Issuer
        'aud' => 'http://localhost:82',  // Audience
        'iat' => time(),              // Issued at
        'exp' => $expiration,         // Expiration time
        'userId' => 123               // Custom data (e.g., user ID)
    ]; 
    // Encode the payload to generate the JWT token
    $jwt = JWT::encode($payload, $secretKey, 'HS512'); 
    // If the incoming data is not empty
    if (!empty($result)) {
        // Return the token and expiration time in the JSON response
        echo json_encode([
            'status' => 'success',
            'Token' => $jwt,
            'Expiration_Date' => $expiration,
            'message' => 'success generate'
        ]);
    } else {
        // Return an error message if something went wrong
        echo json_encode([
            'status' => 'error',
            'message' => 'Error'
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
