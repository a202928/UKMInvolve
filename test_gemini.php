<?php
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . 'YOUR_API_KEY_HERE';
$ch = curl_init($url);
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false]);
$res = curl_exec($ch);
$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'gemini') !== false) {
            echo $m['name'] . "\n";
        }
    }
} else {
    print_r($data);
}
