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
 * Strings for component 'auth_invitation', language 'en'.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['allowedemailregex'] = 'Regex for allowed email addresses';
$string['allowedemailregex_help'] = 'Specify a regular expression here which must appear in the email address (in lower case) of an invited person to allow that person to register using this plugin. The setting auth_invitation/prohibitedemailregex takes precedence over this. If this field is left empty, all email addresses are allowed.';
$string['alreadyregistered'] = 'Ich habe bereits ein Nutzerkonto';
$string['assignedroles'] = 'Roles assigned to new users';
$string['assignedroles_help'] = 'Roles selected here are automatically assigned in the system context to all users who register using this plugin. This can be used to assign a role to invited users which distinguishes them from other users and prohibits certain permissions (e.g. self-enrolment into courses).';
$string['description'] = 'Users can only register using this plugin when they have been invited to a course using the enrolment method "Invitation" (enrol_invitation).';
$string['generateusername'] = 'Automatically generate usernames';
$string['generateusername_help'] = 'Activate this setting to automatically generate and assign a username to newly registered users. When this option is active, user do not need to specify a username in the sign up form. When it is not active, they may freely choose a username.<br> <strong>When this setting is active, users are automatically assigned a username, but they do not know it and thus cannot log in using it. Therefore, please make sure to allow the login via email address in this case by activating the system setting authloginviaemail.</strong>';
$string['invalidinvite'] = 'This invitation is expired or has already been used.';
$string['pluginname'] = 'Invitation';
$string['privacy:metadata'] = 'The Invitation authentication plugin does not store any personal data.';
$string['prohibitedemailregex'] = 'Regex for prohibited email addresses';
$string['prohibitedemailregex_help'] = 'Specify a regular expression here which must NOT appear in the email address (in lower case) of an invited person to allow that person to register using this plugin. This takes precendence over the setting auth_invitation/allowedemailregex. If this field is left empty, all email addresses are allowed.';
$string['registerhere'] = '<p>You have been invited to a course on this site, but you do not seem to have a user account, yet. Please fill out the information below to receive a temporary account which you can then use to access the course.</p><p><b>If you already have an account, please contact the course organizers to receive a new invitation for your existing account.</b></p>';
$string['signupsettings'] = 'Settings for the sign up form';
$string['signupsettingsdesc'] = 'Specify which profile data users can and must provide in the sign up form.';
$string['usernameprefix'] = 'Prefix for generated usernames';
$string['usernameprefix_help'] = 'The prefix specified here is automatically prepended to generated usernames. The final username consists of this prefix followed by a randomly generated number.';
