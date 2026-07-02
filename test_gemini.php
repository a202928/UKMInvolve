<?php
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' . 'YOUR_API_KEY_HERE';
$payload = json_encode(['contents' => [['parts' => [['text' => 'Hello']]]]]);
$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true, 
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json']
]);
$res = curl_exec($ch);
if ($res === false) {
    echo "CURL ERROR: " . curl_error($ch);
} else {
    echo $res;
}
