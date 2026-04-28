<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $result = firebase_login_user($email, $password);
    if (isset($result['error'])) {
        $error = "Email au password si sahihi.";
    } else {
        $uid = $result['localId'];
        $userdata = firebase_get_user($uid);
        if (!isset($userdata['fields'])) {
            $error = "Data ya user haipatikani.";
        } else {
            $fields = $userdata['fields'];
            $approved = $fields['approved']['booleanValue'] ?? false;
            $banned = $fields['banned']['booleanValue'] ?? false;
            $account_type = $fields['account_type']['stringValue'];
            if ($banned) {
                $error = "Account imebanwa. Wasiliana na admin.";
            } elseif ($account_type == 'service' && !$approved) {
                $error = "Account yako haijaapprove bado. Wasiliana na admin kwa WhatsApp.";
                $whatsapp = "https://wa.me/" . ADMIN_WHATSAPP . "?text=Naomba%20approval%20ya%20account%20yangu";
            } else {
                $_SESSION['uid'] = $uid;
                $_SESSION['nickname'] = $fields['nickname']['stringValue'];
                $_SESSION['account_type'] = $account_type;
                if ($account_type == 'client') header('Location: client/dashboard.php');
                elseif ($account_type == 'service') header('Location: provider/dashboard.php');
                else header('Location: admin/index.php');
                exit;
            }
        }
    }
}
?>
<!-- HTML form ya login, sawa na register lakini na email/password tu -->
