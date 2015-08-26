<?php

use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class mod_communitywall_v1_api_test extends advanced_testcase {

    /**
     * @var object
     */
    protected $_user;

    /**
     * @var object
     */
    protected $_course;

    /**
     * @var array
     */
    protected $_walls;

    /**
     * @var Silex\Application
     */
    protected $_app;

    /**
     * setUp
     */
    public function setUp() {
        global $CFG;

        if (!defined('SLUG')) {
            define('SLUG', '');
        }
        if (!defined('SILEX_WEB_TEST')) {
            define('SILEX_WEB_TEST', true);
        }

        // create Silex app
        $this->_app = require __DIR__ . '/../app.php';
        $this->_app['debug'] = true;
        $this->_app['exception_handler']->disable();

        // add middleware to work around Moodle expecting non-empty $_GET or $_POST
        $this->_app->before(function (Request $request) {
            if (empty($_GET) && 'GET' == $request->getMethod()) {
                $_GET = $request->query->all();
            }
            if (empty($_POST) && 'POST' == $request->getMethod()) {
                $_POST = $request->request->all();
            }
        });

        // set up data and reset the database after each test
        list($this->_user, $this->_course, $this->_walls) = $this->_setup_data();
        $this->resetAfterTest();
    }

    /**
     * tearDown
     */
    public function tearDown() {
        $_GET = array();
        $_POST = array();
    }

    /**
     * sets up some data
     * @global moodle_database $DB
     * @return array
     */
    protected function _setup_data() {
        global $DB;

        // create a user
        $user = $this->getDataGenerator()->create_user();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a handful of modules within the course
        $walls = array();
        foreach (range(1, 5) as $i) {
            $walls[] = $this->getDataGenerator()->create_module('communitywall', array(
                'course' => $course->id,
            ));
        }

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // create some walls
        $times = array(
            mktime( 9, 0, 0, 11, 5, 2013),
            mktime( 7, 0, 0, 11, 5, 2013),
            mktime( 6, 0, 0, 11, 5, 2013),
            mktime( 8, 0, 0, 11, 5, 2013),
            mktime(10, 0, 0, 11, 5, 2013),
        );
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_wall' => array(
                array('id', 'instanceid', 'userid', 'title', 'timecreated', 'timemodified'),
                array(1, $walls[0]->id, $user->id, 'Wall 001', $times[0], $times[0]),
                array(2, $walls[0]->id, $user->id, 'Wall 002', $times[1], $times[1]),
                array(3, $walls[0]->id, $user->id, 'Wall 003', $times[2], $times[2]),
                array(4, $walls[1]->id, $user->id, 'Wall 004', $times[3], $times[3]),
                array(5, $walls[2]->id, 2, 'Wall 005', $times[4], $times[4]),
            ),
        )));

        // login the user
        $this->setUser($user);

        // return the objects
        return array($user, $course, $walls);
    }

    /**
     * tests the route that fetches all walls within a particular communitywall activity
     */
    public function test_walls_route_page1() {
        // request page 1 of the collection (2 items per page)
        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id, array(
                'limitfrom' => 0,
                'limitnum' => 2,
            ), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // get the JSON content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $content->total);
        $this->assertCount(2, $content->walls);

        // first item
        $this->assertEquals(1, $content->walls[0]->id);
        $this->assertEquals('Wall 001', $content->walls[0]->title);

        // second item
        $this->assertEquals(2, $content->walls[1]->id);
        $this->assertEquals('Wall 002', $content->walls[1]->title);
    }

    /**
     * tests the route that fetches all walls within a particular communitywall activity
     */
    public function test_walls_route_page2() {
        // request page 2 of the collection (2 items per page)
        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id, array(
            'limitfrom' => 2,
            'limitnum' => 2,
        ), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // get the JSON content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $content->total);
        $this->assertCount(1, $content->walls);

        // first item
        $this->assertEquals(3, $content->walls[0]->id);
        $this->assertEquals('Wall 003', $content->walls[0]->title);
    }

    /**
     * tests the route that requests a single wall
     */
    public function test_wall_get_route() {
        // request wall 3
        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id . '/3', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // get the JSON content
        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(3, $content->id);
        $this->assertEquals('Wall 003', $content->title);
        $this->assertTrue($content->is_owner);
    }

    /**
     * tests the route that requests a single wall when the requested wall doesn't exist
     */
    public function test_wall_get_route_non_existent_wall() {
        // request wall 999
        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id . '/999', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * tests the route that deletes a single wall
     */
    public function test_wall_delete_route() {
        // request wall 3
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/3', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(204, $client->getResponse()->getStatusCode());
    }

    /**
     * tests the route that deletes a single wall when the requested wall doesn't exist
     */
    public function test_wall_delete_route_non_existent_wall() {
        // request wall 999
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/999', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * tests the route that deletes a single wall when the user doesn't own the requested wall
     */
    public function test_wall_delete_route_not_owner_of_wall() {
        // request wall 5
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[2]->id . '/5', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));
        $this->assertTrue($client->getResponse()->isForbidden());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        // get the JSON content
        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notownerofwall', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
    }

    /**
     * tests the route to get all notes of a non existing wall
     */
    public function test_get_all_notes_non_existing_wall() {
        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id . '/999/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * tests the route to get all notes of an existing wall
     */
    public function test_get_all_notes_existing_wall() {
        global $OUTPUT, $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001 http://www.example.com', 0, 0, $now, $now),
                array(2, 1, $this->_user->id, 'Note 002 www.example.com', 0, 0, $now, $now),
                array(3, 1, $this->_user->id, 'Note 003 example.com', 0, 0, $now, $now),
                array(4, 1, $this->_user->id, 'Note 004', 0, 0, $now, $now),
            ),
        )));

        $client = new Client($this->_app);
        $client->request('GET', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ));

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $content = json_decode($client->getResponse()->getContent());
        $this->assertEquals(4, $content->total);
        $this->assertCount(4, $content->notes);

        // Should be correct URLS
        $this->assertEquals('Note 001 http://www.example.com', $content->notes[0]->note);
        $this->assertEquals('Note 002 http://www.example.com', $content->notes[1]->note);

        // Should not be correct URLs
        $this->assertEquals('Note 003 example.com', $content->notes[2]->note);

        $note = (array)$DB->get_record('communitywall_note', array('id' => 1));
        $note['userfullname'] = $this->_user->firstname . ' ' . $this->_user->lastname;
        $note['is_owner'] = true;
        $note['userpicture'] = $OUTPUT->user_picture((object)array(
            'id' => $this->_user->id,
            'picture' => $this->_user->picture,
            'firstname' => $this->_user->firstname,
            'lastname' => $this->_user->lastname,
            'firstnamephonetic' => $this->_user->firstnamephonetic,
            'lastnamephonetic' => $this->_user->lastnamephonetic,
            'middlename' => $this->_user->middlename,
            'alternatename' => $this->_user->alternatename,
            'imagealt' => $this->_user->firstname . ' ' . $this->_user->lastname,
            'email' => $this->_user->email
        ), array(
            'size' => 40,
            'link' => false
        ));
        $this->assertEquals((array)$content->notes[0], $note);

    }

    /**
     * tests the route that posts a note to an existing wall
     */
    public function test_wall_post_note_existing_wall_route() {
        global $DB;

        $time = $this->_app['now']();
        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ));

        $client = new Client($this->_app);
        $client->request('POST', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ), $content);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(1, $DB->count_records('communitywall_note', array('wallid' => 1)));

        $note = (array)$DB->get_record('communitywall_note', array(), 'wallid, note, xcoord, ycoord');
        $this->assertEquals(array(
            'wallid' => 1,
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0,
        ), $note);
    }

    /**
     * tests the route that posts a note to a non existing wall
     */
    public function test_wall_post_note_non_existing_wall_route() {
        global $DB;

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ));

        $client = new Client($this->_app);
        $client->request('POST', '/api/v1/wall/' . $this->_walls[0]->id . '/999/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ), $content);

        $this->assertTrue($client->getResponse()->isNotFound());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(0, $DB->count_records('communitywall_note'));
    }

    /**
     * tests the route that posts a note as a guest to an existing wall
     */
    public function test_wall_post_note_as_guest_route() {
        global $CFG, $DB;

        // login as guest
        $this->setGuestUser();

        // set the instance of the 'guest' enrolment plugin to enabled
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, array(
            'courseid' => $this->_course->id,
            'enrol' => 'guest',
        ));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ));

        $client = new Client($this->_app);
        $client->request('POST', '/api/v1/wall/' . $this->_walls[0]->id . '/3/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:noteasguestdenied', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(0, $DB->count_records('communitywall_note', array('wallid' => 3)));
    }

    /**
     * tests to post a note without text
     */
    public function test_wall_post_note_no_text_route() {
        global $DB;

        // create a comment to post
        $content = json_encode(array(
            'xcoord' => 0,
            'ycoord' => 0
        ));

        $client = new Client($this->_app);
        $client->request('POST', '/api/v1/wall/' . $this->_walls[0]->id . '/3/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notemissing', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(0, $DB->count_records('communitywall_note', array('wallid' => 3)));
    }

    /**
     * tests to post a note without coordinates
     */
    public function test_wall_post_note_no_coords_route() {
        global $DB;

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 001'
        ));

        $client = new Client($this->_app);
        $client->request('POST', '/api/v1/wall/' . $this->_walls[0]->id . '/3/note', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:coordinatesmissing', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(0, $DB->count_records('communitywall_note', array('wallid' => 3)));
    }

    /**
     * tests to edit a note on a non existing wall
     */
    public function test_wall_put_note_non_existing_wall_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/999/note/1', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:walldoesntexist', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
    }

    /**
     * tests to edit a non existing note
     */
    public function test_wall_put_non_existing_note() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/3/note/2', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notedoesntexist', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
    }

    /**
     * tests to edit a note on an existing wall with an existing note
     */
    public function test_wall_put_note_existing_wall_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ), $content);

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $note = (array)$DB->get_record_sql('SELECT note, xcoord, ycoord FROM {communitywall_note} WHERE id = :id', array('id' => 1));
        $this->assertEquals(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ), $note);
    }

    /**
     * tests to edit a note when the user is not the owner
     */
    public function test_wall_put_note_not_owner_route() {
        global $DB;

        $now = time();

        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, 2, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ), $content);

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notownerofnote', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));

        $note = (array)$DB->get_record_sql('SELECT note, xcoord, ycoord FROM {communitywall_note} WHERE id = :id', array('id' => 1));
        $this->assertEquals(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ), $note);
    }

    /**
     * tests to edit a note as admin
     */
    public function test_wall_put_note_as_admin_route() {
        global $DB, $USER;

        // login the admin user
        $this->setAdminUser();
        $USER->email = 'admin@into.uk.com';
        $this->assertEquals(2, $USER->id);

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, 1, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ), $content);

        $note = (array)$DB->get_record_sql('SELECT note, xcoord, ycoord FROM {communitywall_note} WHERE id = :id', array('id' => 1));
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(array(
            'note' => 'Note 002',
            'xcoord' => 10,
            'ycoord' => 10
        ), $note);

    }


    /**
     * tests to edit an existing note without text
     */
    public function test_wall_put_note_no_text_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'xcoord' => 10,
            'ycoord' => 10
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ), $content);

        $note = (array)$DB->get_record_sql('SELECT note, xcoord, ycoord FROM {communitywall_note} WHERE id = :id', array('id' => 1));
        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notemissing', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));

        $this->assertEquals(array(
            'note' => 'Note 001',
            'xcoord' => 0,
            'ycoord' => 0
        ), $note);
    }

    /**
     * tests to edit an existing note without coordinates
     */
    public function test_wall_put_note_no_coordinates_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        // create a comment to post
        $content = json_encode(array(
            'note' => 'Note 002',
        ));

        $client = new Client($this->_app);
        $client->request('PUT', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ), $content);

        $note = (array)$DB->get_record_sql('SELECT note FROM {communitywall_note} WHERE id = :id', array('id' => 1));
        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:coordinatesmissing', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));

        $this->assertEquals(array(
            'note' => 'Note 001'
        ), $note);
    }

    /**
     * tests deleting a note on a non existing wall
     */
    public function test_wall_delete_note_non_existing_wall_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        $this->assertEquals(1, $DB->count_records('communitywall_note'));
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/999/note/1', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:walldoesntexist', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(1, $DB->count_records('communitywall_note'));
    }

    /**
     * test deleting a non existing note
     */
    public function test_wall_delete_note_non_existing_note_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
            ),
        )));

        $this->assertEquals(1, $DB->count_records('communitywall_note'));
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/999', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notedoesntexist', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(1, $DB->count_records('communitywall_note'));
    }

    /**
     * tests deleting an existing note
     */
    public function test_wall_delete_note_existing_note_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
                array(2, 1, $this->_user->id, 'Note 002', 0, 0, $now, $now),
            ),
        )));

        $this->assertEquals(2, $DB->count_records('communitywall_note'));
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(1, $DB->count_records('communitywall_note'));
    }

    /**
     * tests deleting a note where user is not the owner
     */
    public function test_wall_delete_note_not_owner_route() {
        global $DB;

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
                array(2, 1, 2, 'Note 002', 0, 0, $now, $now),
            ),
        )));

        $this->assertEquals(2, $DB->count_records('communitywall_note'));
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/2', array(), array(), array(
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
        ));

        $this->assertTrue($client->getResponse()->isClientError());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));
        $this->assertEquals(get_string('jsonapi:notownerofnote', $this->_app['plugin']), json_decode($client->getResponse()->getContent()));
        $this->assertEquals(2, $DB->count_records('communitywall_note'));
    }

    /**
     * tests deleting a note as admin user
     */
    public function test_wall_delete_note_as_admin_route() {
        global $DB, $USER;

        // login the admin user
        $this->setAdminUser();
        $USER->email = 'admin@into.uk.com';
        $this->assertEquals(2, $USER->id);

        $now = time();
        $this->loadDataSet($this->createArrayDataSet(array(
            'communitywall_note' => array(
                array('id', 'wallid', 'userid', 'note', 'xcoord', 'ycoord', 'timecreated', 'timemodified'),
                array(1, 1, $this->_user->id, 'Note 001', 0, 0, $now, $now),
                array(2, 1, $this->_user->id, 'Note 002', 0, 0, $now, $now),
            ),
        )));

        $this->assertEquals(2, $DB->count_records('communitywall_note'));
        $client = new Client($this->_app);
        $client->request('DELETE', '/api/v1/wall/' . $this->_walls[0]->id . '/1/note/1', array(), array(), array(
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'
            ));

        $this->assertTrue($client->getResponse()->isSuccessful());
        $this->assertEquals(1, $DB->count_records('communitywall_note'));
    }

}
