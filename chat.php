<?php
require_once('../../config.php');
require_login();
header('Content-Type: application/json');

// Rate Limiting (Prevent spam)
$userid = $USER->id;
$cache = cache::make('block_custom_openai_chatbot', 'ratelimit');
$last_request_time = $cache->get("last_request_$userid");

if ($last_request_time && (time() - $last_request_time) < 5) {
    echo json_encode(['error' => 'You are sending messages too quickly. Please wait a few seconds before trying again.']);
    exit;
}
$cache->set("last_request_$userid", time());

// Get API settings from the admin panel
$apikey = get_config('block_custom_openai_chatbot', 'apikey');
$apiurl = get_config('block_custom_openai_chatbot', 'process_query_api'); // Process Query API URL

if (!$apikey || !$apiurl) {
    echo json_encode(['error' => 'API settings are missing. Please configure them in Site Administration.']);
    exit;
}

// Get user input
$data = json_decode(file_get_contents("php://input"), true);

$message = $data['message'] ?? '';
$course_name = $data['courseName'] ?? '';
$course_id = $data['courseId'] ?? '';
$user_id = $data['userId'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'Message cannot be empty.']);
    exit;
}

// Static responses
$static_responses = [
    "hi" => "Hello! How can I assist you today?",
    "hello" => "Hi there! What can I do for you?",
    "hey" => "Hey! How's it going?",
    "how are you" => "I'm just a bot, but I'm doing great! How about you?",
    "good morning" => "Good morning! Hope you have a great day ahead. ☀️",
    "good afternoon" => "Good afternoon! How's your day going?",
    "good evening" => "Good evening! How can I assist you?",
    "good night" => "Good night! Sleep well and take care.",
    "thank you" => "You're welcome!",
    "thanks" => "No problem! Let me know if you need more help.",
    "bye" => "Goodbye! Have a great day!",
    "goodbye" => "See you later! Stay safe.",
    "who are you" => "I'm a chatbot built into Moodle. How can I assist you?",
    "what is your name" => "I'm your friendly AI chatbot!",
    "what do you do" => "I help answer questions and provide information.",
    "who created you" => "I was developed as a Moodle plugin using OpenAI.",
    "what is AI" => "AI stands for Artificial Intelligence. It allows machines to learn and make decisions like humans.",
    "tell me a joke" => "Why don’t programmers like nature? It has too many bugs!",
    "tell me another joke" => "Why did the chatbot go to school? To improve its response time!",
    "what can you do" => "I can answer questions, provide information, and chat with you!",
    "how old are you" => "I exist in the digital world, so I don't age!",
    "are you human" => "Nope! I'm just a chatbot. But I'm here to help!",
    "do you have feelings" => "I don't have real emotions, but I can understand yours!",
    "what's the weather like" => "I can't check the weather, but you can visit a weather website for the latest updates! 🌦️",
    "how do I use this chatbot" => "Simply type your question, and I'll do my best to help!",
    "how do I reset my password" => "You can reset your password from your Moodle profile settings.",
    "how do I contact support" => "You can reach out to the Moodle admin or support team for assistance.",
    "can you help me with my assignment" => "Of course! What do you need help with?",
    "how do I enroll in a course" => "You can enroll in a course from the Moodle course catalog or contact your admin.",
    "what is Moodle" => "Moodle is an open-source learning platform designed for online education.",
    "what is your purpose" => "I'm here to assist you with information and answer your questions!"
];

// Normalize input function
function normalize_text($text)
{
    $text = strtolower($text); // Convert to lowercase
    $text = preg_replace("/[^\w\s]/", "", $text); // Remove punctuation
    return trim($text); // Remove extra spaces
}

// Normalize input
$normalized_message = normalize_text($message);

// Normalize static response keys
$normalized_responses = [];
foreach ($static_responses as $key => $response) {
    $normalized_responses[normalize_text($key)] = $response;
}

// Check if input matches a static response
if (isset($normalized_responses[$normalized_message])) {
    $static_response = $normalized_responses[$normalized_message];

    // Store static response in the database via the API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'user_query' => $message,
        'user_id' => $user_id,
        'course_id' => $course_id,
        'course_name' => $course_name,
        'response' => $static_response, // Pass the static response to the API
        'is_static' => true // Mark it as a static response
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apikey",
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    // Return static response
    echo json_encode(['response' => $static_response]);
    exit;
}



// Call Process Query API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'user_query' => $message,
    'user_id' => $user_id,
    'course_id' => $course_id,
    'course_name' => $course_name
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apikey",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// Handle API errors
if ($response === false) {
    echo json_encode(['error' => 'Failed to connect to AI API.', 'curl_error' => $error]);
    exit;
}

$result = json_decode($response, true);
$reply = $result['response'] ?? 'No response from AI.';

// Return API response
echo json_encode(['response' => $reply]);
