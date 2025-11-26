
<?php

require 'vendor/autoload.php'; // Composer autoload if using Selcom client

use Selcom\ApigwClient\Client;

// ------------------
// Selcom credentials
// ------------------
$apiKey = 'TILL61213801-4174f99e707345a190dbc9b46232b4e2';
$apiSecret = 'bdf5e3-a96d8a-460899-1fef9a-b67e4d-ef';
$baseUrl = "https://apigw.selcommobile.com";  // correct base URL

// Initialize Selcom client
$client = new Client($baseUrl, $apiKey, $apiSecret);

// ------------------
// Order payload
// ------------------
$orderArray = [
    "vendor" => "VENDORTILL",
    "order_id" => "1218d5Qb",
    "buyer_email" => "john@example.com",
    "buyer_name" => "John Joh",
    "buyer_phone" => "255682555555",
    "amount" => 8000,
    "currency" => "TZS",
    "buyer_remarks" => "None",
    "merchant_remarks" => "None",
    "no_of_items" => 1
];

// Path relative to base URL
$orderPath = "/v1/checkout/create-order-minimal";

// ------------------
// Create new order
// ------------------
$response = $client->postFunc($orderPath, $orderArray);

// Output response
echo json_encode($response, JSON_PRETTY_PRINT);
