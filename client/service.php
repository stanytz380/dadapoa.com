<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/provider_functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$provider_id = $_GET['provider'] ?? '';
if (!$provider_id) {
    redirect('dashboard.php');
}

// Fetch provider data
$providerData = firebase_get_user($provider_id);
if (!isset($providerData['fields'])) {
    $_SESSION['flash_error'] = "Mtoa huduma hapatikani.";
    redirect('dashboard.php');
}
$fields = $providerData['fields'];
$nickname = $fields['nickname']['stringValue'] ?? 'Provider';
$profile = getProviderProfile($provider_id);
$categories = $profile['categories'] ?? [];
$prices = $profile['prices'] ?? [];
$photos = $profile['photos'] ?? [];
$status = $profile['status'] ?? 'Active';
$phone = $profile['phone'] ?? '';
$whatsapp = $profile['whatsapp'] ?? '';
$location = $profile['location'] ?? '';
$place_name = $profile['place_name'] ?? '';

// Check if client has paid for this provider's contact
$hasPaid = false;
// Query Firestore collection 'payments' for a successful payment
$payments_query = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents:runQuery?key=" . FIREBASE_API_KEY;
$query_payload = [
    'structuredQuery' => [
        'from' => [['collectionId' => 'payments']],
        'where' => [
            'compositeFilter' => [
                'op' => 'AND',
                'filters' => [
                    ['fieldFilter' => ['field' => ['fieldPath' => 'client_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $_SESSION['uid']]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'provider_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $provider_id]]],
                    ['fieldFilter' => ['field' => ['fieldPath' => 'status'], 'op' => 'EQUAL', 'value' => ['stringValue' => 'paid']]]
                ]
            ]
        ]
    ]
];
$ch = curl_init($payments_query);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$resp = curl_exec($ch);
curl_close($ch);
$payments_result = json_decode($resp, true);
if (!empty($payments_result) && isset($payments_result[0]['document'])) {
    $hasPaid = true;
}

// Also check session flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($nickname); ?> - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .photo-slider { display: flex; overflow-x: auto; gap: 10px; margin: 20px 0; }
        .slide-img { width: 200px; height: 150px; object-fit: cover; border-radius: 12px; }
        .contact-info { background: #e8f5e9; padding: 15px; border-radius: 12px; margin: 20px 0; }
        .unlock-section { background: #fff3e0; padding: 20px; border-radius: 12px; text-align: center; }
        .flash-success { background: #4caf50; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .flash-error { background: #f44336; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .provider-large { text-align: center; }
        .provider-large img { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #c31432; }
    </style>
</head>
<body>
<div class="wrapper">
    <nav class="client-nav">
        <a href="dashboard.php" class="nav-btn"><i class="fas fa-home"></i> Home</a>
        <a href="video.php" class="nav-btn"><i class="fas fa-video"></i> Video</a>
        <a href="service.php" class="nav-btn"><i class="fas fa-concierge-bell"></i> Service</a>
        <a href="group.php" class="nav-btn"><i class="fas fa-users"></i> Community</a>
        <a href="profile.php" class="nav-btn"><i class="fas fa-user"></i> Profile</a>
    </nav>

    <?php if ($flash_success): ?>
        <div class="flash-success"><?php echo htmlspecialchars($flash_success); ?></div>
    <?php endif; ?>
    <?php if ($flash_error): ?>
        <div class="flash-error"><?php echo htmlspecialchars($flash_error); ?></div>
    <?php endif; ?>

    <div class="provider-profile">
        <div class="provider-large">
            <img src="<?php echo htmlspecialchars($profile['profile_pic'] ?? '../assets/images/default-avatar.png'); ?>" alt="Profile">
            <h2><?php echo htmlspecialchars($nickname); ?>
                <?php if ($fields['blue_tick']['booleanValue'] ?? false): ?>
                    <i class="fas fa-check-circle" style="color: #1da1f2;"></i>
                <?php endif; ?>
            </h2>
            <p><i class="fas fa-circle <?php echo ($status == 'Active') ? 'status-active' : 'status-away'; ?>"></i> <?php echo $status; ?></p>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($location); ?> - <?php echo htmlspecialchars($place_name); ?></p>
        </div>

        <?php if (!empty($photos)): ?>
        <div class="photo-slider">
            <?php foreach ($photos as $photo): ?>
                <img src="<?php echo htmlspecialchars($photo); ?>" class="slide-img">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3>Huduma na Bei</h3>
        <ul>
            <?php foreach ($categories as $idx => $cat): ?>
                <li><?php echo htmlspecialchars($cat); ?> - TZS <?php echo number_format($prices[$idx] ?? 0); ?></li>
            <?php endforeach; ?>
        </ul>

        <?php if ($hasPaid): ?>
            <div class="contact-info">
                <p><i class="fas fa-phone-alt"></i> Simu: <a href="tel:<?php echo htmlspecialchars($phone); ?>"><?php echo htmlspecialchars($phone); ?></a></p>
                <p><i class="fab fa-whatsapp"></i> WhatsApp: <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>" target="_blank"><?php echo htmlspecialchars($whatsapp); ?></a></p>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>?text=Halo%20<?php echo urlencode($nickname); ?>%2C%20nimekupata%20kupitia%20Dadapoa" class="whatsapp-btn" target="_blank">
                    <i class="fab fa-whatsapp"></i> Chat with <?php echo htmlspecialchars($nickname); ?>
                </a>
            </div>
        <?php else: ?>
            <div class="unlock-section">
                <p><i class="fas fa-lock"></i> Mawasiliano ya <?php echo htmlspecialchars($nickname); ?> yamefungwa.</p>
                <p>Lipia TZS 3,500 kuuona namba yake na kuwasiliana naye.</p>
                <form action="unlock_contact.php" method="POST">
                    <input type="hidden" name="provider_id" value="<?php echo $provider_id; ?>">
                    <button type="submit" class="premium-btn"><i class="fas fa-lock-open"></i> Lipa TZS 3,500 Sasa</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p>© 2026 STANYTZ | <a href="#" id="support-link"><i class="fas fa-headset"></i> Support</a></p>
    </footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
