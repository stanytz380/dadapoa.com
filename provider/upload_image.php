<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/firebase_rest.php';
if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'service') redirect('../login.php');

// Kwa urahisi, tunapakia picha kwenye folder local, baadaye unaweza kuhamisha Firebase Storage
$uploadDir = '../assets/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$uploadedUrls = [];
foreach ($_FILES['photos']['tmp_name'] as $i => $tmp) {
    $filename = time() . '_' . $_SESSION['uid'] . '_' . $i . '.jpg';
    move_uploaded_file($tmp, $uploadDir . $filename);
    $uploadedUrls[] = BASE_URL . 'assets/uploads/' . $filename;
}

$profile = getProviderProfile($_SESSION['uid']);
$existingPhotos = $profile['photos'] ?? [];
$allPhotos = array_merge($existingPhotos, $uploadedUrls);
$allPhotos = array_slice($allPhotos, 0, 5); // limit 5
saveProviderProfile($_SESSION['uid'], ['photos' => $allPhotos]);

redirect('dashboard.php');
?>
