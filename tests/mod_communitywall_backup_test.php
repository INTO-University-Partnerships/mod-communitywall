<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../src/mod_communitywall_backup_controller.php';

/**
 * @see http://docs.moodle.org/dev/Backup_2.0_for_developers
 */
class mod_communitywall_backup_test extends advanced_testcase {

    /**
     * @var integer
     */
    protected $_cmid;

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * @var object
     */
    protected $_course;

    /**
     * @var object
     */
    protected $_course_module;

    /**
     * @var integer
     */
    protected $_t0;

    /**
     * @var mod_communitywall_backup_controller
     */
    protected $_cut;

    /**
     * setUp
     */
    protected function setUp() {
        global $CFG;
        $CFG->keeptempdirectoriesonbackup = true;

        // record initial time
        $this->_t0 = time();

        // create course and some course modules (of which we're testing the last)
        $this->_course = $this->getDataGenerator()->create_course();
        foreach (array('forum', 'forum', 'communitywall', 'communitywall') as $module) {
            $this->getDataGenerator()->create_module($module, array(
                'course' => $this->_course->id,
            ));
        }
        $this->_course_module = $this->getDataGenerator()->create_module('communitywall', array(
            'course' => $this->_course->id,
            'closed' => 1,
            'header' => array(
                'format' => FORMAT_HTML,
                'text' => '<p>My lovely header</p>'
            ),
            'footer' => array(
                'format' => FORMAT_HTML,
                'text' => '<p>My lovely footer</p>'
            ),
        ));

        // set the course module id and the user id
        $this->_cmid = $this->_course_module->cmid;
        $this->_userid = 2;

        // create an instance of the class under test
        $this->_cut = new mod_communitywall_backup_controller(
            backup::TYPE_1ACTIVITY,
            $this->_cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_GENERAL,
            $this->_userid
        );

        $this->resetAfterTest(true);
    }

    /**
     * tests instantiation of a backup controller
     */
    public function test_backup_controller_instantiation() {
        $this->assertInstanceOf('backup_controller', $this->_cut);
    }

    /**
     * tests executing a plan creates a single directory in dataroot in /temp/backup
     */
    public function test_execute_plan_creates_one_directory() {
        global $CFG;
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(0, $child_directories);
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(1, $child_directories);
    }

    /**
     * tests the backupid corresponds to a directory in dataroot in /temp/backup
     */
    public function test_get_backupid_matches_directory() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $this->assertCount(1, $child_directories);
        $this->assertEquals($child_directories[0], $this->_cut->get_backupid());
    }

    /**
     * tests executing a plan creates a single course module subdirectory in dataroot in /temp/backup/{backupid}/activities/communitywall_{cmid}
     */
    public function test_execute_plan_creates_communitywall_subdirectory() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $dir = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/communitywall_' . $this->_course_module->cmid;
        $this->assertFileExists($dir);
    }

    /**
     * tests executing a plan for a community wall course module creates a module.xml file
     */
    public function test_execute_plan_creates_module_xml() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/communitywall_' . $this->_course_module->cmid . '/module.xml';
        $this->assertFileExists($file);
    }

    /**
     * tests executing a plan for a community wall course module creates a communitywall.xml file
     */
    public function test_execute_plan_creates_communitywall_xml() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/communitywall_' . $this->_course_module->cmid . '/communitywall.xml';
        $this->assertFileExists($file);
    }

    /**
     * tests executing a plan for a community wall course module creates a communitywall.xml file with the expected content
     */
    public function test_execute_plan_creates_expected_communitywall_xml_content() {
        global $CFG;
        $this->_cut->execute_plan();
        $child_directories = self::_get_child_directories($CFG->dataroot . '/temp/backup');
        $file = $CFG->dataroot . '/temp/backup/' . $child_directories[0] . '/activities/communitywall_' . $this->_course_module->cmid . '/communitywall.xml';
        $xml = simplexml_load_file($file);
        $this->assertEquals($this->_course_module->id, $xml['id']);
        $this->assertSame($this->_course_module->cmid, (integer)$xml['moduleid']);
        $this->assertEquals('communitywall', $xml['modulename']);
        $this->assertEquals($this->_course_module->name, $xml->communitywall->name);
        $this->assertGreaterThanOrEqual($this->_t0, (integer)$xml->communitywall->timecreated);
        $this->assertLessThanOrEqual(time(), (integer)$xml->communitywall->timecreated);
        $this->assertGreaterThanOrEqual($this->_t0, (integer)$xml->communitywall->timemodified);
        $this->assertLessThanOrEqual(time(), (integer)$xml->communitywall->timemodified);
        $this->assertSame(1, (integer)$xml->communitywall->closed);
        $this->assertEquals('<p>My lovely header</p>', $xml->communitywall->header);
        $this->assertEquals('<p>My lovely footer</p>', $xml->communitywall->footer);
    }

    /**
     * tests encoding content links encodes the /mod/communitywall/index.php URL
     */
    public function test_encode_content_links_encodes_mod_communitywall_index_url() {
        global $CFG;
        $link = $CFG->wwwroot . '/mod/communitywall/index.php?id=123';
        $content = '<p>hello</p><a href="' . $link . '">click here</a><p>world</p>';
        $result = backup_communitywall_activity_task::encode_content_links($content);
        $encoded_link = '$@COMMUNITYWALLINDEX*123@$';
        $this->assertSame('<p>hello</p><a href="' . $encoded_link . '">click here</a><p>world</p>', $result);
    }

    /**
     * tests encoding content links encodes the /mod/communitywall/view.php URL
     */
    public function test_encode_content_links_encodes_mod_communitywall_view_url() {
        global $CFG;
        $link = $CFG->wwwroot . '/mod/communitywall/view.php?id=123';
        $content = '<p>hello</p><a href="' . $link . '">click here</a><p>world</p>';
        $result = backup_communitywall_activity_task::encode_content_links($content);
        $encoded_link = '$@COMMUNITYWALLVIEWBYID*123@$';
        $this->assertSame('<p>hello</p><a href="' . $encoded_link . '">click here</a><p>world</p>', $result);
    }

    /**
     * returns an array of directories within the given directory (not recursively)
     * @param string $dir
     * @return array
     */
    protected static function _get_child_directories($dir) {
        $retval = array();
        $ignore = array('.', '..');
        if ($handle = opendir($dir)) {
            while (false !== ($entry = readdir($handle))) {
                if (is_dir($dir . '/' . $entry) && !in_array($entry, $ignore)) {
                    $retval[] = $entry;
                }
            }
            closedir($handle);
        }
        return $retval;
    }

}
