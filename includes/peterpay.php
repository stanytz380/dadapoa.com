<?php
require_once __DIR__ . '/config.php';

/**
 * Initiate USSD Push payment via Peterpay
 *
 * @param string $phone - Namba ya simu ya mteja (format: 2557XXXXXXXX)
 * @param float $amount - Kiasi cha malipo (TZS)
 * @param string $reference - Reference unique kwa transaction
 * @param string $callback_url - URL ya callback baada ya malipo kukamilika
 * @return array - Response from Peterpay
 */
function peterpay_initiate_payment($phone, $amount, $reference, $callback_url) {
    // Remove leading zero or +255 if present
    $phone = preg_replace('/^\+?255|^0/', '', $phone);
    $phone = '255' . $phone; // Ensure format 255XXXXXXXXX

    $url = "https://peterpay.co.tz/api/v1/ussd/push";
    
    $payload = [
        'merchant_id' => PETERPAY_MERCHANT_ID,
        'api_key'     => PETERPAY_API_KEY,
        'phone'       => $phone,
        'amount'      => (float)$amount,
        'reference'   => $reference,
        'callback_url'=> $callback_url
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Set true kwenye production ikiwa SSL ni valid
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return ['status' => 'error', 'message' => 'CURL Error: ' . $curlError];
    }

    $decoded = json_decode($response, true);
    
    if ($httpCode != 200) {
        return ['status' => 'error', 'message' => 'HTTP Error: ' . $httpCode, 'raw' => $decoded];
    }

    return $decoded;
}

/**
 * Verify transaction status from Peterpay
 *
 * @param string $transaction_id - Transaction ID from Peterpay response
 * @return array
 */
function peterpay_verify_payment($transaction_id) {
    $url = "https://peterpay.co.tz/api/v1/transaction/status";
    
    $payload = [
        'merchant_id'    => PETERPAY_MERCHANT_ID,
        'api_key'        => PETERPAY_API_KEY,
        'transaction_id' => $transaction_id
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
?>
