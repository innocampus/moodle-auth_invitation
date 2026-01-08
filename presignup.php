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
 * Pre-signup information page for invited users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:ignoreFile moodle.Files.RequireLogin.Missing

require('../../config.php');

global $OUTPUT, $PAGE, $SITE;

$PAGE->set_url('/auth/invitation/presignup.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('acceptinvitation', 'auth_invitation'));
$PAGE->set_heading($SITE->fullname);

echo $OUTPUT->header();

$context = [
    'allowmismatchingemails' => get_config('enrol_invitation', 'allowmismatchingemails'),
    'signupurl' => new moodle_url('/login/signup.php'),
    'loginurl' => get_login_url(),
];
echo $OUTPUT->render_from_template('auth_invitation/presignup', $context);

echo $OUTPUT->footer();
