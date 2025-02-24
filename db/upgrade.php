<?php

function xmldb_block_custom_openai_chatbot_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2021051700) {

        // Define table chatbot_history to be created.
        $table = new xmldb_table('chatbot_history');

        // Adding fields to table chatbot_history.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('response_type', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table chatbot_history.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Adding indexes to table chatbot_history.
        $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
        $table->add_index('user-time', XMLDB_INDEX_NOTUNIQUE, ['userid', 'timecreated']);

        // Conditionally launch create table for chatbot_history.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Custom_openai_chatbot savepoint reached.
        upgrade_block_savepoint(true, 2021051700, 'custom_openai_chatbot');
    }

    return true;
}
