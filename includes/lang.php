<?php
// Hii inaweza kutumika kwa server-side translation ikiwa unahitaji
$lang = $_SESSION['lang'] ?? 'sw';
$texts = [
    'welcome' => ['en'=>'Welcome', 'sw'=>'Karibu'],
    // ...
];
function __($key) {
    global $lang, $texts;
    return $texts[$key][$lang] ?? $key;
}
?>
