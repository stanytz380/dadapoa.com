<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/provider_functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'client') {
    redirect('../login.php');
}

$search = $_GET['search'] ?? '';
$providers = getAllApprovedProviders();
if ($search) {
    $providers = array_filter($providers, function($p) use ($search) {
        return stripos($p['nickname'], $search) !== false || stripos($p['location'], $search) !== false;
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Dashboard - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <!-- Navigation Buttons -->
    <nav class="client-nav">
        <a href="dashboard.php" class="nav-btn"><i class="fas fa-home"></i> Home</a>
        <a href="video.php" class="nav-btn"><i class="fas fa-video"></i> Video</a>
        <a href="service.php" class="nav-btn"><i class="fas fa-concierge-bell"></i> Service</a>
        <a href="group.php" class="nav-btn"><i class="fas fa-users"></i> Community</a>
        <a href="profile.php" class="nav-btn"><i class="fas fa-user"></i> Profile</a>
    </nav>

    <div class="hero">
        <h1>Karibu, <?php echo htmlspecialchars($_SESSION['nickname']); ?></h1>
        <form method="GET" class="search-form">
            <input type="text" name="search" placeholder="Tafuta kwa jina au mkoa" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit"><i class="fas fa-search"></i> Tafuta</button>
        </form>
    </div>

    <div class="providers-grid">
        <?php foreach ($providers as $provider): ?>
            <div class="provider-card">
                <img src="<?php echo $provider['profile_pic']; ?>" alt="Profile" class="provider-img">
                <h3><?php echo htmlspecialchars($provider['nickname']); ?></h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($provider['location']); ?></p>
                <p><i class="fas fa-circle <?php echo ($provider['status'] == 'Active') ? 'status-active' : 'status-away'; ?>"></i> <?php echo $provider['status']; ?></p>
                <a href="service.php?provider=<?php echo $provider['uid']; ?>" class="premium-btn">Angalia Huduma</a>
            </div>
        <?php endforeach; ?>
        <?php if (count($providers) == 0): ?>
            <p>Hakuna watoa huduma wanaopatikana kwa sasa.</p>
        <?php endif; ?>
    </div>

    <footer>
        <p>© 2026 STANYTZ | <a href="#" id="support-link"><i class="fas fa-headset"></i> Support</a></p>
        <div class="social-links">
            <a href="https://wa.me/<?php echo ADMIN_WHATSAPP; ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
        </div>
    </footer>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
