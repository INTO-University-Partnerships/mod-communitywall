<?php

defined('MOODLE_INTERNAL') || die();

return <<<SQL
    FROM {communitywall_wall} cw
    INNER JOIN {user} u ON u.id = cw.userid AND u.deleted = 0
    WHERE cw.instanceid = :instanceid
SQL;
