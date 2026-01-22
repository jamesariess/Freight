<?php
$api_key = "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";

$ch = curl_init("https://api.openai.com/v1/chat/completions");
$data = [
    "model" => "gpt-4o-mini",
    "messages" => [["role" => "user", "content" => "Say hello!"]],
];

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $api_key",
        "Content-Type: application/json"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
]);
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>HTTP Status: $code</h3>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
?>
