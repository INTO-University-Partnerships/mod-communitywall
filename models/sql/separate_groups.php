<?php

defined('MOODLE_INTERNAL') || die();

$subquery = require __DIR__ . '/separate_groups_subquery.php';

return <<<SQL
    FROM {communitywall_wall} cw
    INNER JOIN {communitywall} w ON cw.instanceid = w.id
    INNER JOIN {user} u ON u.id = cw.userid AND u.deleted = 0
    LEFT JOIN ({$subquery}) g ON g.userid = cw.userid AND g.courseid = w.course
    WHERE cw.instanceid = :instanceid
        AND (cw.userid = :userid2 OR g.userid IS NOT NULL)
SQL;
