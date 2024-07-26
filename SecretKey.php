<?php

function encrypt_openssl($msg, $key, $iv) {
    $encryptedMessage = openssl_encrypt($msg, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encryptedMessage); // IV is prefixed to ciphertext for storage
}

function decrypt_openssl($data, $key) {
    $data = base64_decode($data);
    $iv_size = openssl_cipher_iv_length('AES-128-CBC');
    $iv = substr($data, 0, $iv_size);
    $data = substr($data, $iv_size);
    return openssl_decrypt($data, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
}

// Example data and key
$data = "Hello World!";
$key = '1v1LcZTaUW5KkwtOxdDrUWmvITaisxHe9a3YrwM918c='; // Example 16-byte key for AES-128

// Generate random IV
$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CBC'));

echo "Original Data: $data<br>";

$encrypted = encrypt_openssl($data, $key, $iv);
echo "Encrypted (OpenSSL): $encrypted<br>";

$decrypted = decrypt_openssl($encrypted, $key);
echo "Decrypted (OpenSSL): $decrypted<br>";
?>
