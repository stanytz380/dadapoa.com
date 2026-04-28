<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';

$input = json_decode(file_get_contents('php://input'), true);
$reference = $input['reference'] ?? '';
$status = $input['status'] ?? '';

if ($status == 'completed') {
    // Find pending payment
    if (isset($_SESSION['pending_payment']) && $_SESSION['pending_payment']['reference'] == $reference) {
        $type = $_SESSION['pending_payment']['type'];
        if ($type == 'contact') {
            $provider_id = $_SESSION['pending_payment']['provider_id'];
            // Save payment record
            $payment_id = uniqid();
            $data = ['fields' => [
                'client_id' => ['stringValue' => $_SESSION['uid']],
                'provider_id' => ['stringValue' => $provider_id],
                'amount' => ['integerValue' => $_SESSION['pending_payment']['amount']],
                'reference' => ['stringValue' => $reference],
                'status' => ['stringValue' => 'paid'],
                'created_at' => ['timestampValue' => date('c')]
            ]];
            $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/payments/$payment_id?key=" . FIREBASE_API_KEY;
            firebase_post($url, $data);
            unset($_SESSION['pending_payment']);
        }
    }
}
http_response_code(200);
echo "OK";
?>
