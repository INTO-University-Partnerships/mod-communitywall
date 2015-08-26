<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../lib.php';

class mod_communitywall_lib_test extends advanced_testcase {

    /**
     * setUp
     */
    protected function setUp() {
        $this->resetAfterTest();
    }

    /**
     * @global moodle_database $DB
     */
    public function test_communitywall_delete_instance() {
        global $DB;
        $times = array(
            mktime(9, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array(1, $module->id, 2, 'Wall 001', $times[0], $times[0]),
                array(2, $module->id, 2, 'Wall 002', $times[0], $times[0]),
            ),
            'communitywall_note' => array(
                array('wallid', 'userid', 'timecreated', 'timemodified'),
                array(1, 2, $times[0], $times[0]),
                array(1, 2, $times[0], $times[0]),
                array(1, 2, $times[0], $times[0]),
                array(2, 2, $times[0], $times[0]),
                array(2, 2, $times[0], $times[0]),
            ),
        )));
        $this->assertEquals(1, $DB->count_records('communitywall_wall', array('id' => 1)));
        $this->assertEquals(3, $DB->count_records('communitywall_note', array('wallid' => 1)));
        $this->assertEquals(1, $DB->count_records('communitywall_wall', array('id' => 2)));
        $this->assertEquals(2, $DB->count_records('communitywall_note', array('wallid' => 2)));
        communitywall_delete_instance($module->id);
        $this->assertFalse($DB->record_exists('communitywall_wall', array('id' => 1)));
        $this->assertFalse($DB->record_exists('communitywall_note', array('wallid' => 1)));
        $this->assertFalse($DB->record_exists('communitywall_wall', array('id' => 2)));
        $this->assertFalse($DB->record_exists('communitywall_note', array('wallid' => 2)));
        $this->assertFalse($DB->record_exists('communitywall', array('id' => $module->id)));
    }

    /**
     * tests the features that communitywall supports
     */
    public function test_communitywall_supports() {
        $features = array(
            FEATURE_COMPLETION_TRACKS_VIEWS,
            FEATURE_BACKUP_MOODLE2,
            FEATURE_GROUPS,
        );
        foreach ($features as $feature) {
            $this->assertTrue(plugin_supports('mod', 'communitywall', $feature));
        }
    }

    /**
     * tests the features that communitywall does not support
     */
    public function test_communitywall_not_supports() {
        $features = array(
            FEATURE_GRADE_HAS_GRADE,
            FEATURE_GRADE_OUTCOMES,
            FEATURE_ADVANCED_GRADING,
            FEATURE_CONTROLS_GRADE_VISIBILITY,
            FEATURE_PLAGIARISM,
            FEATURE_COMPLETION_HAS_RULES,
            FEATURE_NO_VIEW_LINK,
            FEATURE_IDNUMBER,
            FEATURE_GROUPINGS,
            FEATURE_MOD_ARCHETYPE,
            FEATURE_MOD_INTRO,
            FEATURE_MODEDIT_DEFAULT_COMPLETION,
            FEATURE_COMMENT,
            FEATURE_RATE,
            FEATURE_SHOW_DESCRIPTION,
        );
        foreach ($features as $feature) {
            $this->assertFalse(plugin_supports('mod', 'communitywall', $feature));
        }
    }

}
