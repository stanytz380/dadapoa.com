<?php
require_once '../includes/config.php';
require_once '../includes/firebase_rest.php';
require_once '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['uid']) || $_SESSION['account_type'] !== 'admin') {
    redirect('../login.php');
}

// Helper: Update user fields (approve, ban, blue tick)
function updateUserField($uid, $field, $value) {
    return firebase_update_user($uid, [$field => ['booleanValue' => $value]]);
}

// Handle POST requests (approve, ban, blue tick, add video, add group)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Approve provider
    if (isset($_POST['approve_provider'])) {
        $uid = $_POST['uid'];
        updateUserField($uid, 'approved', true);
        $_SESSION['flash'] = "Provider approved successfully.";
    }
    // Ban/Unban user
    elseif (isset($_POST['ban_user'])) {
        $uid = $_POST['uid'];
        updateUserField($uid, 'banned', true);
        $_SESSION['flash'] = "User banned.";
    }
    elseif (isset($_POST['unban_user'])) {
        $uid = $_POST['uid'];
        updateUserField($uid, 'banned', false);
        $_SESSION['flash'] = "User unbanned.";
    }
    // Blue tick
    elseif (isset($_POST['bluetick_user'])) {
        $uid = $_POST['uid'];
        updateUserField($uid, 'blue_tick', true);
        $_SESSION['flash'] = "Blue tick added.";
    }
    elseif (isset($_POST['remove_bluetick_user'])) {
        $uid = $_POST['uid'];
        updateUserField($uid, 'blue_tick', false);
        $_SESSION['flash'] = "Blue tick removed.";
    }
    // Add video
    elseif (isset($_POST['add_video'])) {
        $title = $_POST['title'];
        $price = (int)$_POST['price'];
        $video_url = $_POST['video_url'];
        $video_id = uniqid();
        $data = ['fields' => [
            'title' => ['stringValue' => $title],
            'price' => ['integerValue' => $price],
            'video_url' => ['stringValue' => $video_url],
            'uploaded_by' => ['stringValue' => $_SESSION['uid']],
            'created_at' => ['timestampValue' => date('c')]
        ]];
        $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/videos/$video_id?key=" . FIREBASE_API_KEY;
        firebase_post($url, $data);
        $_SESSION['flash'] = "Video added successfully.";
    }
    // Delete video
    elseif (isset($_POST['delete_video'])) {
        $video_id = $_POST['video_id'];
        $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/videos/$video_id?key=" . FIREBASE_API_KEY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        $_SESSION['flash'] = "Video deleted.";
    }
    // Add group
    elseif (isset($_POST['add_group'])) {
        $name = $_POST['name'];
        $logo = $_POST['logo'];
        $total_members = (int)$_POST['total_members'];
        $join_fee = (int)$_POST['join_fee'];
        $whatsapp_link = $_POST['whatsapp_link'];
        $group_id = uniqid();
        $data = ['fields' => [
            'name' => ['stringValue' => $name],
            'logo' => ['stringValue' => $logo],
            'total_members' => ['integerValue' => $total_members],
            'join_fee' => ['integerValue' => $join_fee],
            'whatsapp_link' => ['stringValue' => $whatsapp_link],
            'created_at' => ['timestampValue' => date('c')]
        ]];
        $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/groups/$group_id?key=" . FIREBASE_API_KEY;
        firebase_post($url, $data);
        $_SESSION['flash'] = "Group added successfully.";
    }
    // Delete group
    elseif (isset($_POST['delete_group'])) {
        $group_id = $_POST['group_id'];
        $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/groups/$group_id?key=" . FIREBASE_API_KEY;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        $_SESSION['flash'] = "Group deleted.";
    }
    header('Location: index.php');
    exit;
}

// Fetch data for display
// All users
$users = [];
$providers_pending = [];
$total_users = 0;
$total_providers = 0;
$total_logins = 0;
$queryResult = firestore_query_collection('users');
if (is_array($queryResult)) {
    foreach ($queryResult as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            $uid = basename($doc['document']['name']);
            $account_type = $fields['account_type']['stringValue'] ?? '';
            $approved = $fields['approved']['booleanValue'] ?? false;
            $banned = $fields['banned']['booleanValue'] ?? false;
            $blue_tick = $fields['blue_tick']['booleanValue'] ?? false;
            $nickname = $fields['nickname']['stringValue'] ?? '';
            $email = $fields['email']['stringValue'] ?? '';
            $login_count = $fields['login_count']['integerValue'] ?? 0;
            $total_logins += $login_count;
            $users[] = [
                'uid' => $uid,
                'nickname' => $nickname,
                'email' => $email,
                'account_type' => $account_type,
                'approved' => $approved,
                'banned' => $banned,
                'blue_tick' => $blue_tick,
                'login_count' => $login_count
            ];
            if ($account_type == 'service' && !$approved) {
                $providers_pending[] = ['uid' => $uid, 'nickname' => $nickname, 'email' => $email];
            }
            if ($account_type == 'service') $total_providers++;
            $total_users++;
        }
    }
}

// Videos
$videos = [];
$queryResult_v = firestore_query_collection('videos');
if (is_array($queryResult_v)) {
    foreach ($queryResult_v as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            $videos[] = [
                'id' => basename($doc['document']['name']),
                'title' => $fields['title']['stringValue'] ?? '',
                'price' => $fields['price']['integerValue'] ?? 0,
                'url' => $fields['video_url']['stringValue'] ?? ''
            ];
        }
    }
}

// Groups
$groups = [];
$queryResult_g = firestore_query_collection('groups');
if (is_array($queryResult_g)) {
    foreach ($queryResult_g as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            $groups[] = [
                'id' => basename($doc['document']['name']),
                'name' => $fields['name']['stringValue'] ?? '',
                'logo' => $fields['logo']['stringValue'] ?? '',
                'total_members' => $fields['total_members']['integerValue'] ?? 0,
                'join_fee' => $fields['join_fee']['integerValue'] ?? 0,
                'whatsapp_link' => $fields['whatsapp_link']['stringValue'] ?? ''
            ];
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dadapoa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Roboto, sans-serif; }
        body { background: #f4f7fc; }
        .wrapper { max-width: 1400px; margin: 20px auto; padding: 20px; }
        .admin-header { background: linear-gradient(135deg, #c31432, #240b36); color: white; padding: 20px; border-radius: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: white; padding: 20px; border-radius: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-align: center; }
        .stat-card i { font-size: 36px; color: #c31432; }
        .stat-card h3 { margin: 10px 0; font-size: 28px; }
        .section { background: white; border-radius: 20px; margin-bottom: 40px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .section h2 { background: #2c3e50; color: white; padding: 15px 20px; margin: 0; }
        .section-content { padding: 20px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn-sm { padding: 6px 12px; border-radius: 30px; font-size: 12px; border: none; cursor: pointer; margin: 2px; }
        .btn-approve { background: #4caf50; color: white; }
        .btn-ban { background: #f44336; color: white; }
        .btn-unban { background: #ff9800; color: white; }
        .btn-bluetick { background: #2196f3; color: white; }
        .btn-delete { background: #9e9e9e; color: white; }
        .form-inline { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 20px; background: #f8f9fa; padding: 15px; border-radius: 15px; }
        .form-inline input, .form-inline button { padding: 8px 12px; border-radius: 30px; border: 1px solid #ccc; }
        .flash { background: #d4edda; color: #155724; padding: 10px; border-radius: 10px; margin-bottom: 20px; }
        footer { text-align: center; margin-top: 30px; color: #666; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="admin-header">
        <h1><i class="fas fa-crown"></i> Admin Dashboard - Dadapoa</h1>
        <a href="../logout.php" style="color: white;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash"><?php echo htmlspecialchars($flash); ?></div>
    <?php endif; ?>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card"><i class="fas fa-users"></i><h3><?php echo $total_users; ?></h3><p>Total Users</p></div>
        <div class="stat-card"><i class="fas fa-concierge-bell"></i><h3><?php echo $total_providers; ?></h3><p>Service Providers</p></div>
        <div class="stat-card"><i class="fas fa-video"></i><h3><?php echo count($videos); ?></h3><p>Videos</p></div>
        <div class="stat-card"><i class="fas fa-users"></i><h3><?php echo count($groups); ?></h3><p>Groups</p></div>
        <div class="stat-card"><i class="fas fa-chart-line"></i><h3><?php echo $total_logins; ?></h3><p>Total Logins</p></div>
    </div>

    <!-- 1. Approve Providers -->
    <div class="section">
        <h2><i class="fas fa-user-check"></i> Approve Service Providers</h2>
        <div class="section-content">
            <?php if (count($providers_pending) == 0): ?>
                <p>No pending approvals.</p>
            <?php else: ?>
                <table>
                    <tr><th>Nickname</th><th>Email</th><th>Action</th></tr>
                    <?php foreach ($providers_pending as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['nickname']); ?></td>
                        <td><?php echo htmlspecialchars($p['email']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="uid" value="<?php echo $p['uid']; ?>">
                                <button type="submit" name="approve_provider" class="btn-sm btn-approve"><i class="fas fa-check"></i> Approve</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Manage Users (Ban, Blue Tick) -->
    <div class="section">
        <h2><i class="fas fa-user-cog"></i> Manage Users</h2>
        <div class="section-content">
            <table>
                <tr><th>Nickname</th><th>Email</th><th>Type</th><th>Logins</th><th>Blue Tick</th><th>Banned</th><th>Actions</th></tr>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?php echo htmlspecialchars($u['nickname']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo $u['account_type']; ?></td>
                    <td><?php echo $u['login_count']; ?></td>
                    <td><?php echo $u['blue_tick'] ? 'Yes' : 'No'; ?></td>
                    <td><?php echo $u['banned'] ? 'Yes' : 'No'; ?></td>
                    <td>
                        <?php if (!$u['blue_tick']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="uid" value="<?php echo $u['uid']; ?>">
                            <button type="submit" name="bluetick_user" class="btn-sm btn-bluetick"><i class="fas fa-certificate"></i> Blue Tick</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="uid" value="<?php echo $u['uid']; ?>">
                            <button type="submit" name="remove_bluetick_user" class="btn-sm btn-delete"><i class="fas fa-times"></i> Remove Tick</button>
                        </form>
                        <?php endif; ?>
                        <?php if (!$u['banned']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="uid" value="<?php echo $u['uid']; ?>">
                            <button type="submit" name="ban_user" class="btn-sm btn-ban"><i class="fas fa-ban"></i> Ban</button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="uid" value="<?php echo $u['uid']; ?>">
                            <button type="submit" name="unban_user" class="btn-sm btn-unban"><i class="fas fa-check-circle"></i> Unban</button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- 3. Manage Videos -->
    <div class="section">
        <h2><i class="fas fa-video"></i> Manage Videos</h2>
        <div class="section-content">
            <form method="POST" class="form-inline">
                <input type="text" name="title" placeholder="Video Title" required>
                <input type="number" name="price" placeholder="Price (0=Free)" value="0" required>
                <input type="url" name="video_url" placeholder="Video URL (YouTube/Direct)" required>
                <button type="submit" name="add_video" class="btn-sm btn-approve"><i class="fas fa-plus"></i> Add Video</button>
            </form>
            <table>
                <tr><th>Title</th><th>Price</th><th>URL</th><th>Action</th></tr>
                <?php foreach ($videos as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['title']); ?></td>
                    <td><?php echo $v['price'] == 0 ? 'Free' : 'TZS '.number_format($v['price']); ?></td>
                    <td><a href="<?php echo $v['url']; ?>" target="_blank">Link</a></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="video_id" value="<?php echo $v['id']; ?>">
                            <button type="submit" name="delete_video" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- 4. Manage Groups -->
    <div class="section">
        <h2><i class="fas fa-users"></i> Manage Groups</h2>
        <div class="section-content">
            <form method="POST" class="form-inline">
                <input type="text" name="name" placeholder="Group Name" required>
                <input type="url" name="logo" placeholder="Logo URL" required>
                <input type="number" name="total_members" placeholder="Total Members" required>
                <input type="number" name="join_fee" placeholder="Join Fee (0=Free)" value="0" required>
                <input type="url" name="whatsapp_link" placeholder="WhatsApp Invite Link" required>
                <button type="submit" name="add_group" class="btn-sm btn-approve"><i class="fas fa-plus"></i> Add Group</button>
            </form>
            <table>
                <tr><th>Name</th><th>Logo</th><th>Members</th><th>Fee</th><th>Action</th></tr>
                <?php foreach ($groups as $g): ?>
                <tr>
                    <td><?php echo htmlspecialchars($g['name']); ?></td>
                    <td><img src="<?php echo $g['logo']; ?>" width="50"></td>
                    <td><?php echo $g['total_members']; ?></td>
                    <td><?php echo $g['join_fee'] == 0 ? 'Free' : 'TZS '.number_format($g['join_fee']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="group_id" value="<?php echo $g['id']; ?>">
                            <button type="submit" name="delete_group" class="btn-sm btn-delete"><i class="fas fa-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <footer>
        <p>© 2026 STANYTZ | Admin Panel</p>
    </footer>
</div>
</body>
</html>
