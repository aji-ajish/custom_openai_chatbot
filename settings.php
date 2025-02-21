<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // Ensures the settings are only added once
    $settings = new admin_settingpage(
        'block_cusrom_openai_chatbot_settings',
        get_string('pluginname', 'block_cusrom_openai_chatbot')
    );

    // OpenAI API Key
    $settings->add(new admin_setting_configtext(
        'block_cusrom_openai_chatbot/apikey',
        'OpenAI API Key',
        'Enter your OpenAI API Key.',
        '',
        PARAM_TEXT
    ));

    // OpenAI API URL
    $settings->add(new admin_setting_configtext(
        'block_cusrom_openai_chatbot/apiurl',
        'OpenAI API Endpoint',
        'Enter the OpenAI API URL.',
        'https://api.openai.com/v1/chat/completions',
        PARAM_URL
    ));

    // OpenAI Model
    $settings->add(new admin_setting_configtext(
        'block_cusrom_openai_chatbot/model',
        'OpenAI Model',
        'Specify the OpenAI model (e.g., gpt-3.5-turbo)',
        'gpt-3.5-turbo',
        PARAM_TEXT
    ));

    // Max Tokens
    $settings->add(new admin_setting_configtext(
        'block_cusrom_openai_chatbot/max_tokens',
        'Max Tokens',
        'Set the maximum number of tokens per response.',
        '100',
        PARAM_INT
    ));

    // Temperature
    $settings->add(new admin_setting_configtext(
        'block_cusrom_openai_chatbot/temperature',
        'Temperature',
        'Set the response randomness (0 = strict, 1 = creative).',
        '0.7',
        PARAM_FLOAT
    ));

    // ✅ Ensure the settings are added only once
    if (!$ADMIN->locate('blocksettings')) {
        $ADMIN->add('root', new admin_category('blocksettings', get_string('blocksettings', 'admin')));
    }

    // if (!$ADMIN->locate('block_cusrom_openai_chatbot_settings')) {
    //     $ADMIN->add('blocksettings', $settings);
    // }
}
