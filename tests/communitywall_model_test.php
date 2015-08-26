<?php

use Mockery as m;
use Functional as F;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/communitywall_model.php';

class communitywall_model_test extends advanced_testcase {

    /**
     * @var communitywall_model
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        $this->_cut = new communitywall_model();
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        m::close();
    }

    /**
     * tests instantiation
     */
    public function test_instantiation() {
        $this->assertInstanceOf('communitywall_model', $this->_cut);
    }

    /**
     * tests getting community walls by instanceid when no community walls exist
     * @global moodle_database $DB
     */
    public function test_get_all_by_instanceid_1() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $walls = $this->_cut->get_all_by_instanceid($module->id);
        $this->assertEquals(array(), $walls);
    }

    /**
     * tests getting community walls by instanceid when a few community walls exist
     * ensures ordering is by time created descending (i.e. newest first)
     * @global moodle_database $DB
     */
    public function test_get_all_by_instanceid_2() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
            mktime(10, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array(1, $module->id, 2, 'Wall 001', $times[0], $times[0]),
                array(2, $module->id, 2, 'Wall 002', $times[1], $times[1]),
                array(3, $module->id, 2, 'Wall 003', $times[2], $times[2]),
                array(4, $module->id, 2, 'Wall 004', $times[3], $times[3]),
                array(5, $module->id, 2, 'Wall 005', $times[4], $times[4]),
            ),
        )));
        $this->_cut->set_userid(2);
        $walls = $this->_cut->get_all_by_instanceid($module->id);
        $this->assertEquals(5, count($walls));
        $this->assertEquals(5, $walls[0]['id']);
        $this->assertEquals(1, $walls[1]['id']);
        $this->assertEquals(4, $walls[2]['id']);
        $this->assertEquals(2, $walls[3]['id']);
        $this->assertEquals(3, $walls[4]['id']);
    }

    /**
     * tests getting viewable walls by instanceid when in separate groups mode
     */
    public function test_get_all_viewable_by_instanceid_separate_groups() {
        list($module, $user1a, , , $user2b, $user3a) = $this->_seed_groups_and_groups_members();

        // set group mode
        $this->_cut->set_groupmode(SEPARATEGROUPS);

        // sorting function
        $f = function ($left, $right) {
            if ($left === $right) {
                return 0;
            }
            return $left < $right ? -1 : 1;
        };

        // from user1a's point of view, they should be able to see their own wall and user 1b's wall
        $this->_cut->set_userid($user1a->id);
        $walls = $this->_cut->get_all_viewable_by_instanceid($module->id);
        $this->assertCount(2, $walls);
        $this->assertEquals([1, 2], F\sort(F\pluck($walls, 'id'), $f));

        // from user2b's point of view, they should be able to see their own wall and user 2a's wall
        $this->_cut->set_userid($user2b->id);
        $walls = $this->_cut->get_all_viewable_by_instanceid($module->id);
        $this->assertCount(2, $walls);
        $this->assertEquals([3, 4], F\sort(F\pluck($walls, 'id'), $f));

        // from user3a's point of view, they should only be able to see their own wall
        $this->_cut->set_userid($user3a->id);
        $walls = $this->_cut->get_all_viewable_by_instanceid($module->id);
        $this->assertCount(1, $walls);
        $this->assertEquals(5, F\head($walls)['id']);
    }

    /**
     * tests getting a given community wall
     */
    public function test_get() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
            mktime(10, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array(1, $module->id, 2, 'Wall 001', $times[0], $times[0]),
                array(2, $module->id, 2, 'Wall 002', $times[1], $times[1]),
                array(3, $module->id, 2, 'Wall 003', $times[2], $times[2]),
                array(4, $module->id, 2, 'Wall 004', $times[3], $times[3]),
                array(5, $module->id, 2, 'Wall 005', $times[4], $times[4]),
            ),
        )));
        $this->_cut->set_userid(2);
        $wall = $this->_cut->get(3);
        $this->assertEquals(array(
            'id' => 3,
            'instanceid' => $module->id,
            'userid' => 2,
            'userfullname' => 'Admin User',
            'is_owner' => true,
            'title' => 'Wall 003',
            'timecreated' => $times[2],
            'timemodified' => $times[2],
        ), $wall);
    }

    /**
     * tests trying to get a wall that's visible when in separate groups mode
     */
    public function test_get_separate_groups_visible_walls() {
        list(, $user1a, , $user2a, , $user3a) = $this->_seed_groups_and_groups_members();

        // set user and group mode
        $this->_cut->set_groupmode(SEPARATEGROUPS);

        // user1a should be able to see their own and user1b's wall
        $this->_cut->set_userid($user1a->id);
        F\each([1, 2], function ($id) {
            $wall = $this->_cut->get($id);
            $this->assertEquals($id, $wall['id']);
        });

        // user2a should be able to see their own and user2b's wall
        $this->_cut->set_userid($user2a->id);
        F\each([3, 4], function ($id) {
            $wall = $this->_cut->get($id);
            $this->assertEquals($id, $wall['id']);
        });

        // user3a should only be able to see their own wall
        $this->_cut->set_userid($user3a->id);
        $wall = $this->_cut->get(5);
        $this->assertEquals(5, $wall['id']);
    }

    /**
     * tests trying to get a wall that's not visible when in separate groups mode
     * @expectedException dml_missing_record_exception
     */
    public function test_get_separate_groups_not_visible_wall_3() {
        $this->_test_get_separate_groups_not_visible_wall(3);
    }

    /**
     * tests trying to get a wall that's not visible when in separate groups mode
     * @expectedException dml_missing_record_exception
     */
    public function test_get_separate_groups_not_visible_wall_4() {
        $this->_test_get_separate_groups_not_visible_wall(4);
    }

    /**
     * tests trying to get a wall that's not visible when in separate groups mode
     * @expectedException dml_missing_record_exception
     */
    public function test_get_separate_groups_not_visible_wall_5() {
        $this->_test_get_separate_groups_not_visible_wall(5);
    }

    /**
     * tests trying to get a non-existent community wall
     * @expectedException dml_missing_record_exception
     */
    public function test_get_non_existent() {
        $this->_cut->get(1);
    }

    /**
     * tests saving a community wall for the first time
     * @global moodle_database $DB
     */
    public function test_save_1() {
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $data = array(
            'instanceid' => $module->id,
            'userid' => 2,
            'title' => 'Wall 001',
        );
        $data = $this->_cut->save($data, time());
        $this->assertArrayHasKey('id', $data);
    }

    /**
     * tests saving a community wall for a subsequent time
     * @global moodle_database $DB
     */
    public function test_save_2() {
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
                array(1, $module->id, 2, 'Wall 004', $times[0], $times[0]),
            ),
        )));
        $data = array(
            'instanceid' => $module->id,
            'id' => 1,
            'userid' => 2,
            'title' => 'Wall 004a',
        );
        $data = $this->_cut->save($data, time());
        $this->assertGreaterThan($times[0], $data['timemodified']);
        $this->assertEquals('Wall 004a', $DB->get_field('communitywall_wall', 'title', array('id' => 1)));
    }

    /**
     * tests deleting a community wall
     * @global moodle_database $DB
     */
    public function test_delete() {
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
                array(1, $module->id, 2, 'Wall 004', $times[0], $times[0]),
            ),
            'communitywall_note' => array(
                array('wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 2, 'Note 001', 0, 0, $times[0], $times[0]),
                array(1, 2, 'Note 002', 0, 0, $times[0], $times[0]),
                array(1, 2, 'Note 003', 0, 0, $times[0], $times[0]),
            ),
        )));
        $this->assertEquals(1, $DB->count_records('communitywall_wall', array('id' => 1)));
        $this->assertEquals(3, $DB->count_records('communitywall_note', array('wallid' => 1)));
        $this->_cut->delete(1);
        $this->assertFalse($DB->record_exists('communitywall_wall', array('id' => 1)));
        $this->assertFalse($DB->record_exists('communitywall_note', array('wallid' => 1)));
    }

    /**
     * tests counting the total number of community walls by instance id
     */
    public function test_get_total_by_instanceid() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
            mktime(10, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, 2, 'Wall 001', $times[0], $times[0]),
                array($module->id, 2, 'Wall 002', $times[1], $times[1]),
                array($module->id, 2, 'Wall 003', $times[2], $times[2]),
                array($module->id, 2, 'Wall 004', $times[3], $times[3]),
                array($module->id, 2, 'Wall 005', $times[4], $times[4]),
            ),
        )));
        $this->assertEquals(5, $this->_cut->get_total_by_instanceid($module->id));
    }

    /**
     * seeds the database with groups and groups members (for testing separate groups)
     * @return array
     */
    protected function _seed_groups_and_groups_members() {
        // create course and module
        $course = $this->getDataGenerator()->create_course([
            'groupmode'      => SEPARATEGROUPS,
            'groupmodeforce' => true,
        ]);
        $module = $this->getDataGenerator()->create_module('communitywall', [
            'course' => $course->id,
        ]);

        // create users and groups
        list($user1a, $user1b, $user2a, $user2b, $user3a) = F\map([1, 2, 3, 4, 5], function ($_) use ($course) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            return $user;
        });
        list($group1, $group2) = F\map([1, 2], function ($_) use ($course) {
            return $this->getDataGenerator()->create_group([
                'courseid' => $course->id,
            ]);
        });

        // assign group membership
        $this->getDataGenerator()->create_group_member([
            'userid' => $user1a->id,
            'groupid' => $group1->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $user1b->id,
            'groupid' => $group1->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $user2a->id,
            'groupid' => $group2->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'userid' => $user2b->id,
            'groupid' => $group2->id,
        ]);

        // rebuild course cache
        rebuild_course_cache($course->id);

        // some times
        $times = [
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
        ];

        // load dataset
        $this->loadDataSet($this->createArrayDataSet([
            'communitywall_wall' => [
                ['id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'],
                [1, $module->id, $user1a->id, 'Wall 001a', $times[0], $times[0]],
                [2, $module->id, $user1b->id, 'Wall 001b', $times[1], $times[1]],
                [3, $module->id, $user2a->id, 'Wall 002a', $times[2], $times[2]],
                [4, $module->id, $user2b->id, 'Wall 002b', $times[3], $times[3]],
                [5, $module->id, $user3a->id, 'Wall 003a', $times[3], $times[3]],
            ],
        ]));

        // return seeded data
        return [$module, $user1a, $user1b, $user2a, $user2b, $user3a, $group1, $group2];
    }

    /**
     * helper method
     * @param integer $id
     */
    protected function _test_get_separate_groups_not_visible_wall($id) {
        list(, $user1a, , , ,) = $this->_seed_groups_and_groups_members();

        // set user and group mode
        $this->_cut->set_userid($user1a->id);
        $this->_cut->set_groupmode(SEPARATEGROUPS);

        // user1a should not be able to see the given wall
        $this->_cut->get($id);
    }

}
