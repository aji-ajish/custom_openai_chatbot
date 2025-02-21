<?php
class block_cusrom_openai_chatbot extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_cusrom_openai_chatbot');
    }

    public function has_config() {
        return true; // Enable settings page
    }
    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        global $CFG;
        $this->content = new stdClass;

        // Chatbot UI
        $this->content->text = '
        <div id="openai-chatbot-container">
            <div id="chat-messages"></div>
            <div id="chat-input-container">
                <input type="text" id="chat-input" placeholder="Type a message...">
                <button id="send-btn">➤</button>
            </div>
        </div>';

        $this->content->footer = '';

        // Include CSS and JavaScript
        $this->page->requires->css(new moodle_url($CFG->wwwroot . '/blocks/cusrom_openai_chatbot/styles.css'));
        $this->page->requires->js(new moodle_url($CFG->wwwroot . '/blocks/cusrom_openai_chatbot/chat.js'));

        return $this->content;
    }
}
