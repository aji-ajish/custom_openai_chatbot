<?php

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
            'mod' => true,
        ];
    }

    public function get_content()
    {
        global $COURSE, $USER, $CFG, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        $course_id = isset($COURSE->id) ? $COURSE->id : 'General';
        $user_id = $USER->id;
        $course_name = isset($COURSE->fullname) ? $COURSE->shortname : 'General';

        // Fetch chat history from DRF API
        $history_html = $this->fetch_chat_history($user_id, $course_id);

        $this->content = new stdClass;
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

    private function fetch_chat_history($user_id, $course_id) {
        global $CFG;
    
        $api_url = get_config('block_custom_openai_chatbot', 'chat_history_api');
        $api_key = get_config('block_custom_openai_chatbot', 'api_key');
    
        $params = [
            'user_id' => $user_id,
            'course_id' => $course_id
        ];
    
        $headers = [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ];
    
        $response = $this->make_api_request($api_url, $params, $headers);
    
        // Debugging: Log API response
        error_log("Chat History API Raw Response: " . print_r($response, true));
    
        if (!is_array($response)) {
            error_log("Invalid API response. Expected array, got " . gettype($response));
            return '<p>Error retrieving chat history.</p>';
        }
    
        if (empty($response)) {
            return '<p>No chat history found.</p>';
        }
    
        $history_html = '';
        foreach ($response as $record) {
            if (!is_array($record)) {  // Ensure $record is an array
                error_log("Invalid record format: " . print_r($record, true));
                continue;
            }
    
            $history_html .= '<div class="chat-message user-message">'
                . format_text($record['user_query'], FORMAT_PLAIN)
                . '<br><span class="chat-token">Req Token: ' . htmlspecialchars($record['req_token'], ENT_QUOTES) . '</span></div>';
    
            if (!empty($record['response'])) {
                $history_html .= '<div class="chat-message bot-message">'
                    . format_text($record['response'], FORMAT_PLAIN)
                    . '<br><span class="chat-token">Res Token: ' . htmlspecialchars($record['res_token'], ENT_QUOTES) . '</span></div>';
            }
        }
    
        return $history_html;
    }
    


    private function make_api_request($url, $params, $headers) {
        $query_string = http_build_query($params, '', '&');
        $full_url = rtrim($url, '?') . '?' . $query_string;
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        if (curl_errno($ch)) {
            error_log('CURL Error: ' . curl_error($ch));
        }
    
        curl_close($ch);
    
        // Debugging
        error_log("API URL: " . $full_url);
        error_log("API Response Code: " . $http_code);
        error_log("Raw API Response: " . print_r($response, true));
    
        // Check if response is valid JSON
        $data = json_decode($response, true);
    
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON Decode Error: " . json_last_error_msg());
            return []; // Return empty array if JSON is invalid
        }
    
        if (!is_array($data)) {
            error_log("Unexpected API response format. Expected array, got " . gettype($data));
            return [];
        }
    
        return $data;
    }
    
}
