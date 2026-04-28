<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/peterpay.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$group_id = $_POST['group_id'] ?? '';
if (!$group_id) {
    redirect('group.php');
}

// Fetch group details
$group_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/groups/$group_id?key=" . FIREBASE_API_KEY;
$group_data = firebase_get($group_url);
if (!isset($group_data['fields'])) {
    $_SESSION['flash_error'] = "Group haipatikani.";
    redirect('group.php');
}

$join_fee = $group_data['fields']['join_fee']['integerValue'] ?? 0;
if ($join_fee == 0) {
    $_SESSION['flash_error'] = "Group hii ni bure, hakuna malipo.";
    redirect('group.php');
}

$client_data = firebase_get_user($_SESSION['uid']);
$client_phone = $client_data['fields']['phone']['stringValue'] ?? '';
if (empty($client_phone)) {
    $_SESSION['flash_error'] = "Namba yako ya simu haijapatikana.";
    redirect('group.php');
}

$reference = 'GROUP_' . uniqid() . '_' . $_SESSION['uid'];
$callback_url = BASE_URL . 'peterpay_callback.php';

$payment = peterpay_initiate_payment($client_phone, $join_fee, $reference, $callback_url);

if ($payment && isset($payment['status']) && $payment['status'] == 'success') {
    $_SESSION['pending_payment'] = [
        'reference' => $reference,
        'group_id' => $group_id,
        'amount' => $join_fee,
        'type' => 'group',
        'client_uid' => $_SESSION['uid']
    ];
    $pending_data = [
        'fields' => [
            'reference' => ['stringValue' => $reference],
            'client_id' => ['stringValue' => $_SESSION['uid']],
            'group_id' => ['stringValue' => $group_id],
            'amount' => ['integerValue' => $join_fee],
            'type' => ['stringValue' => 'group'],
            'status' => ['stringValue' => 'pending'],
            'created_at' => ['timestampValue' => date('c')]
        ]
    ];
    $doc_id = uniqid();
    $firestore_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/pending_payments/$doc_id?key=" . FIREBASE_API_KEY;
    firebase_post($firestore_url, $pending_data);
    $_SESSION['flash_success'] = "Ombi la malipo limeanza. Tuma USSD kwenye simu yako.";
} else {
    $_SESSION['flash_error'] = "Malipo yameshindikana.";
}
redirect('group.php');
?>
