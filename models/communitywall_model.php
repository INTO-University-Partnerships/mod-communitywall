<?php

use Functional as F;

defined('MOODLE_INTERNAL') || die();

class communitywall_model {

    /**
     * the id of the logged in user
     * @var integer
     */
    protected $_userid;

    /**
     * either NOGROUPS or SEPARATEGROUPS
     * @var integer
     */
    protected $_group_mode = NOGROUPS;

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
     * @param integer $group_mode - either NOGROUPS or SEPARATEGROUPS
     */
    public function set_groupmode($group_mode) {
        $this->_group_mode = $group_mode;
    }

    /**
     * @global moodle_database $DB
     * @param integer $instanceid
     * @return integer
     */
    public function get_total_by_instanceid($instanceid) {
        global $DB;
        return (integer)$DB->count_records('communitywall_wall', array(
            'instanceid' => $instanceid,
        ));
    }

    /**
     * @global moodle_database $DB
     * @param integer $instanceid
     * @return integer
     */
    public function get_total_viewable_by_instanceid($instanceid) {
        global $DB;
        list($sql, $params) = $this->_get_total_viewable_by_instanceid($instanceid);
        return (integer)$DB->count_records_sql($sql, $params);
    }

    /**
     * @param integer $instanceid
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array
     */
    public function get_all_by_instanceid($instanceid, $limitfrom = 0, $limitnum = 0) {
        $sql = <<<SQL
            SELECT cw.*, u.firstname, u.lastname
            FROM {communitywall_wall} cw
            INNER JOIN {user} u ON u.id = cw.userid AND u.deleted = 0
            WHERE cw.instanceid = :instanceid
            ORDER BY cw.timecreated DESC
SQL;
        return $this->_sql_query_to_array($sql, [
            'instanceid' => $instanceid,
        ], $limitfrom, $limitnum);
    }

    /**
     * gets all 'viewable' walls
     * @param integer $instanceid
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array
     */
    public function get_all_viewable_by_instanceid($instanceid, $limitfrom = 0, $limitnum = 0) {
        list($sql, $params) = $this->_get_all_viewable_by_instanceid($instanceid);
        return $this->_sql_query_to_array($sql, $params, $limitfrom, $limitnum);
    }

    /**
     * @global moodle_database $DB
     * @param integer $id
     * @return array
     */
    public function get($id) {
        global $DB;
        $select = 'SELECT cw.*, u.firstname, u.lastname';
        $from = 'FROM {communitywall_wall} cw';
        if ($this->_group_mode === SEPARATEGROUPS) {
            $subquery = require __DIR__ . '/sql/separate_groups_subquery.php';
            $body = <<<SQL
                INNER JOIN {communitywall} w ON cw.instanceid = w.id
                INNER JOIN {user} u ON u.id = cw.userid AND u.deleted = 0
                LEFT JOIN ({$subquery}) g ON g.userid = cw.userid AND g.courseid = w.course
                WHERE cw.id = :id
                    AND (cw.userid = :userid2 OR g.userid IS NOT NULL)
SQL;
            $result = $DB->get_record_sql(join(' ', [$select, $from, $body]), [
                'id' => $id,
                'userid1' => $this->_userid,
                'userid2' => $this->_userid,
            ], MUST_EXIST);
        } else {
            $body = <<<SQL
                INNER JOIN {user} u ON u.id = cw.userid AND u.deleted = 0
                WHERE cw.id = :id
SQL;
            $result = $DB->get_record_sql(join(' ', [$select, $from, $body]), [
                'id' => $id,
            ], MUST_EXIST);
        }
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
        if (array_key_exists('id', $data)) {
            $DB->update_record('communitywall_wall', (object)$data);
        } else {
            $data['timecreated'] = $data['timemodified'];
            $data['id'] = (integer)$DB->insert_record('communitywall_wall', (object)$data);
        }
        return $this->get($data['id']);
    }

    /**
     * @global moodle_database $DB
     * @param integer $id
     */
    public function delete($id) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('communitywall_note', array('wallid' => $id));
        $DB->delete_records('communitywall_wall', array('id' => $id));
        $transaction->allow_commit();
    }

    /**
     * @param integer $instanceid
     * @return array
     */
    protected function _get_total_viewable_by_instanceid($instanceid) {
        $select = 'SELECT COUNT(cw.id)';
        if ($this->_group_mode === SEPARATEGROUPS) {
            $body = require __DIR__ . '/sql/separate_groups.php';
            return [join(' ', [$select, $body]), [
                'instanceid' => $instanceid,
                'userid1' => $this->_userid,
                'userid2' => $this->_userid,
            ]];
        } else {
            $body = require __DIR__ . '/sql/no_groups.php';
            return [join(' ', [$select, $body]), [
                'instanceid' => $instanceid,
                'userid' => $this->_userid,
            ]];
        }
    }

    /**
     * @param integer $instanceid
     * @return array
     */
    protected function _get_all_viewable_by_instanceid($instanceid) {
        $select = 'SELECT cw.*, u.firstname, u.lastname';
        $order_by = 'ORDER BY cw.timecreated DESC';
        if ($this->_group_mode === SEPARATEGROUPS) {
            $body = require __DIR__ . '/sql/separate_groups.php';
            return [join(' ', [$select, $body, $order_by]), [
                'instanceid' => $instanceid,
                'userid1' => $this->_userid,
                'userid2' => $this->_userid,
            ]];
        } else {
            $body = require __DIR__ . '/sql/no_groups.php';
            return [join(' ', [$select, $body, $order_by]), [
                'instanceid' => $instanceid,
                'userid' => $this->_userid,
            ]];
        }
    }

    /**
     * @global moodle_database $DB
     * @param string $sql
     * @param array $params
     * @param integer $limitfrom
     * @param integer $limitnum
     * @return array
     */
    protected function _sql_query_to_array($sql, $params, $limitfrom, $limitnum) {
        global $DB;
        $results = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        if (empty($results)) {
            return [];
        }
        return array_values(F\map($results, function ($result) {
            return $this->_obj_to_array($result);
        }));
    }

    /**
     * @param object $obj
     * @return array
     */
    protected function _obj_to_array($obj) {
        return array(
            'id' => (integer)$obj->id,
            'instanceid' => (integer)$obj->instanceid,
            'userid' => (integer)$obj->userid,
            'userfullname' => $obj->firstname . ' ' . $obj->lastname,
            'is_owner' => (isset($this->_userid) && $obj->userid == $this->_userid),
            'title' => $obj->title,
            'timecreated' => (integer)$obj->timecreated,
            'timemodified' => (integer)$obj->timemodified,
        );
    }

}
