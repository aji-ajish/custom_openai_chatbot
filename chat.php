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

// Get settings from the admin panel
$apikey = get_config('block_custom_openai_chatbot', 'apikey');
$apiurl = get_config('block_custom_openai_chatbot', 'apiurl');
$model = get_config('block_custom_openai_chatbot', 'model');
$max_tokens = get_config('block_custom_openai_chatbot', 'max_tokens');
$temperature = get_config('block_custom_openai_chatbot', 'temperature');

if (!$apikey || !$apiurl || !$model) {
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

$modified_message = "This question is about '$course_name.' Please answer accordingly: $message.";


// Static Responses (Avoid API Calls for Common Questions)
$static_responses = [
    "hi" => "Hello! How can I assist you today? 😊",
    "hello" => "Hi there! What can I do for you?",
    "hey" => "Hey! How's it going?",
    "how are you" => "I'm just a bot, but I'm doing great! How about you?",
    "good morning" => "Good morning! Hope you have a great day ahead. ☀️",
    "good afternoon" => "Good afternoon! How's your day going?",
    "good evening" => "Good evening! How can I assist you?",
    "good night" => "Good night! Sleep well and take care. 🌙",
    "thank you" => "You're welcome! 😊",
    "thanks" => "No problem! Let me know if you need more help.",
    "bye" => "Goodbye! Have a great day! 👋",
    "goodbye" => "See you later! Stay safe. 😃",
    "who are you" => "I'm a chatbot built into Moodle. How can I assist you?",
    "what is your name" => "I'm your friendly AI chatbot!",
    "what do you do" => "I help answer questions and provide information.",
    "who created you" => "I was developed as a Moodle plugin using OpenAI. 😊",
    "what is AI" => "AI stands for Artificial Intelligence. It allows machines to learn and make decisions like humans.",
    "tell me a joke" => "Why don’t programmers like nature? It has too many bugs! 😂",
    "tell me another joke" => "Why did the chatbot go to school? To improve its response time! 😆",
    "what can you do" => "I can answer questions, provide information, and chat with you!",
    "how old are you" => "I exist in the digital world, so I don't age! 😄",
    "are you human" => "Nope! I'm just a chatbot. But I'm here to help!",
    "do you have feelings" => "I don't have real emotions, but I can understand yours! 😊",
    "what's the weather like" => "I can't check the weather, but you can visit a weather website for the latest updates! 🌦️",
    "how do I use this chatbot" => "Simply type your question, and I'll do my best to help!",
    "how do I reset my password" => "You can reset your password from your Moodle profile settings.",
    "how do I contact support" => "You can reach out to the Moodle admin or support team for assistance.",
    "can you help me with my assignment" => "Of course! What do you need help with?",
    "how do I enroll in a course" => "You can enroll in a course from the Moodle course catalog or contact your admin.",
    "what is Moodle" => "Moodle is an open-source learning platform designed for online education.",
    "what is your purpose" => "I'm here to assist you with information and answer your questions!"
];

// Normalize input function (Already in your code)
function normalize_text($text) {
    $text = strtolower($text); // Convert to lowercase
    $text = preg_replace("/[^\w\s]/", "", $text); // Remove punctuation
    return trim($text); // Remove extra spaces
}

// Normalize input
$normalized_message = normalize_text($message);

// Normalize static response keys (Ensure they match)
$normalized_responses = [];
foreach ($static_responses as $key => $response) {
    $normalized_responses[normalize_text($key)] = $response;
}

// Now, check if normalized input exists in normalized responses
if (isset($normalized_responses[$normalized_message])) {
    $response = $normalized_responses[$normalized_message];
    // Save the static response in the database
    save_chat_history($user_id, $course_id, $message, $response, 'static');
    echo json_encode(['response' => $response]);
    exit;
}


// Call OpenAI API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiurl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => $model,
    'messages' => [['role' => 'user', 'content' => $modified_message]],
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

// Error Handling
if ($httpcode !== 200) {
    $error_message = 'Failed to connect to OpenAI.';

    if ($httpcode === 401) {
        $error_message = 'Invalid API key. Please check your settings.';
    } elseif ($httpcode === 429) {
        $error_message = 'Too many requests. OpenAI API rate limit exceeded.';
    } elseif ($httpcode === 500) {
        $error_message = 'OpenAI server error. Please try again later.';
    }

    // Log the error for administrators
    debugging("OpenAI API Error (HTTP $httpcode): $error | Response: $response", DEBUG_DEVELOPER);

    // Send a generic error message to the user
    echo json_encode([
        'error' => $error_message,
        'http_code' => $httpcode,
        'curl_error' => $error,
        'api_response' => $response
    ]);
    exit;
}



// === Get Response from OpenAI === //
$result = json_decode($response, true);
$reply = $result['choices'][0]['message']['content'] ?? 'No response from OpenAI.';

// Check if the response is cut off (max tokens reached)
if (strlen($reply) >= ($max_tokens - 50)) { 
    // If the response is close to max_tokens, ask OpenAI to continue
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiurl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $message],
            ['role' => 'assistant', 'content' => $reply], // Provide previous response for context
            ['role' => 'user', 'content' => "Continue the response."]
        ],
        'max_tokens' => (int)$max_tokens,
        'temperature' => (float)$temperature
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apikey",
        "Content-Type: application/json"
    ]);

    $followup_response = curl_exec($ch);
    curl_close($ch);

    $followup_result = json_decode($followup_response, true);
    $followup_reply = $followup_result['choices'][0]['message']['content'] ?? '';

    $reply .= " " . $followup_reply; // Append follow-up response
}

// Save the API response in the database
save_chat_history($user_id, $course_id, $message, $reply, 'api');
// Return final response
echo json_encode(['response' => $reply]);
exit;

function save_chat_history($userid, $courseid, $message, $response, $response_type) {
    global $DB;

    // Prepare the record object
    $record = new stdClass();
    $record->userid = $userid;
    $record->courseid = $courseid;
    $record->message = $message;
    $record->response = $response;
    $record->response_type = $response_type;

    // Assign the current Unix timestamp to the timecreated field
    $record->timecreated = time();  // This stores the current Unix timestamp

    try {
        // Insert the record into the chatbot_history table
        $DB->insert_record('chatbot_history', $record);
    } catch (Exception $e) {
        // Handle the error and debug it
        debugging("Error: " . $e->getMessage(), DEBUG_DEVELOPER);
        throw $e;
    }
}



