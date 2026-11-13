<?php
// Local defaults are intended for XAMPP development only.
// Set MEDICARE_DB_* environment variables on hosted environments.
$host     = getenv('MEDICARE_DB_HOST') ?: 'localhost';
$username = getenv('MEDICARE_DB_USERNAME') ?: 'root';
$password = getenv('MEDICARE_DB_PASSWORD') ?: '';
$database = getenv('MEDICARE_DB_NAME') ?: 'hospital_db';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    error_log('MediCare database connection failed: ' . mysqli_connect_error());
    http_response_code(500);
    exit('Database connection error. Please check the server configuration.');
}

if (!mysqli_set_charset($conn, 'utf8mb4')) {
    error_log('MediCare database charset configuration failed: ' . mysqli_error($conn));
    http_response_code(500);
    exit('Database configuration error.');
}
?>