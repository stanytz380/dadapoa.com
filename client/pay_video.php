<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/peterpay.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$video_id = $_POST['video_id'] ?? '';
if (!$video_id) {
    redirect('video.php');
}

// Fetch video details
$video_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/videos/$video_id?key=" . FIREBASE_API_KEY;
$video_data = firebase_get($video_url);
if (!isset($video_data['fields'])) {
    $_SESSION['flash_error'] = "Video haipatikani.";
    redirect('video.php');
}

$price = $video_data['fields']['price']['integerValue'] ?? 0;
if ($price == 0) {
    // Free video – no payment needed
    $_SESSION['flash_error'] = "Video hii ni bure, hakuna malipo.";
    redirect('video.php');
}

$client_data = firebase_get_user($_SESSION['uid']);
$client_phone = $client_data['fields']['phone']['stringValue'] ?? '';
if (empty($client_phone)) {
    $_SESSION['flash_error'] = "Namba yako ya simu haijapatikana. Wasiliana na admin.";
    redirect('video.php');
}

$reference = 'VIDEO_' . uniqid() . '_' . $_SESSION['uid'];
$callback_url = BASE_URL . 'peterpay_callback.php';

$payment = peterpay_initiate_payment($client_phone, $price, $reference, $callback_url);

if ($payment && isset($payment['status']) && $payment['status'] == 'success') {
    $_SESSION['pending_payment'] = [
        'reference' => $reference,
        'video_id' => $video_id,
        'amount' => $price,
        'type' => 'video',
        'client_uid' => $_SESSION['uid']
    ];
    // Hifadhi pending kwenye Firestore pia
    $pending_data = [
        'fields' => [
            'reference' => ['stringValue' => $reference],
            'client_id' => ['stringValue' => $_SESSION['uid']],
            'video_id' => ['stringValue' => $video_id],
            'amount' => ['integerValue' => $price],
            'type' => ['stringValue' => 'video'],
            'status' => ['stringValue' => 'pending'],
            'created_at' => ['timestampValue' => date('c')]
        ]
    ];
    $doc_id = uniqid();
    $firestore_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/pending_payments/$doc_id?key=" . FIREBASE_API_KEY;
    firebase_post($firestore_url, $pending_data);
    $_SESSION['flash_success'] = "Ombi la malipo limetumwa kwa simu yako ($client_phone). Tafadhali toa USSD push.";
} else {
    $error_msg = $payment['message'] ?? "Malipo yameshindikana. Jaribu tena.";
    $_SESSION['flash_error'] = $error_msg;
}
redirect('video.php');
?>
