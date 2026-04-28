<?php
require_once 'includes/config.php';
require_once 'includes/firebase_rest.php';
$error = '';
$success = '';
$whatsapp_link = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $nickname = trim($_POST['nickname']);
    $password = $_POST['password'];
    $account_type = $_POST['account_type']; // client / service

    // 1. Create user in Firebase Auth
    $auth_result = firebase_create_user($email, $password);
    if (isset($auth_result['error'])) {
        $error = "Email tayari imetumika: " . $auth_result['error']['message'];
    } else {
        $uid = $auth_result['localId'];
        // 2. Prepare Firestore data
        $now = date('c');
        $user_data = [
            'fields' => [
                'email' => ['stringValue' => $email],
                'phone' => ['stringValue' => $phone],
                'nickname' => ['stringValue' => $nickname],
                'account_type' => ['stringValue' => $account_type],
                'approved' => ['booleanValue' => ($account_type == 'client') ? true : false],
                'banned' => ['booleanValue' => false],
                'blue_tick' => ['booleanValue' => false],
                'created_at' => ['timestampValue' => $now]
            ]
        ];
        firebase_save_user($uid, $user_data);
        
        if ($account_type == 'client') {
            // Auto login client
            $_SESSION['uid'] = $uid;
            $_SESSION['nickname'] = $nickname;
            $_SESSION['account_type'] = 'client';
            header('Location: client/dashboard.php');
            exit;
        } else {
            // Service provider: show WhatsApp contact button
            $whatsapp_link = "https://wa.me/" . ADMIN_WHATSAPP . "?text=Naomba%20niapprove%20account%20yangu%20$nickname%20($email)";
            $success = "Account yako imeundwa. Inahitaji kuapprove na admin. Bonyeza hapa kuwasiliana nami.";
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
<div class="container">
    <h2>Register</h2>
    <?php if($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if($success): ?>
        <div class="success"><?php echo $success; ?></div>
        <?php if($whatsapp_link): ?>
            <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="whatsapp-btn"><i class="fab fa-whatsapp"></i> Contact Admin</a>
        <?php endif; ?>
    <?php else: ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="tel" name="phone" placeholder="Namba ya Simu" required>
        <input type="text" name="nickname" placeholder="Nickname" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="account_type" required>
            <option value="client">Client Account</option>
            <option value="service">Service Account</option>
        </select>
        <button type="submit">Register</button>
    </form>
    <?php endif; ?>
    <p>Already have account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
