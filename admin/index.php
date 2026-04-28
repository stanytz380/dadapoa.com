<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
if (!isset($_SESSION['uid']) || $_SESSION['account_type'] !== 'admin') redirect('../login.php');

$total_users = count(firestore_query_collection('users'));
$total_videos = count(firestore_query_collection('videos'));
$total_groups = count(firestore_query_collection('groups'));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Dadapoa</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <h1>Admin Dashboard</h1>
    <div class="stats">
        <p>Total Users: <?php echo $total_users; ?></p>
        <p>Total Videos: <?php echo $total_videos; ?></p>
        <p>Total Groups: <?php echo $total_groups; ?></p>
    </div>
    <nav>
        <a href="manage_users.php">Manage Users (Ban/Blue Tick)</a>
        <a href="manage_videos.php">Manage Videos</a>
        <a href="manage_groups.php">Manage Groups</a>
        <a href="approve_providers.php">Approve Providers</a>
    </nav>
    <p><a href="../logout.php">Logout</a></p>
</div>
</body>
</html>
