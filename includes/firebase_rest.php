<?php
require_once __DIR__ . '/config.php';

function firebase_post($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

function firebase_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

function firebase_patch($url, $data) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    curl_close($ch);
    return json_decode($resp, true);
}

function firebase_create_user($email, $password) {
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:signUp?key=" . FIREBASE_API_KEY;
    $data = ['email' => $email, 'password' => $password, 'returnSecureToken' => true];
    return firebase_post($url, $data);
}

function firebase_login_user($email, $password) {
    $url = "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key=" . FIREBASE_API_KEY;
    $data = ['email' => $email, 'password' => $password, 'returnSecureToken' => true];
    return firebase_post($url, $data);
}

function firebase_save_user($uid, $data) {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/users/$uid?key=" . FIREBASE_API_KEY;
    return firebase_post($url, $data);
}

function firebase_get_user($uid) {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/users/$uid?key=" . FIREBASE_API_KEY;
    return firebase_get($url);
}

function firebase_update_user($uid, $fields) {
    $mask = [];
    foreach (array_keys($fields) as $key) {
        $mask[] = "updateMask.fieldPaths=$key";
    }
    $mask_str = implode('&', $mask);
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents/users/$uid?$mask_str&key=" . FIREBASE_API_KEY;
    $data = ['fields' => $fields];
    return firebase_patch($url, $data);
}

function firestore_query_collection($collection, $filters = []) {
    $url = "https://firestore.googleapis.com/v1/projects/" . FIREBASE_PROJECT_ID . "/databases/(default)/documents:runQuery?key=" . FIREBASE_API_KEY;
    $query = [
        'structuredQuery' => [
            'from' => [['collectionId' => $collection]],
            'limit' => 100
        ]
    ];
    // Kwa sasa tunaruka filters, unaweza kuongeza baadaye
    return firebase_post($url, $query);
}
?>
