<?php

defined('MOODLE_INTERNAL') || die();

$app['is_guest_user'] = $app->protect(function () {
    return isguestuser();
});
