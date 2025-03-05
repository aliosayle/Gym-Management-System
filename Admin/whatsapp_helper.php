<?php

//replace this with your actual whatstapp api keys
include 'layouts/api_keys.php';

function sendWhatsAppMessage($phoneNumber, $messageBody) {


    // Initialize cURL
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            "recipient_type" => "individual",
            "to" => $phoneNumber,
            "type" => "text",
            "text" => ["body" => $messageBody]
        ]),
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $authToken",
            'Content-Type: application/json'
        ],
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    // Return the response and status
    return [
        'success' => $httpCode === 200,
        'response' => $response,
        'http_code' => $httpCode
    ];
}
