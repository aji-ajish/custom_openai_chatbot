<?php
// This file is part of Moodle - http://moodle.org/
// 
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

class block_custom_openai_chatbot extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_custom_openai_chatbot');
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
        $this->page->requires->css(new moodle_url($CFG->wwwroot . '/blocks/custom_openai_chatbot/styles.css'));
        $this->page->requires->js(new moodle_url($CFG->wwwroot . '/blocks/custom_openai_chatbot/chat.js'));

        return $this->content;
    }
}
