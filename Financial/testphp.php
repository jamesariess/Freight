<?php
// groq-proxy.php - Secure backend proxy for Groq API

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Change to your domain in production
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only accept POST with JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action']) || $input['action'] !== 'groq_allocate') {
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$prompt = $input['prompt'] ?? '';
if (empty($prompt)) {
    echo json_encode(['error' => 'No prompt provided']);
    exit;
}

// === YOUR GROQ API KEY (KEEP THIS SECRET!) ===
$apiKey = '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'; // ← Replace only if needed

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'model' => 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.3,
        'max_tokens' => 1024
    ])
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Groq Error: $httpCode - $curlError - Response: $response");
    echo json_encode([
        'error' => 'AI service failed',
        'code' => $httpCode,
        'details' => $response
    ]);
} else {
    echo $response;
}
?>