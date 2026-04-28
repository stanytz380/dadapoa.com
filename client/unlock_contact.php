<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/peterpay.php';

// Hakikisha client ameingia
if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$provider_id = $_POST['provider_id'] ?? '';
if (!$provider_id) {
    redirect('dashboard.php');
}

// Pata namba ya simu ya client kutoka Firebase
$client_data = firebase_get_user($_SESSION['uid']);
$client_phone = $client_data['fields']['phone']['stringValue'] ?? '';

if (empty($client_phone)) {
    $_SESSION['flash_error'] = "Namba yako ya simu haijapatikana. Wasiliana na admin.";
    header("Location: service.php?provider=$provider_id");
    exit;
}

$amount = 3500;
$reference = 'CONTACT_' . uniqid() . '_' . $_SESSION['uid'];
$callback_url = BASE_URL . 'peterpay_callback.php';

// Anza malipo kwa Peterpay
$payment_result = peterpay_initiate_payment($client_phone, $amount, $reference, $callback_url);

if ($payment_result && isset($payment_result['status']) && $payment_result['status'] == 'success') {
    // Hifadhi taarifa za malipo yanayosubiri kwenye session na Firestore (optional)
    $_SESSION['pending_payment'] = [
        'reference' => $reference,
        'provider_id' => $provider_id,
        'amount' => $amount,
        'type' => 'contact',
        'client_uid' => $_SESSION['uid'],
        'created_at' => time()
    ];

    // Pia tunaweza kuhifadhi kwenye Firestore collection 'pending_payments' kwa backup
    $pending_data = [
        'fields' => [
            'reference' => ['stringValue' => $reference],
            'client_id' => ['stringValue' => $_SESSION['uid']],
            'provider_id' => ['stringValue' => $provider_id],
            'amount' => ['integerValue' => $amount],
            'type' => ['stringValue' => 'contact'],
            'status' => ['stringValue' => 'pending'],
            'created_at' => ['timestampValue' => date('c')]
        ]
    ];
    $doc_id = uniqid();
    $firestore_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/pending_payments/$doc_id?key=" . FIREBASE_API_KEY;
    firebase_post($firestore_url, $pending_data);

    // Onyesha ujumbe wa kutoa USSD na kuelekeza kwenye page ya provider
    $_SESSION['flash_success'] = "Ombi la malipo limetumwa kwa simu yako ($client_phone). Tafadhali toa USSD push ili kukamilisha. Ukilipa, utaweza kuona mawasiliano ya provider.";
} else {
    // Kama kuna hitilafu
    $error_msg = $payment_result['message'] ?? "Malipo yameshindikana. Jaribu tena baadaye.";
    $_SESSION['flash_error'] = $error_msg;
}

header("Location: service.php?provider=$provider_id");
exit;
?>
