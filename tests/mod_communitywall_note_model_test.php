<?php

use Mockery as m;

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/communitywall_note_model.php';

class communitywall_note_model_test extends advanced_testcase {

    /**
     * @var communitywall_note_model
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        $this->_cut = new communitywall_note_model();
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
        $this->assertInstanceOf('communitywall_note_model', $this->_cut);
    }

    /**
     * tests getting notes by wallid when no notes exist
     * @global moodle_database $DB
     */
    public function test_get_all_by_wallid_1() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013)
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, 2, 'Wall 001', $times[0], $times[0])
            )
        )));
        $notes = $this->_cut->get_all_by_wallid(1);
        $this->assertEquals(array(), $notes);
    }

    /**
     * tests getting notes by wallid when a few notes exist
     * ensures ordering is by time created descending (i.e. newest first)
     * @global moodle_database $DB
     */
    public function test_get_all_by_wallid_2() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, 2, 'Wall 001', $times[0], $times[0])
            ),
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, 2, 'Note 001', 0, 0, $times[1], $times[1]),
                array(2, 1, 2, 'Note 002', 10, 10, $times[2], $times[2]),
                array(3, 1, 2, 'Note 003', 20, 20, $times[3], $times[3])
            )
        )));
        $this->_cut->set_userid(2);
        $notes = $this->_cut->get_all_by_wallid(1);
        $this->assertEquals(3, count($notes));
        $this->assertEquals(3, $notes[0]['id']);
        $this->assertEquals(1, $notes[1]['id']);
        $this->assertEquals(2, $notes[2]['id']);
    }

    /**
     * tests getting a given note
     */
    public function test_get() {

        $user = $this->getDataGenerator()->create_user();
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, $user->id, 'Wall 001', $times[0], $times[0])
            ),
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $user->id, 'Note 001', 0, 0, $times[1], $times[1]),
                array(2, 1, $user->id, 'Note 002', 10, 10, $times[2], $times[2]),
                array(3, 1, $user->id, 'Note 003', 20, 20, $times[3], $times[3])
            )
        )));
        $this->_cut->set_userid($user->id);
        $note = $this->_cut->get(3);
        $this->assertEquals(array(
            'id' => 3,
            'wallid' => 1,
            'userid' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'userfullname' => sprintf("%s %s", $user->firstname, $user->lastname),
            'firstnamephonetic' => $user->firstnamephonetic,
            'lastnamephonetic' => $user->lastnamephonetic,
            'middlename' => $user->middlename,
            'alternatename' => $user->alternatename,
            'email' => $user->email,
            'picture' => 0,
            'is_owner' => true,
            'note' => 'Note 003',
            'xcoord' => 20,
            'ycoord' => 20,
            'timecreated' => $times[3],
            'timemodified' => $times[3],
        ), $note);
    }

    /**
     * tests trying to get a non-existent note
     * @expectedException dml_missing_record_exception
     */
    public function test_get_non_existent() {
        $this->_cut->get(1);
    }

    /**
     * tests saving a note for the first time
     * @global moodle_database $DB
     */
    public function test_save_1() {
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013)
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, 2, 'Wall 001', $times[0], $times[0])
            )
        )));
        $data = array(
            'wallid' => 1,
            'userid' => 2,
            'note' => 'My first note',
            'xcoord' => 30,
            'ycoord' => 30
        );
        $data = $this->_cut->save($data, time());
        $this->assertArrayHasKey('id', $data);
    }

    /**
     * tests saving a note for a subsequent time
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
            'wallid' => 1,
            'userid' => 2,
            'note' => 'Note 002',
            'xcoord' => 30,
            'ycoord' => 30
        );
        $data = $this->_cut->save($data, time());
        $this->assertGreaterThan($times[0], $data['timemodified']);
        $this->assertEquals('Note 002', $DB->get_field('communitywall_note', 'note', array('wallid' => 1)));
    }

    /**
     * tests saving a note with floating point coordinates
     * @global moodle_database $DB
     */
    public function test_save_with_floating_point_coordinates() {
        global $DB;
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013)
        );
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array($module->id, 2, 'Wall 001', $times[0], $times[0])
            )
        )));
        $data = array(
            'wallid' => 1,
            'userid' => 2,
            'note' => 'My first note',
            'xcoord' => 30.25,
            'ycoord' => 30.50
        );
        $data = $this->_cut->save($data, time());
        $this->assertArrayHasKey('id', $data);
        $this->assertEquals('30', $DB->get_field('communitywall_note', 'xcoord', array('id' => $data['id'])));
        $this->assertEquals('31', $DB->get_field('communitywall_note', 'ycoord', array('id' => $data['id'])));
    }

    /**
     * tests deleting a note
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
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, 2, 'Note 001', 0, 0, $times[0], $times[0]),
                array(2, 1, 2, 'Note 002', 0, 0, $times[0], $times[0]),
                array(3, 1, 2, 'Note 003', 0, 0, $times[0], $times[0]),
            ),
        )));
        $this->assertEquals(3, $DB->count_records('communitywall_note', array('wallid' => 1)));
        $this->_cut->delete(1);
        $this->assertEquals(2, $DB->count_records('communitywall_note', array('wallid' => 1)));
    }

    /**
     * tests counting the total number of note by wall id
     */
    public function test_get_total_by_wallid() {
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
            ),
            'communitywall_note' => array(
                array('wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 2, 'Note 001', 0, 0, $times[0], $times[0]),
                array(1, 2, 'Note 002', 0, 0, $times[0], $times[0]),
                array(1, 2, 'Note 003', 0, 0, $times[0], $times[0]),
            ),
        )));
        $this->assertEquals(3, $this->_cut->get_total_by_wallid(1));
    }

}
