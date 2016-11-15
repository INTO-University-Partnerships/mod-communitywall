<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../lib.php';

class mod_communitywall_lib_test extends advanced_testcase {

    /**
     * @var object
     */
    protected $_user1;

    /**
     * @var object
     */
    protected $_user2;

    /**
     * @var object
     */
    protected $_user3;

    /**
     * @var object
     */
    protected $_course;

    /**
     * @var object
     */
    protected $_module;

    /**
     * setUp
     */
    protected function setUp() {
        $times = [
            mktime(9, 0, 0, 11, 5, 2013),
        ];
        $this->_user1 = $this->getDataGenerator()->create_user();
        $this->_user2 = $this->getDataGenerator()->create_user();
        $this->_user3 = $this->getDataGenerator()->create_user();
        $this->_course = $this->getDataGenerator()->create_course();
        $this->_module = $this->getDataGenerator()->create_module('communitywall', [
            'course' => $this->_course->id,
            'completioncreatewall' => 1,
            'completionpostonwall' => 1,
        ]);
        $this->loadDataSet($this->createArrayDataSet([
            'communitywall_wall' => [
                ['id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'],
                [1, $this->_module->id, $this->_user1->id, 'Wall 001', $times[0], $times[0]],
                [2, $this->_module->id, $this->_user2->id, 'Wall 002', $times[0], $times[0]],
            ],
            'communitywall_note' => [
                ['wallid', 'userid', 'timecreated', 'timemodified'],
                [1, $this->_user1->id, $times[0], $times[0]],
                [1, $this->_user2->id, $times[0], $times[0]],
                [1, $this->_user2->id, $times[0], $times[0]],
                [1, $this->_user2->id, $times[0], $times[0]],
                [2, $this->_user2->id, $times[0], $times[0]],
                [2, $this->_user2->id, $times[0], $times[0]],
            ],
        ]));
        $this->resetAfterTest();
    }

    /**
     * @global moodle_database $DB
     */
    public function test_communitywall_delete_instance() {
        global $DB;
        $this->assertEquals(1, $DB->count_records('communitywall_wall', ['id' => 1]));
        $this->assertEquals(4, $DB->count_records('communitywall_note', ['wallid' => 1]));
        $this->assertEquals(1, $DB->count_records('communitywall_wall', ['id' => 2]));
        $this->assertEquals(2, $DB->count_records('communitywall_note', ['wallid' => 2]));
        communitywall_delete_instance($this->_module->id);
        $this->assertFalse($DB->record_exists('communitywall_wall', ['id' => 1]));
        $this->assertFalse($DB->record_exists('communitywall_note', ['wallid' => 1]));
        $this->assertFalse($DB->record_exists('communitywall_wall', ['id' => 2]));
        $this->assertFalse($DB->record_exists('communitywall_note', ['wallid' => 2]));
        $this->assertFalse($DB->record_exists('communitywall', ['id' => $this->_module->id]));
    }

    /**
     * tests the features that communitywall supports
     */
    public function test_communitywall_supports() {
        $features = [
            FEATURE_COMPLETION_TRACKS_VIEWS,
            FEATURE_COMPLETION_HAS_RULES,
            FEATURE_BACKUP_MOODLE2,
            FEATURE_GROUPS,
        ];
        foreach ($features as $feature) {
            $this->assertTrue(plugin_supports('mod', 'communitywall', $feature));
        }
    }

    /**
     * tests the features that communitywall does not support
     */
    public function test_communitywall_not_supports() {
        $features = [
            FEATURE_GRADE_HAS_GRADE,
            FEATURE_GRADE_OUTCOMES,
            FEATURE_ADVANCED_GRADING,
            FEATURE_CONTROLS_GRADE_VISIBILITY,
            FEATURE_PLAGIARISM,
            FEATURE_NO_VIEW_LINK,
            FEATURE_IDNUMBER,
            FEATURE_GROUPINGS,
            FEATURE_MOD_ARCHETYPE,
            FEATURE_MOD_INTRO,
            FEATURE_MODEDIT_DEFAULT_COMPLETION,
            FEATURE_COMMENT,
            FEATURE_RATE,
            FEATURE_SHOW_DESCRIPTION,
        ];
        foreach ($features as $feature) {
            $this->assertFalse(plugin_supports('mod', 'communitywall', $feature));
        }
    }

    /**
     * tests communitywall_get_completion_state
     * @global moodle_database $DB
     */
    public function test_communitywall_get_completion_state_neither() {
        global $DB;
        $DB->set_field('communitywall', 'completioncreatewall', '0', ['id' => $this->_module->id]);
        $DB->set_field('communitywall', 'completionpostonwall', '0', ['id' => $this->_module->id]);
        $cm = (object)[
            'instance' => $this->_module->id,
        ];

        // user 3 has done neither
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user3->id,
            COMPLETION_AND
        );
        $this->assertSame($result, COMPLETION_AND);

        // user 3 has done neither
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user3->id,
            COMPLETION_OR
        );
        $this->assertSame($result, COMPLETION_OR);
    }

    /**
     * tests communitywall_get_completion_state
     * @global moodle_database $DB
     */
    public function test_communitywall_get_completion_state_only_createwall() {
        global $DB;
        $DB->set_field('communitywall', 'completionpostonwall', '0', ['id' => $this->_module->id]);
        $cm = (object)[
            'instance' => $this->_module->id,
        ];

        // user 1 has created a wall (but hasn't posted on a wall)
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user1->id,
            COMPLETION_AND
        );
        $this->assertTrue($result);

        // user 3 hasn't created a wall
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user3->id,
            COMPLETION_AND
        );
        $this->assertFalse($result);
    }

    /**
     * tests communitywall_get_completion_state
     * @global moodle_database $DB
     */
    public function test_communitywall_get_completion_state_only_postonwall() {
        global $DB;
        $DB->set_field('communitywall', 'completioncreatewall', '0', ['id' => $this->_module->id]);
        $cm = (object)[
            'instance' => $this->_module->id,
        ];

        // user 1 hasn't posted on a wall (other than their own)
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user1->id,
            COMPLETION_AND
        );
        $this->assertFalse($result);

        // user 2 has posted on a wall (other than their own)
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user2->id,
            COMPLETION_AND
        );
        $this->assertTrue($result);
    }

    /**
     * tests communitywall_get_completion_state
     */
    public function test_communitywall_get_completion_state_both() {
        $cm = (object)[
            'instance' => $this->_module->id,
        ];

        // user 1 hasn't done both
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user1->id,
            COMPLETION_AND
        );
        $this->assertFalse($result);

        // user 1 has done either (he's created a wall)
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user1->id,
            COMPLETION_OR
        );
        $this->assertTrue($result);

        // user 2 has done both
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user2->id,
            COMPLETION_AND
        );
        $this->assertTrue($result);

        // user 2 has done either (because he's done both)
        $result = communitywall_get_completion_state(
            new stdClass(),
            $cm,
            $this->_user2->id,
            COMPLETION_OR
        );
        $this->assertTrue($result);
    }

}
