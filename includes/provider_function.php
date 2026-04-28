<?php
require_once __DIR__ . '/firebase_rest.php';

function saveProviderProfile($uid, $data) {
    $fields = [];
    if (isset($data['profile_pic'])) $fields['profile_pic'] = ['stringValue' => $data['profile_pic']];
    if (isset($data['username'])) $fields['username'] = ['stringValue' => $data['username']];
    if (isset($data['categories'])) $fields['categories'] = ['arrayValue' => ['values' => array_map(fn($c) => ['stringValue' => $c], $data['categories'])]];
    if (isset($data['prices'])) $fields['prices'] = ['arrayValue' => ['values' => array_map(fn($p) => ['stringValue' => $p], $data['prices'])]];
    if (isset($data['location'])) $fields['location'] = ['stringValue' => $data['location']];
    if (isset($data['place_name'])) $fields['place_name'] = ['stringValue' => $data['place_name']];
    if (isset($data['phone'])) $fields['phone'] = ['stringValue' => $data['phone']];
    if (isset($data['whatsapp'])) $fields['whatsapp'] = ['stringValue' => $data['whatsapp']];
    if (isset($data['status'])) $fields['status'] = ['stringValue' => $data['status']];
    if (isset($data['photos'])) {
        $fields['photos'] = ['arrayValue' => ['values' => array_map(fn($url) => ['stringValue' => $url], $data['photos'])]];
    }
    return firebase_update_user($uid, $fields);
}

function getProviderProfile($uid) {
    $user = firebase_get_user($uid);
    if (!isset($user['fields'])) return null;
    $fields = $user['fields'];
    // Extract provider-specific fields
    return [
        'profile_pic' => $fields['profile_pic']['stringValue'] ?? '',
        'username' => $fields['username']['stringValue'] ?? '',
        'categories' => array_map(fn($v) => $v['stringValue'], $fields['categories']['arrayValue']['values'] ?? []),
        'prices' => array_map(fn($v) => $v['stringValue'], $fields['prices']['arrayValue']['values'] ?? []),
        'location' => $fields['location']['stringValue'] ?? '',
        'place_name' => $fields['place_name']['stringValue'] ?? '',
        'phone' => $fields['phone']['stringValue'] ?? '',
        'whatsapp' => $fields['whatsapp']['stringValue'] ?? '',
        'status' => $fields['status']['stringValue'] ?? 'Active',
        'photos' => array_map(fn($v) => $v['stringValue'], $fields['photos']['arrayValue']['values'] ?? [])
    ];
}

function getAllApprovedProviders() {
    $result = firestore_query_collection('users');
    $providers = [];
    foreach ($result as $doc) {
        if (isset($doc['document'])) {
            $fields = $doc['document']['fields'];
            if ($fields['account_type']['stringValue'] == 'service' && $fields['approved']['booleanValue'] == true && $fields['banned']['booleanValue'] == false) {
                $uid = basename($doc['document']['name']);
                $providers[] = [
                    'uid' => $uid,
                    'nickname' => $fields['nickname']['stringValue'],
                    'profile_pic' => $fields['profile_pic']['stringValue'] ?? 'assets/images/default-avatar.png',
                    'location' => $fields['location']['stringValue'] ?? 'Unknown',
                    'status' => $fields['status']['stringValue'] ?? 'Active'
                ];
            }
        }
    }
    return $providers;
}
?>
