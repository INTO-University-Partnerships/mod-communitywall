<?php

defined('MOODLE_INTERNAL') || die();

require_once __DIR__ . '/../models/communitywall_note_model.php';

$app['get_all_notes'] = $app->protect(function (communitywall_note_model $communitywall_note_model, $wallid) {
    global $OUTPUT;

    $total = $communitywall_note_model->get_total_by_wallid($wallid);
    $notes = $communitywall_note_model->get_all_by_wallid($wallid);

    foreach ($notes as $key => $note) {
        // Convert URLs without http (but with www.) to have http://
        $notes[$key]['note'] = preg_replace('#(^|\s)(www.[^\s]*)#', "$1http://$2", $note['note']);

        // Create userpicture
        $notes[$key]['userpicture'] = $OUTPUT->user_picture((object)[
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
        ], [
            'size' => 40,
            'link' => false
        ]);
        foreach (['picture', 'firstname', 'lastname', 'email', 'firstnamephonetic', 'lastnamephonetic', 'middlename', 'alternatename'] as $exclude_from_json) {
            unset($notes[$key][$exclude_from_json]);
        }
    }

    return [
        'notes' => $notes,
        'total' => $total,
    ];
});
