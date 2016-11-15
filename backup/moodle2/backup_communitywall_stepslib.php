<?php

defined('MOODLE_INTERNAL') || die;

class backup_communitywall_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {
        $wall = new backup_nested_element('communitywall', array('id'), array(
            'name',
            'header',
            'footer',
            'closed',
            'timecreated',
            'timemodified',
            'completioncreatewall',
            'completionpostonwall'
        ));

        $wall->set_source_table('communitywall', array('id' => backup::VAR_ACTIVITYID));

        return $this->prepare_activity_structure($wall);
    }

}
