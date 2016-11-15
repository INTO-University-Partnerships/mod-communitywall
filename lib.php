<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @global moodle_database $DB
 * @param object $obj
 * @param mod_communitywall_mod_form $mform
 * @return integer
 */
function communitywall_add_instance($obj, mod_communitywall_mod_form $mform = null) {
    global $DB;
    $obj->timecreated = $obj->timemodified = time();
    $obj->closed = !empty($obj->closed);
    $obj->header = (isset($obj->header) && array_key_exists('text', $obj->header)) ? $obj->header['text'] : null;
    $obj->footer = (isset($obj->footer) && array_key_exists('text', $obj->footer)) ? $obj->footer['text'] : null;
    $obj->id = $DB->insert_record('communitywall', $obj);
    return $obj->id;
}

/**
 * @global moodle_database $DB
 * @param object $obj
 * @param mod_communitywall_mod_form $mform
 * @return boolean
 */
function communitywall_update_instance($obj, mod_communitywall_mod_form $mform) {
    global $DB;
    $obj->id = $obj->instance;
    $obj->timemodified = time();
    $obj->closed = !empty($obj->closed);
    $obj->header = (isset($obj->header) && array_key_exists('text', $obj->header)) ? $obj->header['text'] : null;
    $obj->footer = (isset($obj->footer) && array_key_exists('text', $obj->footer)) ? $obj->footer['text'] : null;
    $success = $DB->update_record('communitywall', $obj);
    return $success;
}

/**
 * @global moodle_database $DB
 * @param integer $id
 * @return boolean
 */
function communitywall_delete_instance($id) {
    global $DB;
    require_once __DIR__ . '/models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $walls = $communitywall_model->get_all_by_instanceid($id);
    foreach ($walls as $wall) {
        $communitywall_model->delete($wall['id']);
    }
    $success = $DB->delete_records('communitywall', array('id' => $id));
    return $success;
}

/**
* @param string $feature
* @return boolean
*/
function communitywall_supports($feature) {
    $support = array(
        FEATURE_COMPLETION_TRACKS_VIEWS => true,
        FEATURE_GRADE_HAS_GRADE => false,
        FEATURE_GRADE_OUTCOMES => false,
        FEATURE_ADVANCED_GRADING => false,
        FEATURE_CONTROLS_GRADE_VISIBILITY => false,
        FEATURE_PLAGIARISM => false,
        FEATURE_COMPLETION_HAS_RULES => true,
        FEATURE_NO_VIEW_LINK => false,
        FEATURE_IDNUMBER => false,
        FEATURE_GROUPS => true,
        FEATURE_GROUPINGS => false,
        FEATURE_MOD_ARCHETYPE => false,
        FEATURE_MOD_INTRO => false,
        FEATURE_MODEDIT_DEFAULT_COMPLETION => false,
        FEATURE_COMMENT => false,
        FEATURE_RATE => false,
        FEATURE_BACKUP_MOODLE2 => true,
        FEATURE_SHOW_DESCRIPTION => false,
    );
    if (!array_key_exists($feature, $support)) {
        return null;
    }
    return $support[$feature];
}

/**
 * Obtains the automatic completion state for this community wall based on any conditions
 * in community wall settings.
 *
 * @param object $course
 * @param object $cm
 * @param int $userid
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool whether completed
 */
function communitywall_get_completion_state($course, $cm, $userid, $type) {
    global $DB;
    $wall = $DB->get_record('communitywall', ['id' => $cm->instance], '*', MUST_EXIST);

    $notes_sql = <<< SQL
        SELECT wn.id, wn.note
        FROM {communitywall_note} wn
        INNER JOIN {communitywall_wall} cw ON cw.id = wn.wallid
        WHERE cw.instanceid = ?
            AND wn.userid = ?
            AND cw.userid != ?
SQL;
    $notes = $DB->get_records_sql($notes_sql, [$wall->id, $userid, $userid]);

    $walls_sql = <<<SQL
        SELECT cw.id, cw.title
        FROM {communitywall_wall} cw
        WHERE cw.instanceid = ?
            AND cw.userid = ?
SQL;
    $walls = $DB->get_records_sql($walls_sql, [$wall->id, $userid]);

    if ($type == COMPLETION_AND && $wall->completionpostonwall && $wall->completioncreatewall) {
        return !empty($notes) && !empty($walls);
    } else if ($type == COMPLETION_OR && $wall->completionpostonwall && $wall->completioncreatewall) {
        return !empty($notes) || !empty($walls);
    } else if ($wall->completionpostonwall) {
        return !empty($notes);
    } else if ($wall->completioncreatewall) {
        return !empty($walls);
    }
    return $type;
}
