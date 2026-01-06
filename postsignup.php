<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Post-signup information page for invited users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

/** @var \core\output\core_renderer $OUTPUT */
global $OUTPUT;
global $PAGE, $SITE;

$token = required_param('invitationtoken', PARAM_ALPHANUM);

$PAGE->set_url(new moodle_url('/auth/invitation/postsignup.php', ['invitationtoken' => $token]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('signupcomplete', 'auth_invitation'));
$PAGE->set_heading($SITE->fullname);

require_login(autologinguest: false);

echo $OUTPUT->header();

$context = [
    'courseurl' => new moodle_url('/enrol/invitation/enrol.php', ['token' => $token]),
];
echo $OUTPUT->render_from_template('auth_invitation/postsignup', $context);

echo $OUTPUT->footer();
