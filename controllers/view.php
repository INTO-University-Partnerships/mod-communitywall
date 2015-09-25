<?php

defined('MOODLE_INTERNAL') || die();

$controller = $app['controllers_factory'];

// view the given activity
$controller->get('/cmid/{cmid}', function ($cmid) use ($app) {
    global $CFG, $DB;

    // get instanceid
    $instanceid = (integer)$DB->get_field('course_modules', 'instance', array(
        'id' => $cmid,
    ), MUST_EXIST);

    // redirect
    return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('byinstanceid', array(
        'id' => $instanceid,
    )));
})
->bind('bycmid')
->assert('cmid', '\d+');

// view the given activity
$controller->get('/{id}', function ($id) use ($app) {
    global $CFG, $DB;

    // get module id from modules table
    $moduleid = (integer)$DB->get_field('modules', 'id', array(
        'name' => $app['module_table'],
    ), MUST_EXIST);

    // get instance
    $instance = $DB->get_record($app['module_table'], array(
        'id' => $id,
    ), '*', MUST_EXIST);

    // get course module
    $cm = $DB->get_record('course_modules', array(
        'module' => $moduleid,
        'instance' => $id,
    ), '*', MUST_EXIST);

    // get course
    $course = $DB->get_record('course', array(
        'id' => $cm->course,
    ), '*', MUST_EXIST);

    // require course login
    $app['require_course_login']($course, $cm);

    // get module context
    $context = context_module::instance($cm->id);

    // log it
    $app['course_module_viewed']($cm, $instance, $course, $context);

    // mark viewed
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);

    // get module context
    $context = context_module::instance($cm->id);

    // set heading and title
    $app['heading_and_title']($course->fullname, $instance->name);

    // render
    return $app['twig']->render('walls.twig', array(
        'baseurl' => $CFG->wwwroot . SLUG,
        'cm' => $cm,
        'api' => '/api/v1',
        'instance' => $instance,
        'course' => $course,
        'can_manage' => $app['has_capability']('moodle/course:manageactivities', $context),
        'is_guest' => $app['is_guest_user'](),
    ));
})
->bind('byinstanceid')
->assert('id', '\d+');

// view a wall
$controller->get('/wall/{id}', function ($id) use ($app) {
    global $CFG, $DB, $USER;

    // Get wall
    $wall = $DB->get_record('communitywall_wall', array(
        'id' => $id,
    ), '*', MUST_EXIST);

    // get instance
    $instance = $DB->get_record($app['module_table'], array(
        'id' => $wall->instanceid,
    ), '*', MUST_EXIST);

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instance->id);
    $app['require_course_login']($course, $cm);

    // determine whether the logged in user can manage
    $context = context_module::instance($cm->id);
    $can_manage = $app['has_capability']('moodle/course:manageactivities', $context);

    // load the wall (taking group mode into consideration if the logged in user can't manage activities)
    require_once __DIR__ . '/../models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $communitywall_model->set_userid($USER->id);
    if (!$can_manage) {
        $communitywall_model->set_groupmode($app['get_groupmode']($course->id, $cm->id));
    }
    $wall = $communitywall_model->get($id);

    // get module context
    $context = context_module::instance($cm->id);

    // set heading and title
    $instance_title = sprintf(
        '%s %s %s',
        $wall['title'],
        strtolower(get_string('by', $app['plugin'])),
        $wall['userfullname']
    );
    $app['heading_and_title']($course->fullname, $instance_title);

    return $app['twig']->render('view.twig', array(
        'baseurl' => $CFG->wwwroot . SLUG,
        'cm' => $cm,
        'api' => '/api/v1',
        'wall' => $wall,
        'course' => $course,
        'can_manage' => $app['has_capability']('moodle/course:manageactivities', $context),
        'is_guest' => $app['is_guest_user'](),
        'instance' => $instance,
        'instance_title' => $instance_title,
    ));
})
->bind('wall')
->assert('id', '\d+');

// return the controller
return $controller;
