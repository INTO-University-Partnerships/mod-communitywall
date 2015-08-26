<?php

defined('MOODLE_INTERNAL') || die();

class communitywall_note_model {

    /**
    * the id of the logged in user
    * @var integer
    */
    protected $_userid;

    /**
     * c'tor
     */
    public function __construct() {
        // empty
    }

    /**
     * @param integer $userid
     */
    public function set_userid($userid) {
        $this->_userid = $userid;
    }

    /**
     * @global moodle_database $DB
     * @param integer $wallid
     * @return integer
     */
    public function get_total_by_wallid($wallid) {
        global $DB;
        return (integer)$DB->count_records('communitywall_note', array(
            'wallid' => $wallid,
        ));
    }

    /**
     * @global moodle_database $DB
     * @param integer $wallid
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array
     */
    public function get_all_by_wallid($wallid, $limitfrom = 0, $limitnum = 0) {
        global $DB;
        $retval = array();
        $userfields = user_picture::fields('u', null, 'userid');
        $sql = <<<SQL
            SELECT cn.*, $userfields
            FROM {communitywall_note} cn
            INNER JOIN {user} u ON u.id = cn.userid AND u.deleted = 0
            WHERE cn.wallid = :wallid
            ORDER BY cn.timecreated DESC
SQL;
        $results = $DB->get_records_sql($sql, array(
            'wallid' => $wallid,
        ), $limitfrom, $limitnum);
        if (empty($results)) {
            return $retval;
        }
        foreach ($results as $result) {
            $retval[] = $this->_obj_to_array($result);
        }
        return $retval;
    }

    /**
     * @global moodle_database $DB
     * @param integer $id
     * @return array
     */
    public function get($id) {
        global $DB;
        $userfields = user_picture::fields('u', null, 'userid');
        $sql = <<<SQL
            SELECT cn.*, $userfields
            FROM {communitywall_note} cn
            INNER JOIN {user} u ON u.id = cn.userid AND u.deleted = 0
            WHERE cn.id = :id
SQL;
        $result = $DB->get_record_sql($sql, array(
            'id' => $id,
        ), MUST_EXIST);
        return $this->_obj_to_array($result);
    }

    /**
     * @global moodle_database $DB
     * @param array $data
     * @param integer $now
     * @return array
     */
    public function save(array $data, $now) {
        global $DB;
        $data['timemodified'] = $now;
        if (array_key_exists('xcoord', $data)) {
            $data['xcoord'] = (integer)round($data['xcoord']);
        }
        if (array_key_exists('ycoord', $data)) {
            $data['ycoord'] = (integer)round($data['ycoord']);
        }
        if (array_key_exists('id', $data)) {
            $DB->update_record('communitywall_note', (object)$data);
        } else {
            $data['timecreated'] = $data['timemodified'];
            $data['id'] = (integer)$DB->insert_record('communitywall_note', (object)$data);
        }
        return $this->get($data['id']);
    }

    /**
     * @global moodle_database $DB
     * @param integer $id
     */
    public function delete($id) {
        global $DB;
        $DB->delete_records('communitywall_note', array('id' => $id));
    }

    /**
     * @param object $obj
     * @return array
     */
    protected function _obj_to_array($obj) {
        return array(
            'id' => (integer)$obj->id,
            'wallid' => (integer)$obj->wallid,
            'userid' => (integer)$obj->userid,
            'firstname' => $obj->firstname,
            'lastname' => $obj->lastname,
            'firstnamephonetic' => $obj->firstnamephonetic,
            'lastnamephonetic' => $obj->lastnamephonetic,
            'middlename' => $obj->middlename,
            'alternatename' => $obj->alternatename,
            'userfullname' => $obj->firstname . ' ' . $obj->lastname,
            'email' => $obj->email,
            'picture' => $obj->picture,
            'is_owner' => (isset($this->_userid) && $obj->userid == $this->_userid),
            'note' => $obj->note,
            'xcoord' => (integer)$obj->xcoord,
            'ycoord' => (integer)$obj->ycoord,
            'timecreated' => (integer)$obj->timecreated,
            'timemodified' => (integer)$obj->timemodified,
        );
    }
}