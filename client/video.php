<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

// Fetch all videos from Firestore
$videos = [];
$queryResult = firestore_query_collection('videos');
if (is_array($queryResult)) {
    foreach ($queryResult as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            $video_id = basename($doc['document']['name']);
            $price = $fields['price']['integerValue'] ?? 0;
            $videos[] = [
                'id' => $video_id,
                'title' => $fields['title']['stringValue'] ?? 'No title',
                'price' => $price,
                'url' => $fields['video_url']['stringValue'] ?? '#',
                'created_at' => $fields['created_at']['timestampValue'] ?? date('c')
            ];
        }
    }
}

// Helper: Check if client has purchased a specific video
function hasPurchasedVideo($client_id, $video_id) {
    $query_url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents:runQuery?key=" . FIREBASE_API_KEY;
    $query = [
        'structuredQuery' => [
            'from' => [['collectionId' => 'video_purchases']],
            'where' => [
                'compositeFilter' => [
                    'op' => 'AND',
                    'filters' => [
                        ['fieldFilter' => ['field' => ['fieldPath' => 'client_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $client_id]]],
                        ['fieldFilter' => ['field' => ['fieldPath' => 'video_id'], 'op' => 'EQUAL', 'value' => ['stringValue' => $video_id]]],
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

// Get flash messages
$flash_success = $_SESSION['flash_success'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Video - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; margin-top: 30px; }
        .video-card { background: white; border-radius: 20px; padding: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .video-card h3 { margin: 10px 0; }
        video { width: 100%; border-radius: 12px; }
        .video-actions { margin-top: 15px; display: flex; gap: 10px; justify-content: space-between; }
        .premium-btn-sm { background: #c31432; color: white; padding: 8px 16px; border-radius: 40px; text-decoration: none; font-size: 14px; }
        .free-badge { background: #4caf50; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; display: inline-block; }
        .paid-badge { background: #ff9800; color: white; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .flash-success { background: #4caf50; color: white; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
        .flash-error { background: #f44336; color: white; padding: 12px; border-radius: 10px; margin-bottom: 20px; }
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

    <h2>Sehemu ya Video</h2>
    <div class="videos-grid">
        <?php if (count($videos) == 0): ?>
            <p>Hakuna video zilizopo kwa sasa. Tazama baadaye.</p>
        <?php else: ?>
            <?php foreach ($videos as $video): ?>
                <?php $purchased = hasPurchasedVideo($_SESSION['uid'], $video['id']); ?>
                <div class="video-card">
                    <h3><?php echo htmlspecialchars($video['title']); ?>
                        <?php if ($video['price'] == 0): ?>
                            <span class="free-badge">Free</span>
                        <?php else: ?>
                            <span class="paid-badge">TZS <?php echo number_format($video['price']); ?></span>
                        <?php endif; ?>
                    </h3>
                    <?php if ($video['price'] == 0 || $purchased): ?>
                        <!-- Free or purchased video: show player and download -->
                        <video controls src="<?php echo htmlspecialchars($video['url']); ?>"></video>
                        <div class="video-actions">
                            <a href="<?php echo htmlspecialchars($video['url']); ?>" download class="premium-btn-sm"><i class="fas fa-download"></i> Download</a>
                        </div>
                    <?php else: ?>
                        <!-- Paid not yet purchased: show pay button -->
                        <div class="video-preview">
                            <p><i class="fas fa-lock"></i> Video hii inalipishwa TZS <?php echo number_format($video['price']); ?></p>
                            <form action="pay_video.php" method="POST">
                                <input type="hidden" name="video_id" value="<?php echo $video['id']; ?>">
                                <button type="submit" class="premium-btn-sm"><i class="fas fa-shopping-cart"></i> Lipa na Utazame</button>
                            </form>
                        </div>
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
