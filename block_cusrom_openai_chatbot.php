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
        $this->content->text = '<div id="openai-chatbot-container">
            <input type="text" id="chat-input" placeholder="Ask me something...">
            <button id="send-btn">Send</button>
            <div id="chat-output"></div>
        </div>';
        $this->content->footer = '';

        // Include JavaScript
        $this->page->requires->js(new moodle_url($CFG->wwwroot . '/blocks/cusrom_openai_chatbot/chat.js'));

        return $this->content;
    }
}

