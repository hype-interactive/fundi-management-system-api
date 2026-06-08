
<?php

require 'vendor/autoload.php'; // Composer autoload if using Selcom client

use Dotenv\Dotenv;
use Selcom\ApigwClient\Client;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// ------------------
// Selcom credentials
// ------------------
$apiKey = $_ENV['SELCOM_API_KEY'];
$apiSecret = $_ENV['SELCOM_API_SECRET'];
$vendorId = $_ENV['SELCOM_VENDOR_ID'];
$baseUrl = 'https://apigw.selcommobile.com';

// Initialize Selcom client
$client = new Client($baseUrl, $apiKey, $apiSecret);

// ------------------
// Order payload
// ------------------
$orderArray = [
    'vendor' => $vendorId,
    'order_id' => '1218d5Qb',
    'buyer_email' => 'john@example.com',
    'buyer_name' => 'John Joh',
    'buyer_phone' => '255682555555',
    'amount' => 8000,
    'currency' => 'TZS',
    'buyer_remarks' => 'None',
    'merchant_remarks' => 'None',
    'no_of_items' => 1,
];

// Path relative to base URL
$orderPath = '/v1/checkout/create-order-minimal';

// ------------------
// Create new order
// ------------------
$response = $client->postFunc($orderPath, $orderArray);

// Output response
echo json_encode($response, JSON_PRETTY_PRINT);
