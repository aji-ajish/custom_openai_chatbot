<?php
function block_custom_openai_chatbot_global_db_vars() {
    return array(
        'block_custom_openai_chatbot/apikey',
        'block_custom_openai_chatbot/apiurl',
        'block_custom_openai_chatbot/model',
        'block_custom_openai_chatbot/max_tokens',
        'block_custom_openai_chatbot/temperature'
    );
}

function block_custom_openai_chatbot_has_config() {
    return true; // This tells Moodle that this block has a settings page.
}
