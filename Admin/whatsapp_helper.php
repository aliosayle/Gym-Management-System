<?php
function sendWhatsAppMessage($phoneNumber, $messageBody) {
    // Define the WhatsApp API endpoint and authorization token
    $apiUrl = 'http://www.00243.net:3001/api/v1/messages';
    $authToken = 'u4xKAyGrv8LUaPzR.zSRIH21JkxCr0IZ4Pk1wPQbVDSqHRl03';

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
