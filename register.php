<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';
require_once 'includes/functions.php';

$error = $success = $whatsapp_link = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nickname = trim($_POST['nickname']);
    $password = $_POST['password'];
    $account_type = $_POST['account_type'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Email si sahihi.";
    elseif (strlen($password) < 6) $error = "Password iwe angalau herufi 6.";
    else {
        $result = firebase_create_user($email, $password);
        if (isset($result['error'])) $error = "Email tayari imesajiliwa.";
        else {
            $uid = $result['localId'];
            $user_fields = ['fields' => [
                'email' => ['stringValue' => $email],
                'phone' => ['stringValue' => $phone],
                'nickname' => ['stringValue' => $nickname],
                'account_type' => ['stringValue' => $account_type],
                'approved' => ['booleanValue' => ($account_type == 'client')],
                'banned' => ['booleanValue' => false],
                'blue_tick' => ['booleanValue' => false],
                'login_count' => ['integerValue' => 0],
                'created_at' => ['timestampValue' => date('c')]
            ]];
            firebase_save_user($uid, $user_fields);
            if ($account_type == 'client') {
                $_SESSION['uid'] = $uid; $_SESSION['nickname'] = $nickname; $_SESSION['account_type'] = 'client';
                redirect('client/dashboard.php');
            } else {
                $whatsapp_link = "https://wa.me/".ADMIN_WHATSAPP."?text=Naomba%20niapprove%20account%20yangu%20$nickname%20($email)";
                $success = "Account imeundwa. Wasiliana na admin kwa approval.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>Register - Dadapoa</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="container" style="max-width:500px; margin:50px auto; background:white; padding:30px; border-radius:30px;">
    <h2>Jisajili</h2>
    <?php if($error) echo "<div style='color:red;'>$error</div>"; ?>
    <?php if($success) echo "<div style='color:green;'>$success</div><a href='$whatsapp_link' class='whatsapp-btn'>Wasiliana na Admin</a>"; ?>
    <?php if(!$success): ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Barua pepe" required>
        <input type="tel" name="phone" placeholder="Namba ya Simu" required>
        <input type="text" name="nickname" placeholder="Nickname" required>
        <input type="password" name="password" placeholder="Password (min 6)" required>
        <select name="account_type">
            <option value="client">Client Account</option>
            <option value="service">Service Account</option>
        </select>
        <button type="submit" class="premium-btn">Sajili</button>
    </form>
    <?php endif; ?>
    <p>Una account? <a href="login.php">Ingia</a></p>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
