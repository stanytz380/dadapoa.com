<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/firebase_rest.php';

// Kama ameingia, mpeleke kwenye dashboard yake
if (isset($_SESSION['uid'])) {
    if ($_SESSION['account_type'] == 'client') header('Location: client/dashboard.php');
    elseif ($_SESSION['account_type'] == 'service') header('Location: provider/dashboard.php');
    else header('Location: admin/index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$providers = [];
// Fetch approved service providers from Firestore (simplified)
// Kwa mfano, tutatumia query rahisi. Hapa nitaweka tupu kwa sasa, utakuja kuijaza
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dadapoa - Tafuta Huduma</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Link preview meta tags -->
    <meta property="og:title" content="Dadapoa - Tafuta Huduma Kwa Urahisi">
    <meta property="og:description" content="Wasiliana na watoa huduma, video na groups">
    <meta property="og:image" content="<?php echo BASE_URL; ?>assets/images/logo.png">
    <meta property="og:url" content="<?php echo BASE_URL; ?>">
</head>
<body>
    <div class="wrapper">
        <header>
            <div class="logo">DADAPOA</div>
            <div class="lang-switch">
                <button id="lang-en">English</button> | <button id="lang-sw">Swahili</button>
            </div>
        </header>

        <main>
            <div class="hero">
                <h1 data-lang-key="welcome">Karibu Dadapoa</h1>
                <form method="GET" action="" class="search-form">
                    <input type="text" name="search" placeholder="Tafuta kwa mkoa au jina" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><i class="fas fa-search"></i> Tafuta</button>
                </form>
            </div>

            <div class="provider-list">
                <h2 data-lang-key="providers">Watoa Huduma Walio Karibu Nawe</h2>
                <div class="providers-grid">
                    <?php if (count($providers) == 0): ?>
                        <p data-lang-key="no_providers">Hakuna watoa huduma bado. Jaribu tena baadaye.</p>
                    <?php else: ?>
                        <!-- Loopy providers -->
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer>
            <p>© 2026 STANYTZ</p>
            <div class="social-links">
                <a href="https://wa.me/<?php echo ADMIN_WHATSAPP; ?>" target="_blank"><i class="fab fa-whatsapp"></i></a>
                <!-- channel link -->
                <a href="https://t.me/dadapoachannel" target="_blank"><i class="fab fa-telegram"></i></a>
            </div>
            <p id="support-contact">Wasiliana nasi: support@dadapoa.com</p>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
    <script>
        // Disable right click
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });
    </script>
</body>
</html>
