<?php
require_once('../../config.php');
require_login();
header('Content-Type: application/json');

// Get settings from the admin panel
$apikey = get_config('block_cusrom_openai_chatbot', 'apikey');
$apiurl = get_config('block_cusrom_openai_chatbot', 'apiurl');
$model = get_config('block_cusrom_openai_chatbot', 'model');
$max_tokens = get_config('block_cusrom_openai_chatbot', 'max_tokens');
$temperature = get_config('block_cusrom_openai_chatbot', 'temperature');

if (!$apikey || !$apiurl || !$model) {
    echo json_encode(['error' => 'API settings are missing.']);
    exit;
}

// Get user input
$data = json_decode(file_get_contents("php://input"), true);
$message = $data['message'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

// Call OpenAI API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => $model,
    'messages' => [['role' => 'user', 'content' => $message]],
    'max_tokens' => (int)$max_tokens,
    'temperature' => (float)$temperature
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apikey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Debugging output
if ($httpcode !== 200) {
    echo json_encode([
        'error' => 'Failed to connect to OpenAI.',
        'http_code' => $httpcode,
        'curl_error' => $error,
        'api_response' => $response
    ]);
    exit;
}

$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? 'No response from OpenAI.';

echo json_encode(['response' => $reply]);
exit;
