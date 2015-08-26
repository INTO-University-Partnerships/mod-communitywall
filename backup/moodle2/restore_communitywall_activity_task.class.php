<?php

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/mod/communitywall/backup/moodle2/restore_communitywall_stepslib.php';

class restore_communitywall_activity_task extends restore_activity_task {

    protected function define_my_settings() {
        // empty
    }

    protected function define_my_steps() {
        $this->add_step(new restore_communitywall_activity_structure_step('communitywall_structure', 'communitywall.xml'));
    }

    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('communitywall', array('header', 'footer'), 'communitywall');

        return $contents;
    }

    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('COMMUNITYWALLVIEWBYID', '/mod/communitywall/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('COMMUNITYWALLINDEX', '/mod/communitywall/index.php?id=$1', 'course');

        return $rules;
    }

}
