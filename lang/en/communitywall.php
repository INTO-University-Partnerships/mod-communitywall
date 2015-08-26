<?php

defined('MOODLE_INTERNAL') || die();

// general
$string['modulename'] = 'Community wall';
$string['pluginname'] = 'Community wall';
$string['modulenameplural'] = 'Community walls';
$string['modulename_help'] = 'Allows course participants to add notes to a wall';
$string['communitywallname'] = 'Community wall name';
$string['invalidcommunitywallid'] = 'Community wall ID was incorrect';
$string['title'] = 'Title';
$string['created'] = 'Created';
$string['modified'] = 'Modified';
$string['noviewcapability'] = 'You cannot view this community wall';
$string['pluginadministration'] = 'Community wall administration';
$string['header'] = 'Header';
$string['footer'] = 'Footer';
$string['nowalls'] = 'There are no walls in this activity.';
$string['nonotes'] = 'There are no notes on this wall.';
$string['page'] = 'Page';
$string['of'] = 'Of';
$string['by'] = 'By';
$string['doubleclicktoedit'] = 'Double-click to edit note.';
$string['backtocommunitywalls'] = 'Back to Community Walls';
$string['doubleclick_to_post'] = 'Double click anywhere to post a note.';
$string['communitywallclosed'] = 'Community Walls cannot be added to a Community Wall activity that is closed';
$string['communitywallclosed_help'] = 'Community Walls cannot be added to a Community Wall activity that is closed';
$string['closed'] = 'Closed';
$string['activityclosed:user'] = 'This activity is closed. No Community Walls can be added.';
$string['activityclosed:admin'] = 'This activity is closed. No Community Walls can be added by students.';

// capabilities
$string['communitywall:addinstance'] = 'Add a new community wall';
$string['communitywall:view'] = 'View a community wall';

// JSON API
$string['jsonapi:notownerofwall'] = 'You are not the owner of this wall.';
$string['jsonapi:notownerofnote'] = 'You are not the owner of this note.';
$string['jsonapi:noteasguestdenied'] = 'You cannot post a note on a wall as the guest user';
$string['jsonapi:notemissing'] = 'You must add some text to this note.';
$string['jsonapi:coordinatesmissing'] = 'Your note is missing x and y coordinates.';
$string['jsonapi:notedoesntexist'] = 'This note does not exist.';
$string['jsonapi:walldoesntexist'] = ' This wall does not exist.';