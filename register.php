<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$whatsapp_link = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nickname = trim($_POST['nickname']);
    $password = $_POST['password'];
    $account_type = $_POST['account_type']; // client au service

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Barua pepe si sahihi.";
    } elseif (strlen($password) < 6) {
        $error = "Password iwe angalau herufi 6.";
    } else {
        $result = firebase_create_user($email, $password);
        if (isset($result['error'])) {
            $error = "Email tayari imesajiliwa: " . $result['error']['message'];
        } else {
            $uid = $result['localId'];
            $now = date('c');
            $user_fields = [
                'email' => ['stringValue' => $email],
                'phone' => ['stringValue' => $phone],
                'nickname' => ['stringValue' => $nickname],
                'account_type' => ['stringValue' => $account_type],
                'approved' => ['booleanValue' => ($account_type == 'client') ? true : false],
                'banned' => ['booleanValue' => false],
                'blue_tick' => ['booleanValue' => false],
                'created_at' => ['timestampValue' => $now]
            ];
            firebase_save_user($uid, ['fields' => $user_fields]);

            if ($account_type == 'client') {
                $_SESSION['uid'] = $uid;
                $_SESSION['nickname'] = $nickname;
                $_SESSION['account_type'] = 'client';
                redirect('client/dashboard.php');
            } else {
                $whatsapp_link = "https://wa.me/" . ADMIN_WHATSAPP . "?text=Naomba%20niapprove%20account%20yangu%20$nickname%20($email)";
                $success = "Account yako imeundwa. Inahitaji kuapprove na admin. Bonyeza hapa kuwasiliana nami.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container" style="max-width:500px; margin:50px auto; background:white; padding:30px; border-radius:30px;">
    <h2>Jisajili</h2>
    <?php if($error): ?>
        <div class="error" style="color:red;"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="success" style="color:green;"><?php echo $success; ?></div>
        <?php if($whatsapp_link): ?>
            <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="whatsapp-btn"><i class="fab fa-whatsapp"></i> Wasiliana na Admin</a>
        <?php endif; ?>
    <?php else: ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Barua pepe" required style="width:100%; padding:12px; margin:8px 0;">
        <input type="tel" name="phone" placeholder="Namba ya Simu" required style="width:100%; padding:12px; margin:8px 0;">
        <input type="text" name="nickname" placeholder="Nickname" required style="width:100%; padding:12px; margin:8px 0;">
        <input type="password" name="password" placeholder="Password (min 6)" required style="width:100%; padding:12px; margin:8px 0;">
        <select name="account_type" required style="width:100%; padding:12px; margin:8px 0;">
            <option value="client">Client Account</option>
            <option value="service">Service Account</option>
        </select>
        <button type="submit" class="premium-btn" style="width:100%;">Sajili</button>
    </form>
    <?php endif; ?>
    <p>Una account? <a href="login.php">Ingia</a></p>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
