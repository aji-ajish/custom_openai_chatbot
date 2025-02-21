<?php
// File: blocks/custom_openai_chatbot/db/caches.php
defined('MOODLE_INTERNAL') || die();

$definitions = [
    'ratelimit' => [
        'mode' => cache_store::MODE_APPLICATION,
    ],
];
