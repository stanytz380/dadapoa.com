<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';
require_once 'includes/functions.php';

$error = '';
$whatsapp = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $result = firebase_login_user($email, $password);
    if (isset($result['error'])) {
        $error = "Email au password si sahihi.";
    } else {
        $uid = $result['localId'];
        $userData = firebase_get_user($uid);
        if (!isset($userData['fields'])) {
            $error = "Taarifa za mtumiaji hazipatikani.";
        } else {
            $fields = $userData['fields'];
            $approved = $fields['approved']['booleanValue'] ?? false;
            $banned = $fields['banned']['booleanValue'] ?? false;
            $account_type = $fields['account_type']['stringValue'];
            $nickname = $fields['nickname']['stringValue'];
            if ($banned) {
                $error = "Account yako imebanwa. Wasiliana na msimamizi.";
            } elseif ($account_type == 'service' && !$approved) {
                $whatsapp = "https://wa.me/" . ADMIN_WHATSAPP . "?text=Naomba%20niapprove%20account%20yangu%20$nickname";
                $error = "Account yako haijaapprove bado. Wasiliana na admin kwa WhatsApp.";
            } else {
                $_SESSION['uid'] = $uid;
                $_SESSION['nickname'] = $nickname;
                $_SESSION['account_type'] = $account_type;
                if ($account_type == 'client') redirect('client/dashboard.php');
                elseif ($account_type == 'service') redirect('provider/dashboard.php');
                else redirect('admin/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:500px; margin:50px auto; background:white; padding:30px; border-radius:30px;">
    <h2>Ingia</h2>
    <?php if($error): ?>
        <div class="error" style="color:red;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($whatsapp): ?>
        <a href="<?php echo $whatsapp; ?>" target="_blank" class="whatsapp-btn"><i class="fab fa-whatsapp"></i> Wasiliana na Admin</a>
    <?php endif; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Barua pepe" required style="width:100%; padding:12px; margin:8px 0;">
        <input type="password" name="password" placeholder="Password" required style="width:100%; padding:12px; margin:8px 0;">
        <button type="submit" class="premium-btn" style="width:100%;">Ingia</button>
    </form>
    <p>Huna account? <a href="register.php">Jisajili</a></p>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
