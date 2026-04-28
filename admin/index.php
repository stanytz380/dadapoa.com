<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['uid']) || $_SESSION['account_type'] !== 'admin') {
    redirect('../login.php');
}

// Fetch service accounts with approved = false
$pending = [];
$queryResult = firestore_query_collection('users');
if ($queryResult) {
    foreach ($queryResult as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            if ($fields['account_type']['stringValue'] == 'service' && $fields['approved']['booleanValue'] == false) {
                $pending[] = [
                    'uid' => basename($doc['document']['name']),
                    'nickname' => $fields['nickname']['stringValue'],
                    'email' => $fields['email']['stringValue'],
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">
    <h1>Admin Dashboard</h1>
    <h2>Service Accounts Pending Approval</h2>
    <?php if (count($pending) == 0): ?>
        <p>Hakuna watoa huduma wanaosubiri kwa sasa.</p>
    <?php else: ?>
        <table border="1" cellpadding="10">
            <table>
                <th>Nickname</th><th>Email</th><th>Action</th>
            </tr>
            <?php foreach ($pending as $p): ?>
            <tr>
                </table><?php echo $p['nickname']; ?></td>
                <td><?php echo $p['email']; ?></td>
                <td>
                    <a href="approve_provider.php?uid=<?php echo $p['uid']; ?>" class="premium-btn">Approve</a>
                    <a href="ban_provider.php?uid=<?php echo $p['uid']; ?>" style="color:red;">Ban</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
    <p><a href="../logout.php">Logout</a></p>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
