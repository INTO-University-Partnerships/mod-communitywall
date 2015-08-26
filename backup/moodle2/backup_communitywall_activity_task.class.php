<?php

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/mod/communitywall/backup/moodle2/backup_communitywall_stepslib.php';

class backup_communitywall_activity_task extends backup_activity_task {

    protected function define_my_settings() {
        // empty
    }

    protected function define_my_steps() {
        $this->add_step(new backup_communitywall_activity_structure_step('communitywall_structure', 'communitywall.xml'));
    }

    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        // link to the list of pages
        $search="/(".$base."\/mod\/communitywall\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@COMMUNITYWALLINDEX*$2@$', $content);

        // link to page view by moduleid
        $search="/(".$base."\/mod\/communitywall\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@COMMUNITYWALLVIEWBYID*$2@$', $content);

        return $content;
    }

}
