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

    public function applicable_formats() {
        return [
            'all' => false,
            'site' => true,
            'course-view' => true, 
            'mod' => true, // Ensure this is set to allow blocks in module pages
        ];
    }
    

    public function get_content() {
        global $COURSE, $USER, $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $course_name = isset($COURSE->fullname) ? $COURSE->shortname : 'General';
        $course_id = isset($COURSE->fullname) ? $COURSE->id : 'General';
        $user_id = $USER->id;

        // Retrieve previous chat history for the user and course

        $sql = "SELECT * FROM {chatbot_history} 
        WHERE userid = :userid AND courseid = :courseid 
        ORDER BY timecreated ASC";

        $params = ['userid' => $USER->id, 'courseid' => $COURSE->id];

        $history = $DB->get_records_sql($sql, $params);

        // Display the chat history
        $history_html = '';
        foreach ($history as $record) {
            $history_html .= '<div class="chat-message user-message" style="margin-top: 5px;margin-bottom: 5px;">' . format_text($record->message, FORMAT_PLAIN) . '</div>';
            
            if (!empty($record->response)) {
                $history_html .= '<div class="chat-message bot-message">' . format_text($record->response, FORMAT_PLAIN) . '</div>';
            }
            
        }
        
        $this->content = new stdClass;

        // Chatbot UI
        $this->content->text = '
        <div id="openai-chatbot-container">
            <div id="chat-messages">' . $history_html . '</div>
            <div id="chat-input-container">
                <input type="hidden" id="course-name" value="' . htmlspecialchars($course_name, ENT_QUOTES) . '">
                <input type="hidden" id="course-id" value="' . htmlspecialchars($course_id, ENT_QUOTES) . '">
                <input type="hidden" id="user-id" value="' . htmlspecialchars($user_id, ENT_QUOTES) . '">
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
