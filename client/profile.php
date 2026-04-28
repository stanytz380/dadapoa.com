<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$uid = $_SESSION['uid'];
$userData = firebase_get_user($uid);
$fields = $userData['fields'] ?? [];
$nickname = $fields['nickname']['stringValue'] ?? '';
$email = $fields['email']['stringValue'] ?? '';
$phone = $fields['phone']['stringValue'] ?? '';
$member_since = isset($fields['created_at']['timestampValue']) ? date('d M Y', strtotime($fields['created_at']['timestampValue'])) : 'Unknown';

// Handle password change (optional)
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Passwords hazifanani.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password mpya iwe angalau herufi 6.";
    } else {
        // Re-authenticate user before changing password
        $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . FIREBASE_API_KEY;
        $data = ['email' => $email, 'password' => $old_password, 'returnSecureToken' => true];
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($ch);
        curl_close($ch);
        $auth = json_decode($resp, true);
        
        if (isset($auth['error'])) {
            $error = "Password ya sasa si sahihi.";
        } else {
            // Update password
            $update_url = "https://identitytoolkit.googleapis.com/v1/accounts:update?key=" . FIREBASE_API_KEY;
            $update_data = ['idToken' => $auth['idToken'], 'password' => $new_password, 'returnSecureToken' => true];
            $ch2 = curl_init($update_url);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($update_data));
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch2);
            curl_close($ch2);
            $success = "Password imebadilishwa kikamilifu.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Profile - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <nav class="client-nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="video.php"><i class="fas fa-video"></i> Video</a>
        <a href="service.php"><i class="fas fa-concierge-bell"></i> Service</a>
        <a href="group.php"><i class="fas fa-users"></i> Community</a>
        <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
    </nav>

    <div class="profile-container" style="max-width:600px; margin:20px auto;">
        <h2>Profile Yangu</h2>
        <?php if($error): ?><div class="error" style="color:red;"><?php echo $error; ?></div><?php endif; ?>
        <?php if($success): ?><div class="success" style="color:green;"><?php echo $success; ?></div><?php endif; ?>
        
        <p><strong>Nickname:</strong> <?php echo htmlspecialchars($nickname); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Namba ya Simu:</strong> <?php echo htmlspecialchars($phone); ?></p>
        <p><strong>Member since:</strong> <?php echo $member_since; ?></p>
        
        <hr>
        <h3>Badilisha Password</h3>
        <form method="POST">
            <input type="password" name="old_password" placeholder="Password ya sasa" required>
            <input type="password" name="new_password" placeholder="Password mpya" required>
            <input type="password" name="confirm_password" placeholder="Thibitisha password mpya" required>
            <button type="submit" name="change_password" class="premium-btn">Badilisha Password</button>
        </form>
        
        <p style="margin-top:20px;"><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></p>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
