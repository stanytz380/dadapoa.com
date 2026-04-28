<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

// Fetch all groups from Firestore
$groups = [];
$queryResult = firestore_query_collection('groups');
if (is_array($queryResult)) {
    foreach ($queryResult as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            $group_id = basename($doc['document']['name']);
            $join_fee = $fields['join_fee']['integerValue'] ?? 0;
            $groups[] = [
                'id' => $group_id,
                'name' => $fields['name']['stringValue'] ?? 'Group',
                'logo' => $fields['logo']['stringValue'] ?? '../assets/images/default-group.png',
                'total_members' => $fields['total_members']['integerValue'] ?? 0,
                'join_fee' => $join_fee,
                'whatsapp_link' => $fields['whatsapp_link']['stringValue'] ?? '#'
            ];
        }
    }
}

// Helper: Check if client has purchased a specific group
function hasPurchasedGroup($client_id, $group_id) {
    $query_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents:runQuery?key=" . FIREBASE_API_KEY;
    $query = [
        'structuredQuery' => [
            'from' => [['collectionId' => 'group_purchases']],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [
                        ['fieldFilter' => ['field' => ['fieldPath' => 'client_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $client_id]]],
                        ['fieldFilter' => ['field' => ['fieldPath' => 'group_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $group_id]]],
                        ['fieldFilter' => ['field' => ['fieldPath' => 'status'], 'op' => 'EQUAL', 'value' => ['stringValue' => 'paid']]]
                    ]
                ]
            ]
        ]
    ];
    $ch = curl_init($query_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $resp = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($resp, true);
    return !empty($result) && isset($result[0]['document']);
}

$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Groups - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .groups-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; margin-top: 30px; }
        .group-card { background: white; border-radius: 20px; padding: 20px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .group-card img { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
        .group-card h3 { margin: 10px 0; }
        .group-card p { color: #666; margin: 5px 0; }
        .whatsapp-btn, .premium-btn-sm { display: inline-block; margin-top: 15px; padding: 10px 20px; border-radius: 40px; text-decoration: none; color: white; }
        .whatsapp-btn { background: #25D366; }
        .premium-btn-sm { background: #c31432; border: none; cursor: pointer; }
        .flash-success, .flash-error { padding: 12px; border-radius: 10px; margin-bottom: 20px; color: white; }
        .flash-success { background: #4caf50; }
        .flash-error { background: #f44336; }
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

    <h2>Community Groups</h2>
    <div class="groups-grid">
        <?php if (count($groups) == 0): ?>
            <p>Hakuna groups zilizopo kwa sasa. Tazama baadaye.</p>
        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <?php $purchased = hasPurchasedGroup($_SESSION['uid'], $group['id']); ?>
                <div class="group-card">
                    <img src="<?php echo htmlspecialchars($group['logo']); ?>" alt="Group Logo">
                    <h3><?php echo htmlspecialchars($group['name']); ?></h3>
                    <p><i class="fas fa-users"></i> Members: <?php echo number_format($group['total_members']); ?></p>
                    <?php if ($group['join_fee'] == 0 || $purchased): ?>
                        <!-- Free or purchased: show WhatsApp link directly -->
                        <a href="<?php echo htmlspecialchars($group['whatsapp_link']); ?>" target="_blank" class="whatsapp-btn">
                            <i class="fab fa-whatsapp"></i> Join Now <?php if ($group['join_fee'] == 0) echo "Free'; ?>
                        </a>
                    <?php else: ?>
                        <!-- Paid group not yet purchased -->
                        <p><i class="fas fa-lock"></i> Join Fee: TZS <?php echo number_format($group['join_fee']); ?></p>
                        <form action="pay_group.php" method="POST">
                            <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                            <button type="submit" class="premium-btn-sm"><i class="fas fa-shopping-cart"></i> Lipa na Jiunge</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <footer>
        <p>© 2026 STANYTZ | <i class="fas fa-headset"></i> Support: <a href="https://wa.me/<?php echo ADMIN_WHATSAPP; ?>">WhatsApp</a></p>
    </footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
