<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';
require_once '../includes/provider_functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] != 'service') {
    redirect('../login.php');
}

$uid = $_SESSION['uid'];
$userData = firebase_get_user($uid);
$nickname = $userData['fields']['nickname']['stringValue'] ?? '';
$profile = getProviderProfile($uid);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    $data = [
        'username' => $_POST['username'],
        'categories' => explode(',', $_POST['categories']),
        'prices' => explode(',', $_POST['prices']),
        'location' => $_POST['location'],
        'place_name' => $_POST['place_name'],
        'phone' => $_POST['phone'],
        'whatsapp' => $_POST['whatsapp'],
        'status' => $_POST['status']
    ];
    saveProviderProfile($uid, $data);
    $success = "Profile imehifadhiwa.";
    $profile = getProviderProfile($uid); // refresh
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Provider Dashboard - Dadapoa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <h1>Dashboard ya Mtoa Huduma: <?php echo htmlspecialchars($nickname); ?></h1>
    <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
    <form method="POST">
        <label>Username (Jina la Kuonyesha):</label>
        <input type="text" name="username" value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>">

        <label>Categories (tenganisha kwa koma, mfano: Shoga, Videocall, Massage):</label>
        <input type="text" name="categories" value="<?php echo implode(',', $profile['categories'] ?? []); ?>">

        <label>Bei kwa kila Category (tenganisha kwa koma kwa mpangilio sawa):</label>
        <input type="text" name="prices" value="<?php echo implode(',', $profile['prices'] ?? []); ?>">

        <label>Mkoa / Eneo (Google Map tayari):</label>
        <input type="text" name="location" value="<?php echo htmlspecialchars($profile['location'] ?? ''); ?>">

        <label>Jina la Hoteli / Lodge / Sehemu:</label>
        <input type="text" name="place_name" value="<?php echo htmlspecialchars($profile['place_name'] ?? ''); ?>">

        <label>Namba ya Simu (ya kawaida):</label>
        <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">

        <label>Namba ya WhatsApp:</label>
        <input type="tel" name="whatsapp" value="<?php echo htmlspecialchars($profile['whatsapp'] ?? ''); ?>">

        <label>Status:</label>
        <select name="status">
            <option <?php echo ($profile['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
            <option <?php echo ($profile['status'] == 'Busy') ? 'selected' : ''; ?>>Busy</option>
            <option <?php echo ($profile['status'] == 'Away') ? 'selected' : ''; ?>>Away</option>
        </select>

        <button type="submit" name="save_profile" class="premium-btn">Hifadhi Profile</button>
    </form>

    <hr>
    <h3>Picha Zako (Angalau 3, max 5)</h3>
    <form action="upload_photos.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="photos[]" multiple accept="image/*">
        <button type="submit">Pakia Picha</button>
    </form>
    <div class="photo-gallery">
        <?php foreach ($profile['photos'] as $photo): ?>
            <img src="<?php echo $photo; ?>" width="100">
        <?php endforeach; ?>
    </div>

    <p><a href="../logout.php">Logout</a></p>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
