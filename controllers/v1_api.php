<?php

use Symfony\Component\HttpFoundation\Request;

defined('MOODLE_INTERNAL') || die();

if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

$controller = $app['controllers_factory'];

// get all walls
$controller->get('/wall/{instanceid}', function (Request $request, $instanceid) use ($app) {
    global $USER;

    // get pagination parameters
    $limitfrom = (integer)$request->get('limitfrom');
    $limitnum = (integer)$request->get('limitnum');

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // determine whether the user can manage
    $context = context_module::instance($cm->id);
    $can_manage = $app['has_capability']('moodle/course:manageactivities', $context);

    // determine whether the user can view all walls (or only 'viewable' walls)
    require_once __DIR__ . '/../models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $communitywall_model->set_userid($USER->id);
    if (!$can_manage) {
        $communitywall_model->set_groupmode($app['get_groupmode']($course->id, $cm->id));
    }
    $f = $can_manage ? 'get_total_by_instanceid' : 'get_total_viewable_by_instanceid';
    $g = $can_manage ? 'get_all_by_instanceid' : 'get_all_viewable_by_instanceid';

    // count and fetch walls and return JSON response
    $total = $communitywall_model->$f($instanceid);
    $walls = $communitywall_model->$g($instanceid, $limitfrom, $limitnum);
    return $app->json((object)array(
        'walls' => $walls,
        'total' => $total,
    ));
})
->bind('all')
->assert('instanceid', '\d+');

// get one wall
$controller->get('/wall/{instanceid}/{wallid}', function ($instanceid, $wallid) use ($app) {
    global $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json('', 404);
    }

    // create communitywall model
    require_once __DIR__ . '/../models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $communitywall_model->set_userid($USER->id);

    // fetch wall and return JSON response
    $wall = $communitywall_model->get($wallid);
    return $app->json($wall);
})
->assert('instanceid', '\d+')
->assert('wallid', '\d+');

// delete one wall
$controller->delete('/wall/{instanceid}/{wallid}', function ($instanceid, $wallid) use ($app) {
    global $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json('', 404);
    }

    // create communitywall model and get wall
    require_once __DIR__ . '/../models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $wall = $communitywall_model->get($wallid);

    // get module context
    $context = context_module::instance($cm->id);

    // check that the logged in user can either manage activities or is the owner of the wall
    if (!$app['has_capability']('moodle/course:manageactivities', $context) && ($USER->id != $wall['userid'])) {
        return $app->json(get_string('jsonapi:notownerofwall', $app['plugin']), 403);
    }

    // delete wall
    $communitywall_model->delete($wallid);
    return $app->json('', 204);
})
->assert('instanceid', '\d+')
->assert('wallid', '\d+');

// get all notes
$controller->get('/wall/{instanceid}/{wallid}/note', function(Request $request, $instanceid, $wallid) use ($app) {
    global $DB, $USER, $OUTPUT;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json(get_string('jsonapi:walldoesntexist', $app['plugin']), 404);
    }

    // create communitywall_note model and save note
    require_once __DIR__ . '/../models/communitywall_note_model.php';
    $communitywall_note_model = new communitywall_note_model();
    $communitywall_note_model->set_userid($USER->id);

    $total = $communitywall_note_model->get_total_by_wallid($wallid);
    $notes = $communitywall_note_model->get_all_by_wallid($wallid);

    foreach($notes as $key => $note) {
        // Convert URLs without http (but with www.) to have http://
        $notes[$key]['note'] = preg_replace('#(^|\s)(www.[^\s]*)#', "$1http://$2", $note['note']);
        // Create userpicture
        $notes[$key]['userpicture'] = $OUTPUT->user_picture((object)array(
            'id' => $note['userid'],
            'picture' => $note['picture'],
            'firstname' => $note['firstname'],
            'lastname' => $note['lastname'],
            'firstnamephonetic' => $note['firstnamephonetic'],
            'lastnamephonetic' => $note['lastnamephonetic'],
            'middlename' => $note['middlename'],
            'alternatename' => $note['alternatename'],
            'imagealt' => $note['userfullname'],
            'email' => $note['email']
        ), array(
            'size' => 40,
            'link' => false
        ));
        foreach (array('picture', 'firstname', 'lastname', 'email', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename') as $exclude_from_json) {
            unset($notes[$key][$exclude_from_json]);
        }
    }

    // return JSON response
    return $app->json((object)array(
        'notes' => $notes,
        'total' => $total
    ));
})
    ->assert('instanceid', '\d+')
    ->assert('wallid', '\d+');

// create one note
$controller->post('/wall/{instanceid}/{wallid}/note', function(Request $request, $instanceid, $wallid) use ($app) {
    global $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json(get_string('jsonapi:walldoesntexist', $app['plugin']), 404);
    }

    // ensure the user isn't the guest user
    if ($app['is_guest_user']()) {
        return $app->json(get_string('jsonapi:noteasguestdenied', $app['plugin']), 400);
    }

    // create communitywall_note model and save note
    require_once __DIR__ . '/../models/communitywall_note_model.php';
    $communitywall_note_model = new communitywall_note_model();
    $communitywall_note_model->set_userid($USER->id);

    $uploaded = (array)json_decode($request->getContent());
    if (!array_key_exists('note', $uploaded)) {
        return $app->json(get_string('jsonapi:notemissing', $app['plugin']), 400);
    }

    if (!array_key_exists('xcoord', $uploaded) || !array_key_exists('ycoord', $uploaded)) {
        return $app->json(get_string('jsonapi:coordinatesmissing', $app['plugin']), 400);
    }

    $data = array(
        'wallid' => $wallid,
        'userid' => $USER->id,
        'note' => $uploaded['note'],
        'xcoord' => $uploaded['xcoord'],
        'ycoord' => $uploaded['ycoord']
    );

    $communitywall_note_model->save($data, $app['now']());

    return $app->json('', 201);
})
    ->assert('instanceid', '\d+')
    ->assert('wallid', '\d+');

// update one note
$controller->put('/wall/{instanceid}/{wallid}/note/{noteid}', function(Request $request, $instanceid, $wallid, $noteid) use($app) {
    global $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json(get_string('jsonapi:walldoesntexist', $app['plugin']), 404);
    }

    // see whether the note actually exists
    if (!$DB->record_exists('communitywall_note', array(
        'wallid' => $wallid,
        'id' => $noteid,
    ))) {
        return $app->json(get_string('jsonapi:notedoesntexist', $app['plugin']), 404);
    }

    require_once __DIR__ . '/../models/communitywall_note_model.php';
    $communitywall_note_model = new communitywall_note_model();
    $communitywall_note_model->set_userid($USER->id);
    $note = $communitywall_note_model->get($noteid);

    // get module context
    $context = context_module::instance($cm->id);

    // Check that the logged in user can either manage the activities or is the owner of the note
    if (!$app['has_capability']('moodle/course:manageactivities', $context) && ($USER->id != $note['userid'])) {
        return $app->json(get_string('jsonapi:notownerofnote', $app['plugin']), 403);
    }

    $uploaded = (array)json_decode($request->getContent());
    if (!array_key_exists('note', $uploaded)) {
        return $app->json(get_string('jsonapi:notemissing', $app['plugin']), 400);
    }

    if (!array_key_exists('xcoord', $uploaded) || !array_key_exists('ycoord', $uploaded)) {
        return $app->json(get_string('jsonapi:coordinatesmissing', $app['plugin']), 400);
    }

    $data = array(
        'id' => $noteid,
        'note' => $uploaded['note'],
        'xcoord' => $uploaded['xcoord'],
        'ycoord' => $uploaded['ycoord']
    );

    $communitywall_note_model->save($data, $app['now']());

    return $app->json('', 200);
})
    ->assert('instanceid', '\d+')
    ->assert('wallid', '\d+')
    ->assert('noteid', '\d+');

// delete one note
$controller->delete('/wall/{instanceid}/{wallid}/note/{noteid}', function(Request $request, $instanceid, $wallid, $noteid) use($app) {
    global $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // see whether the wall actually exists
    if (!$DB->record_exists('communitywall_wall', array(
        'instanceid' => $instanceid,
        'id' => $wallid,
    ))) {
        return $app->json(get_string('jsonapi:walldoesntexist', $app['plugin']), 404);
    }

    // see whether the note actually exists
    if (!$DB->record_exists('communitywall_note', array(
        'wallid' => $wallid,
        'id' => $noteid,
    ))) {
        return $app->json(get_string('jsonapi:notedoesntexist', $app['plugin']), 404);
    }

    require_once __DIR__ . '/../models/communitywall_note_model.php';
    $communitywall_note_model = new communitywall_note_model();
    $note = $communitywall_note_model->get($noteid);

    // get module context
    $context = context_module::instance($cm->id);

    // Check that the logged in user can either manage the activities or is the owner of the note
    if (!$app['has_capability']('moodle/course:manageactivities', $context) && ($USER->id != $note['userid'])) {
        return $app->json(get_string('jsonapi:notownerofnote', $app['plugin']), 403);
    }

    $communitywall_note_model->delete($noteid);

    return $app->json('', 204);
})
    ->assert('instanceid', '\d+')
    ->assert('wallid', '\d+')
    ->assert('noteid', '\d+');
// return the controller
return $controller;
