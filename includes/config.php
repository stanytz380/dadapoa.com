<?php
// config.php - Weka hapa API keys zako kutoka Firebase na Peterpay

// Firebase REST API Key (kutoka Firebase Console -> Project Settings -> Web API Key)
define('FIREBASE_API_KEY', 'AIzaSy...YourActualKey...');

// Firebase Project ID (kutoka Firebase Console)
define('FIREBASE_PROJECT_ID', 'dadapoa-12345');

// Firebase Auth Domain
define('FIREBASE_AUTH_DOMAIN', 'dadapoa-12345.firebaseapp.com');

// Peterpay Credentials (jaza kutoka kwa Peterpay)
define('PETERPAY_MERCHANT_ID', 'MERCHANT123');
define('PETERPAY_API_KEY', 'PP-API-KEY-xxxx');
define('PETERPAY_CALLBACK_URL', 'https://yourdomain.com/peterpay_callback.php');

// Admin WhatsApp Number (kwa service provider kuomba approval)
define('ADMIN_WHATSAPP', '255700000000');

// Root URL (badilisha na dom yako)
define('BASE_URL', 'https://yourdomain.com/');

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
