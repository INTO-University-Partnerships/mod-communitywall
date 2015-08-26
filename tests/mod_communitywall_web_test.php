<?php

// use the Client and Request classes
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

class mod_communitywall_web_test extends advanced_testcase {

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
     * tests a non-existent route
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function test_non_existent_route() {
        $client = new Client($this->_app);
        $client->request('GET', '/does_not_exist');
    }

    /**
     * tests the instances route that shows all activity instances (i.e. course modules) in a certain course
     * @global moodle_database $DB
     */
    public function test_instances_route() {
        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/instances/' . $this->_course->id);
        $this->assertTrue($client->getResponse()->isOk());

        // check the page content
        foreach (range(1, count($this->_walls)) as $i) {
            $this->assertContains('Community wall ' . $i, $client->getResponse()->getContent());
        }
        $this->assertNotContains('Community wall ' . (count($this->_walls) + 1), $client->getResponse()->getContent());
    }

    /**
     * tests the 'byinstanceid' route that lets you view a communitywall by instance id (as opposed to course module id)
     */
    public function test_byinstanceid_route_0() {
        $this->_test_byinstanceid_route($this->_walls[0]->id);
    }

    /**
     * tests the 'byinstanceid' route that lets you view a communitywall by instance id (as opposed to course module id)
     */
    public function test_byinstanceid_route_1() {
        $this->_test_byinstanceid_route($this->_walls[1]->id);
    }

    /**
     * tests the 'byinstanceid' route that lets you view a communitywall by instance id (as opposed to course module id)
     */
    public function test_byinstanceid_route_2() {
        $this->_test_byinstanceid_route($this->_walls[2]->id);
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_byinstanceid_route_invalid_id() {
        $this->_test_byinstanceid_route(999);
    }

    /**
     * tests the 'bycmid' route redirects to the 'byinstanceid' route
     */
    public function test_bycmid_route_0() {
        $this->_test_bycmid_route($this->_walls[0]->cmid, $this->_walls[0]->id);
    }

    /**
     * tests the 'bycmid' route redirects to the 'byinstanceid' route
     */
    public function test_bycmid_route_1() {
        $this->_test_bycmid_route($this->_walls[1]->cmid, $this->_walls[1]->id);
    }

    /**
     * tests the 'bycmid' route redirects to the 'byinstanceid' route
     */
    public function test_bycmid_route_2() {
        $this->_test_bycmid_route($this->_walls[2]->cmid, $this->_walls[2]->id);
    }

    /**
     * @expectedException dml_missing_record_exception
     */
    public function test_bycmid_route_invalid_id() {
        $this->_test_bycmid_route(999, -1);
    }

    /**
     * tests adding a new wall to an existing wall activity
     */
    public function test_add_new_wall_route() {
        global $CFG;

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/' . $this->_walls[0]->id . '/add');
        $this->assertTrue($client->getResponse()->isOk());

        // post some data
        $form = $crawler->selectButton(get_string('savechanges'))->form();
        $client->submit($form, array(
            'form[title]' => 'Title 001',
        ));

        // expect to get redirected to the wall that was added
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('wall', array(
            'id' => 6,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    /**
     * tests trying to add a new wall as a guest user is not permitted (and results in a redirect to the wall activity page)
     * @global moodle_database $DB
     */
    public function test_add_new_wall_as_guest() {
        global $CFG, $DB;

        // login as guest
        $this->setGuestUser();

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // set the instance of the 'guest' enrolment plugin to enabled
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_ENABLED, array(
            'courseid' => $course->id,
            'enrol' => 'guest',
        ));

        // create a course module
        $wall = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
        ));

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/' . $wall->id . '/add');

        // ensure we cannot add a new wall
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('byinstanceid', array(
            'id' => $wall->id,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    /**
     * tests trying to add a new wall to a wall that is closed for CPs
     * @global moodle_database $DB
     */
    public function test_add_new_wall_to_closed_communitywall() {
        global $CFG, $DB;

        // create a course
        $course = $this->getDataGenerator()->create_course();

        // create a course module
        $wall = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $course->id,
            'closed' => 1
        ));

        // enrol the user on the course
        $this->getDataGenerator()->enrol_user($this->_user->id, $course->id, $DB->get_field('role', 'id', array(
            'shortname' => 'student',
        )));

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/' . $wall->id . '/add');

        // ensure we cannot add a new wall
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('byinstanceid', array(
                'id' => $wall->id,
            ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    /**
     * tests editing an existing wall in an existing wall activity
     * @global moodle_database $DB
     */
    public function test_edit_existing_wall_route() {
        global $CFG, $DB;

        // request the page
        $client = new Client($this->_app);
        $crawler = $client->request('GET', '/' . $this->_walls[0]->id . '/edit/1');
        $this->assertTrue($client->getResponse()->isOk());

        // post some data
        $form = $crawler->selectButton(get_string('savechanges'))->form();
        $client->submit($form, array(
            'form[title]' => 'Wall 001a',
        ));
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('wall', array(
            'id' => 1,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));

        // ensure the title got changed as expected
        $this->assertEquals('Wall 001a', $DB->get_field('communitywall_wall', 'title', array('id' => 1)));
    }

    /**
     * tests trying to edit a non-existing wall to a non-existent wall activity
     * @expectedException dml_missing_record_exception
     */
    public function test_edit_non_existent_wall_in_non_existent_activity() {
        $client = new Client($this->_app);
        $client->request('GET', '/999/edit/999');
    }

    /**
     * tests trying to edit a non-existing wall
     * @expectedException dml_missing_record_exception
     */
    public function test_edit_non_existent_wall() {
        $client = new Client($this->_app);
        $client->request('GET', '/' . $this->_walls[0]->id . '/edit/999');
    }

    /**
     * tests trying to edit an existing wall when the user isn't the owner (or an admin)
     */
    public function test_edit_existing_wall_when_not_owner_or_admin() {
        global $CFG;

        // request the page
        $client = new Client($this->_app);
        $client->request('GET', '/' . $this->_walls[2]->id . '/edit/5');
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('wall', array(
            'id' => 5,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

    /**
     * test route to view an existing wall
     */
    public function test_get_wall_route() {
        $client = new Client($this->_app);
        $client->request('GET', '/wall/3');
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertContains('Wall 003', $client->getResponse()->getContent());
    }

    /**
     * test route to view a non existing wall
     * @expectedException dml_missing_record_exception
     */
    public function test_get_non_existing_wall_route() {
        $client = new Client($this->_app);
        $client->request('GET', '/wall/999');
    }

    /**
     * tests the 'byinstanceid' route that lets you view a communitywall by instance id (as opposed to course module id)
     * @param integer $id
     */
    protected function _test_byinstanceid_route($id) {
        $client = new Client($this->_app);
        $client->request('GET', '/' . $id);
        $this->assertTrue($client->getResponse()->isOk());
        $this->assertRegExp('/<h2>Community wall [1-3]{1}<\/h2>/', $client->getResponse()->getContent());
        $this->assertContains('Header goes here', $client->getResponse()->getContent());
        $this->assertContains('Footer goes here', $client->getResponse()->getContent());
    }

    /**
     * tests the 'bycmid' route redirects to the 'byinstanceid' route
     * @param integer $cmid
     * @param integer $id
     */
    protected function _test_bycmid_route($cmid, $id) {
        global $CFG;
        $client = new Client($this->_app);
        $client->request('GET', '/cmid/' . $cmid);
        $url = $CFG->wwwroot . SLUG . $this->_app['url_generator']->generate('byinstanceid', array(
            'id' => $id,
        ));
        $this->assertTrue($client->getResponse()->isRedirect($url));
    }

}
