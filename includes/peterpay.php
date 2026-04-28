<?php
require_once 'config.php';
function peterpay_initiate_payment($phone, $amount, $reference, $callback_url) {
    $phone = preg_replace('/^\+?255|^0/', '', $phone); $phone = '255' . $phone;
    $url = "https://peterpay.co.tz/api/v1/ussd/push";
    $payload = ['merchant_id'=>PETERPAY_MERCHANT_ID, 'api_key'=>PETERPAY_API_KEY, 'phone'=>$phone, 'amount'=>(float)$amount, 'reference'=>$reference, 'callback_url'=>$callback_url];
    $ch = curl_init($url); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); $resp = curl_exec($ch); curl_close($ch); return json_decode($resp, true);
}
function peterpay_verify_payment($transaction_id) {
    $url = "https://peterpay.co.tz/api/v1/transaction/status";
    $payload = ['merchant_id'=>PETERPAY_MERCHANT_ID, 'api_key'=>PETERPAY_API_KEY, 'transaction_id'=>$transaction_id];
    $ch = curl_init($url); curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload)); curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); $resp = curl_exec($ch); curl_close($ch); return json_decode($resp, true);
}
?>
