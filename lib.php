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
        FEATURE_COMPLETION_HAS_RULES => false,
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
