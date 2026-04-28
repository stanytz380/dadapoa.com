<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/provider_functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') redirect('../login.php');

$provider_id = $_GET['provider'] ?? '';
if (!$provider_id) redirect('dashboard.php');

$providerData = firebase_get_user($provider_id);
$fields = $providerData['fields'] ?? [];
$nickname = $fields['nickname']['stringValue'] ?? 'Provider';
$profile = getProviderProfile($provider_id);
$categories = $profile['categories'] ?? [];
$prices = $profile['prices'] ?? [];
$photos = $profile['photos'] ?? [];
$status = $profile['status'] ?? 'Active';
$phone = $profile['phone'] ?? '';
$whatsapp = $profile['whatsapp'] ?? '';

// Check if client has paid for this provider's contact
$hasPaid = false; // TODO: check Firestore payments collection
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($nickname); ?> - Dadapoa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <div class="provider-profile">
        <img src="<?php echo $profile['profile_pic'] ?? '../assets/images/default-avatar.png'; ?>" class="profile-large">
        <h2><?php echo htmlspecialchars($nickname); ?></h2>
        <p>Status: <?php echo $status; ?></p>
        <p>Location: <?php echo htmlspecialchars($profile['location']); ?> - <?php echo htmlspecialchars($profile['place_name']); ?></p>

        <div class="photo-slider">
            <?php foreach ($photos as $photo): ?>
                <img src="<?php echo $photo; ?>" class="slide-img" width="200">
            <?php endforeach; ?>
        </div>

        <h3>Huduma na Bei</h3>
        <ul>
            <?php foreach ($categories as $idx => $cat): ?>
                <li><?php echo htmlspecialchars($cat); ?> - TZS <?php echo htmlspecialchars($prices[$idx] ?? '0'); ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if ($hasPaid): ?>
            <div class="contact-info">
                <p><i class="fas fa-phone"></i> Simu: <?php echo htmlspecialchars($phone); ?></p>
                <p><i class="fab fa-whatsapp"></i> WhatsApp: <?php echo htmlspecialchars($whatsapp); ?></p>
                <a href="https://wa.me/<?php echo $whatsapp; ?>" class="whatsapp-btn" target="_blank">Chat with <?php echo $nickname; ?></a>
            </div>
        <?php else: ?>
            <div class="unlock-section">
                <p>Wasiliana na <?php echo $nickname; ?> kwa TZS 3,500 tu.</p>
                <form action="unlock_contact.php" method="POST">
                    <input type="hidden" name="provider_id" value="<?php echo $provider_id; ?>">
                    <button type="submit" class="premium-btn">Lipa TZS 3,500 <i class="fas fa-lock-open"></i></button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
