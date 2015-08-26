<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

defined('MOODLE_INTERNAL') || die();

$controller = $app['controllers_factory'];

// form for adding a new wall
$controller->match('/{instanceid}/add', function (Request $request, $instanceid) use ($app) {
    global $CFG, $DB, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // ensure the user isn't the guest user
    if (isguestuser()) {
        return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('byinstanceid', array(
            'id' => $instanceid,
        )));
    }

    // get module context
    $context = context_module::instance($cm->id);

    // ensure the communitywall is not closed
    $closed = $DB->get_field('communitywall', 'closed', array('id' => $instanceid), MUST_EXIST) == 1;
    if (!$app['has_capability']('moodle/course:manageactivities', $context) && $closed) {
        return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('byinstanceid', array(
            'id' => $instanceid,
        )));
    }

    // create a form builder and populate a form with form elements
    $form = $app['form.factory']->createBuilder('form', null, array('csrf_protection' => false))
        ->add('title', 'text', array(
            'label' => get_string('title', $app['plugin']),
            'required' => true,
            'constraints' => new Symfony\Component\Validator\Constraints\Length(array(
                'max' => 100,
            )),
        ))
        ->getForm();

    // handle form submission
    if ('POST' == $request->getMethod()) {
        require_sesskey();
        $form->bind($request);
        if ($form->isValid()) {
            $data = $form->getData();

            // fill in the blanks
            $data['instanceid'] = $instanceid;
            $data['userid'] = $USER->id;

            // create wall model and save the wall
            require_once __DIR__ . '/../models/communitywall_model.php';
            $communitywall_model = new communitywall_model();
            $data = $communitywall_model->save($data, $app['now']());

            // redirect to the new wall itself
            return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('wall', array(
                'id' => $data['id'],
            )));
        }
    }

    // render
    return $app['twig']->render('add.twig', array(
        'cm' => $cm,
        'form' => $form->createView(),
        'instanceid' => $instanceid,
        'sesskey' => $USER->sesskey,
    ));
})
->bind('add')
->assert('instanceid', '\d+');

// form for editing an existing wall
$controller->match('/{instanceid}/edit/{id}', function (Request $request, $instanceid, $id) use ($app) {
    global $CFG, $USER;

    // require course login
    list($course, $cm) = $app['get_course_and_course_module']($instanceid);
    $app['require_course_login']($course, $cm);

    // get module context
    $context = context_module::instance($cm->id);

    // load the wall
    require_once __DIR__ . '/../models/communitywall_model.php';
    $communitywall_model = new communitywall_model();
    $wall = $communitywall_model->get($id);

    // check that the logged in user can either manage activities or is the owner of the wall
    if (!$app['has_capability']('moodle/course:manageactivities', $context) && ($USER->id != $wall['userid'])) {
        return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('wall', array(
            'id' => $id,
        )));
    }

    // create a form builder and populate a form with form elements
    $form = $app['form.factory']->createBuilder('form', null, array('csrf_protection' => false))
        ->add('title', 'text', array(
            'label' => get_string('title', $app['plugin']),
            'required' => true,
            'constraints' => new Symfony\Component\Validator\Constraints\Length(array(
                    'max' => 100,
                )),
            'data' => $wall['title'],
        ))
        ->getForm();

    // handle form submission
    if ('POST' == $request->getMethod()) {
        require_sesskey();
        $form->bind($request);
        if ($form->isValid()) {
            $data = $form->getData();

            // fill in the blanks
            $data['instanceid'] = $instanceid;
            $data['id'] = $id;

            // save the wall
            $communitywall_model->save($data, $app['now']());

            // redirect to the wall itself
            return $app->redirect($CFG->wwwroot . SLUG . $app['url_generator']->generate('wall', array(
                'id' => $id,
            )));
        }
    }

    // render
    return $app['twig']->render('edit.twig', array(
        'cm' => $cm,
        'form' => $form->createView(),
        'instanceid' => $instanceid,
        'id' => $id,
        'sesskey' => $USER->sesskey,
    ));
})
->bind('edit')
->assert('instanceid', '\d+')
->assert('id', '\d+');

// return the controller
return $controller;
