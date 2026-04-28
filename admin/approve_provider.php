<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] !== 'admin') redirect('../login.php');

$uid = $_GET['uid'] ?? '';
if ($uid) {
    firebase_update_user($uid, [
        'approved' => ['booleanValue' => true]
    ]);
}
redirect('index.php');
?>
