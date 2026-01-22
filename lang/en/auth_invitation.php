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

$string['acceptinvitation'] = 'Accept invitation';
$string['accountcreatedsuccessfully'] = 'Your account was created successfully. You can log in with your chosen credentials using the regular login form in the future.';
$string['accountdeleted'] = 'Hi {$a->firstname},

Your account at \'{$a->sitefullname}\' was deleted automatically due to inactivity.

If this was done in error, please contact us immediately by responding to this email. Otherwise, you can ignore this email.

Sincerely,
{$a->admin}';
$string['accountdeletedsubject'] = '{$a->sitefullname}: account deleted';
$string['accountdeletionnotice'] = 'Hi {$a->firstname},

Your account at \'{$a->sitefullname}\' will be deleted automatically if you do not log in to the site until {$a->deletionafter}.

Note that you will not be able to access your account after this date. This includes any courses you may be enrolled in as well as your submissions and grades in these courses.

To stop your account from being deleted, please log in here until {$a->deletionafter}:

{$a->loginurl}

If you need help, please contact the site administrator,
{$a->admin}';
$string['accountdeletionnoticesubject'] = '{$a->sitefullname}: account deletion in {$a->deletionindays} days';
$string['allowedemailpatterns'] = 'Allowed email addresses';
$string['allowedemailpatterns_help'] = 'Specify all email addresses for which signing up using this plugin is allowed. Each row must contain one email address or a pattern matching multiple email addresses. You can use the wildcards <code>*</code> (any string) and <code>?</code> (any character) as well as character classes in the format <code>[0-9]</code> (any digit), <code>[a-z]</code> (a character in the range a-z), <code>[!abc]</code> (any character <i>except</i> a, b, and c). Special characters can be escaped using <code>\</code>. Email addresses are converted to lower case before comparison. The setting auth_invitation/prohibitedemailpatterns takes precedence over this.';
$string['alreadyregistered'] = 'I already have an account';
$string['assignedroles'] = 'Roles assigned to new users';
$string['assignedroles_help'] = 'Roles selected here are automatically assigned in the system context to all users who sign up using this plugin. This can be used to assign a role to invited users which distinguishes them from other users and prohibits certain capabilities (e.g. self-enrolment into courses).';
$string['autodeleteusers'] = 'Auto-delete inactive users';
$string['autodeleteusers_help'] = 'Specify whether users who have signed up using this plugin should automatically be deleted if they do not access the site for a predefined number of days.';
$string['autodeleteusersafterdays'] = 'Auto-delete after (days)';
$string['autodeleteusersafterdays_help'] = 'Number of days which must have passed since a user\'s last access to the site for the user to be automatically deleted.';
$string['autodeleteusersnoticedays'] = 'Notification time (days)';
$string['autodeleteusersnoticedays_help'] = 'Number of days before deletion at which the user is notified via email about the pending deletion of their account. Set to 0 to disable notifications.';
$string['confirmpassword'] = 'Confirm password';
$string['confirmpasswordonsignup'] = 'Password confirmation required';
$string['confirmpasswordonsignup_help'] = 'Specify whether the password needs to be entered a second time for confirmation in the signup form.';
$string['coursenowavailable'] = 'You may now proceed to the course to which you were invited.';
$string['createnewaccount'] = 'Create new account';
$string['deleteinactiveusers'] = 'Delete inactive users';
$string['description'] = 'Users can only sign up using this plugin if they have been invited to a course on this site using the enrolment method "Invitation" (enrol_invitation).';
$string['generateusername'] = 'Automatically generate usernames';
$string['generateusername_help'] = 'Activate this setting to automatically generate and assign a username to newly registered users. When this option is active, user do not need to specify a username in the sign up form. When it is not active, they may freely choose a username.<br> <strong>When this setting is active, users are automatically assigned a username, but they do not know it and thus cannot log in using it. Therefore, please make sure to allow the login via email address in this case by activating the system setting authloginviaemail.</strong>';
$string['hidecityfieldonsignup'] = 'Hide profile field "City"';
$string['hidecityfieldonsignup_help'] = 'When this option is active, the profile field "City" is hidden in the sign up form.';
$string['hidecountryfieldonsignup'] = 'Hide profile field "Country"';
$string['hidecountryfieldonsignup_help'] = 'When this option is active, the profile field "Country" is hidden in the sign up form.';
$string['invalidinvite'] = 'This invitation is expired or has already been used. Self-registration is only possible with a valid invitation to a course on this site.';
$string['loginprohibitedbyemail'] = 'Login is not possible because your email address is listed as prohibited. Please try a different login method or contact the site administrators if you think this is an error.';
$string['mismatchingpasswords'] = 'Passwords do not match';
$string['pluginname'] = 'Invitation-based self-registration';
$string['privacy:metadata'] = 'The Invitation-based self-registration authentication plugin does not store any personal data.';
$string['proceedtocourse'] = 'Proceed to course';
$string['prohibitedemailloginerror'] = 'Error on login with prohibited email address';
$string['prohibitedemailloginerror_help'] = 'Specify whether a special error message should be displayed when a login attempt using a prohibited email address fails. <b>This only works for non-existent users or when the system setting authloginviaemail is deactivated.</b> The error message may for example be used to explain the correct login process to users who have access via an IdP. The contents of the error message can be changed by customizing the language string <b>loginprohibitedbyemail</b> of the <b>auth_invitation</b> plugin.';
$string['prohibitedemailpatterns'] = 'Prohibited email addresses';
$string['prohibitedemailpatterns_help'] = 'Specify all email addresses for which signing up using this plugin is <i>not</i> allowed. This uses the same syntax as auth_invitation/allowedemailpatterns and takes precendence over that setting. This setting can be used to define exceptions for the patterns defined in auth_invitation/allowedemailpatterns';
$string['redirecttosignup'] = 'Automatically redirect to signup';
$string['redirecttosignup_help'] = 'Specify whether invited users without an account should automatically be redirected to the signup form instead of the login form. Users with email addresses for which self registration is not allowed are exempted from this.';
$string['registereduserscontactteachers'] = 'If you already have an account with a different email address, please contact the course organizers to receive a new invitation for your existing account.';
$string['registeredusersloginhere'] = 'If you already have an account with a different email address, please instead use the button "I already have an account" to log in.';
$string['sendwelcomeemail'] = 'Send welcome email';
$string['sendwelcomeemail_help'] = 'Specify whether a welcome email should be sent to newly registered users. The contents of this email can be changed by customizing the language strings <b>welcomeemail</b> and <b>welcomeemailsubject</b> of the <b>auth_invitation</b> plugin.';
$string['signupaccountexists'] = 'There already exists a user account for this invitation or email address. Please log in normally.';
$string['signupcomplete'] = 'Signup complete';
$string['signuphere'] = 'You have been invited to a course on this site, but you do not seem to have a user account, yet. Please choose "Create new account" to create an account which you can then use to access the course.';
$string['signuponlywithinvite'] = 'Self-registration is only possible with an invitation to a course on this site. Please contact your teachers to receive an invitation.';
$string['signupprohibitedbyemail'] = 'Self-registration not allowed for this invitation because your email address is listed as prohibited. Please try to log in normally or contact the site administrators if you think this is an error.';
$string['signupsettings'] = 'Settings for the sign up form';
$string['signupsettingsdesc'] = 'Specify which profile data users can and must provide in the sign up form.';
$string['usernameprefix'] = 'Prefix for generated usernames';
$string['usernameprefix_help'] = 'The prefix specified here is prepended to generated usernames. The final username consists of this prefix followed by a randomly generated number.';
$string['welcomeemail'] = 'Hi {$a->firstname},

Welcome to \'{$a->sitefullname}\'!

Your account has been created successfully. If you have not already done so, you may now access the course which you were invited to by following the link in the invitation email.

Please note that the invitation link can only be used once. To access the course in the future, please log in here using your chosen credentials and select the course from the list on the \'My courses\' page:

{$a->loginurl}

If you need help, please contact the site administrator,
{$a->admin}';
$string['welcomeemailsubject'] = '{$a->sitefullname}: account created';
