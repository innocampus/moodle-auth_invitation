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
 * Auth plugin for users that were invited using enrol_invitation.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use auth_invitation\email_helper;
use Random\RandomException;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Auth plugin for users that were invited using enrol_invitation.
 *
 * Users can only register using this plugin when they have been invited to a course using enrol_invitation.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_invitation extends auth_plugin_base {
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'invitation';
        $this->config = get_config('auth_invitation');
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     * @throws dml_exception
     */
    public function user_login($username, $password): bool {
        global $CFG, $DB;
        if ($user = $DB->get_record('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
            return validate_internal_user_password($user, $password);
        }
        return false;
    }

    /**
     * Updates the user's password.
     *
     * Called when the user password is updated.
     *
     * @param object $user User table object (with system magic quotes)
     * @param string $newpassword Plaintext password (with system magic quotes)
     * @return boolean result
     *
     * @throws dml_exception
     */
    public function user_update_password($user, $newpassword): bool {
        $user = get_complete_user_data('id', $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Returns true if plugin allows user signup.
     *
     * @return true
     */
    public function can_signup(): true {
        return true;
    }

    /**
     * Sign up a new invited user without confirmation.
     * Password is passed in plaintext.
     *
     * This requires `$user->invitationtoken` to be set to a valid invitation token. `$user->email` must match that invitation.
     *
     * @param object $user new user object
     * @param boolean $notify print notice with link and terminate
     * @throws moodle_exception
     * @throws RandomException
     */
    public function user_signup($user, $notify = true): bool {
        global $CFG;

        // Validate invitation token.
        if (empty($user->invitationtoken)) {
            throw new coding_exception('Missing invitationtoken in user data from signup form.');
        }
        $invite = $this->validate_signup_prerequisites($user->invitationtoken);
        if (strtolower($invite->email) !== strtolower($user->email)) {
            throw new coding_exception('Email in user data from signup form does not match email in invitation.');
        }

        // We can confirm the user since the invitation token is valid (and matches their email address).
        $user->confirmed = 1;

        // Create user account.
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $plainpassword = $user->password;
        $user->password = hash_internal_user_password($user->password);
        if (empty($user->calendartype)) {
            $user->calendartype = $CFG->calendartype;
        }

        $user->id = user_create_user($user, false, false);

        user_add_password_history($user->id, $plainpassword);

        // Save any custom profile field information.
        profile_save_data($user);

        // Assign global roles.
        $this->assign_global_roles($user->id);

        // Trigger event.
        \core\event\user_created::create_from_userid($user->id)->trigger();

        if (get_config('auth_invitation', 'sendwelcomeemail')) {
            // Send welcome email.
            $this->send_welcome_email($user);
        }

        if ($notify) {
            // Log in newly created user.
            $user = get_complete_user_data('username', $user->username);
            complete_user_login($user);

            \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

            // Redirect to post-signup page.
            redirect(new moodle_url('/auth/invitation/postsignup.php', ['invitationtoken' => $invite->token]));
        }

        return true;
    }

    /**
     * Assigns global roles selected in the auth_invitation/assignedroles setting to a newly registered user.
     *
     * @param int $userid The user ID.
     * @return void
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function assign_global_roles(int $userid): void {
        $roles = get_config('auth_invitation', 'assignedroles');
        foreach (explode(',', $roles) as $roleid) {
            $roleid = trim($roleid);
            if (empty($roleid)) {
                continue;
            }

            // The function just returns when the user already has this role.
            \role_assign($roleid, $userid, \context_system::instance(), 'auth_invitation');
        }
    }

    /**
     * Send a welcome email to a user who has just signed up.
     *
     * @param stdClass $user
     * @throws moodle_exception
     */
    protected function send_welcome_email(stdClass $user): void {
        $data = email_helper::get_common_email_data($user);
        $data->loginurl = get_login_url();
        email_helper::send_localized_email($user, 'welcomeemailsubject', 'welcomeemail', $data);
    }

    /**
     * Get valid (i.e., unused and not expired) invitation record from token.
     *
     * @param string $token
     * @return stdClass|null Invitation record or null if not found or not valid.
     * @throws dml_exception
     */
    public function get_valid_invitation(string $token): ?stdClass {
        global $DB;
        if (!enrol_get_plugin('invitation')) {
            return null;
        }
        return $DB->get_record_select(
            'enrol_invitation',
            'token = :token AND tokenused = 0 AND timeexpiration >= :time',
            [
                'token' => $token,
                'time' => time(),
            ]
        ) ?: null;
    }

    /**
     * Get the current invitation token from $SESSION->wantsurl.
     *
     * @return string|null Invitation record or null if no invitation found in session.
     * @throws moodle_exception
     * @throws dml_exception
     */
    protected function get_invitation_token_from_session(): ?string {
        global $SESSION;
        if (empty($SESSION->wantsurl)) {
            return null;
        }
        $wantsurl = new moodle_url($SESSION->wantsurl);
        // Check whether $wantsurl matches /enrol/invitation/enrol.php.
        $enrolurl = new moodle_url('/enrol/invitation/enrol.php');
        if ($wantsurl->get_path() !== $enrolurl->get_path()) {
            return null;
        }
        // Verify this is not a rejection URL.
        if ($wantsurl->param('reject')) {
            return null;
        }
        // Return invitation token if it is a string.
        $token = $wantsurl->param('token');
        if (is_string($token)) {
            return $token;
        }
        return null;
    }

    /**
     * Return a form to capture user details for account creation. This is used in /login/signup.php.
     *
     * Only users who were invited to a course using enrol_invitation can use this form to sign up.
     *
     * @param string|null $invitationtoken The user's invitation token. Is automatically determined from $SESSION->wantsurl if not
     * provided.
     * @return moodleform A form which edits a record from the user table.
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function signup_form(?string $invitationtoken = null): moodleform {
        $token = $invitationtoken ?: $this->get_invitation_token_from_session();
        $invite = $this->validate_signup_prerequisites($token);
        $customdata = [
            'invitationtoken' => $invite->token,
            'email' => $invite->email,
        ];
        return new \auth_invitation\forms\login_signup_form(null, $customdata, 'post', '', ['autocomplete' => 'on']);
    }

    /**
     * Hook called by require_login before redirecting to the login page.
     *
     * This automatically redirects the user to the signup form if all conditions are met.
     *
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function pre_loginpage_hook(): void {
        global $CFG;
        if ($CFG->registerauth !== $this->authtype) {
            // This plugin is not selected for self registration -> show normal login form.
            return;
        }
        if (!get_config('auth_invitation', 'redirecttosignup')) {
            // Automatic redirection to signup is disabled -> show normal login form.
            return;
        }
        $token = $this->get_invitation_token_from_session();
        try {
            $this->validate_signup_prerequisites($token);
        } catch (dml_exception $e) {
            throw $e; // Always rethrow database errors.
        } catch (moodle_exception $e) {
            // Invitation token cannot be used for signup -> show normal login form.
            return;
        }
        // Invited user does not exist in DB -> redirect to pre-signup page.
        redirect(new moodle_url('/auth/invitation/presignup.php'));
    }

    /**
     * Called from /login/signup.php via {@see auth_invitation_pre_signup_requests()} before showing the signup form.
     *
     * tool_policy uses the same pre_signup_requests callback to redirect to the policy acceptance pages
     * (see {@see tool_policy_pre_signup_requests()}). We want to prevent this if we already know signup is not possible
     * to improve user experience (seeing an error message only AFTER accepting the site policies is bad UX).
     * Fortunately, our callback is called before tool_policy due to the component loading order.
     *
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function pre_signup_hook(): void {
        global $CFG;
        if ($CFG->registerauth !== $this->authtype) {
            // This plugin is not selected for self registration.
            return;
        }
        $token = $this->get_invitation_token_from_session();
        $this->validate_signup_prerequisites($token);
        // All prerequisites met, signup process can commence with policy acceptance.
    }

    /**
     * Validates whether an invitation token is valid for signup. Returns the invitation record if it is or throws an
     * appropriate exception otherwise.
     *
     * @param string|null $token Invitation token, or null if none is available (this will always throw an exception).
     * @return stdClass The invitation record valid for signup.
     * @throws moodle_exception
     * @throws dml_exception
     */
    protected function validate_signup_prerequisites(?string $token): stdClass {
        if (!$token) {
            throw new moodle_exception('signuponlywithinvite', 'auth_invitation');
        }
        $invite = $this->get_valid_invitation($token);
        if (!$invite) {
            throw new moodle_exception('invalidinvite', 'auth_invitation');
        }
        if ($invite->userid) {
            throw new moodle_exception('signupaccountexists', 'auth_invitation');
        }
        if (!$this->is_allowed_email($invite->email)) {
            throw new moodle_exception('signupprohibitedbyemail', 'auth_invitation');
        }
        if ($this->get_user_by_email($invite->email)) {
            throw new moodle_exception('signupaccountexists', 'auth_invitation');
        }
        return $invite;
    }

    /**
     * Checks whether self-registration using this plugin is allowed for the user with the specified email address.
     *
     * This first checks whether the email address is prohibited by the setting auth_invitation/prohibitedemailpatterns and then
     * whether it is allowed by the setting auth_invitation/allowedemailpatterns.
     *
     * The function {@see fnmatch} is used to compare the provided email (in lower case) to the patterns specified in the settings.
     *
     * @param string $email The email address of the invited user.
     * @return bool Whether self-registration using this plugin is allowed.
     * @throws dml_exception
     */
    protected function is_allowed_email(string $email): bool {
        $config = get_config('auth_invitation');
        $email = strtolower($email);
        $splitpatterns = fn($patterns) => array_filter(array_map('trim', explode("\n", $patterns)));
        foreach ($splitpatterns($config->prohibitedemailpatterns) as $prohibitedpattern) {
            if (fnmatch($prohibitedpattern, $email)) {
                return false;
            }
        }
        foreach ($splitpatterns($config->allowedemailpatterns) as $allowedpattern) {
            if (fnmatch($allowedpattern, $email)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get user by email.
     *
     * @param string $email Case and accent-insensitive email address.
     * @return stdClass|null User or null if not found.
     * @throws dml_exception
     */
    protected function get_user_by_email(string $email): ?stdClass {
        global $DB, $CFG;

        // Emails in Moodle as case-insensitive and accents-sensitive. Such a combination can lead to very slow queries
        // on some DBs such as MySQL. So we first get the list of candidate users in a subselect via more effective
        // accent-insensitive query that can make use of the index and only then we search within that limited subset.
        $sql = "SELECT *
                  FROM {user}
                 WHERE " . $DB->sql_equal('email', ':email1', false, true) . "
                   AND id IN (SELECT id
                                FROM {user}
                               WHERE " . $DB->sql_equal('email', ':email2', false, false) . "
                                 AND mnethostid = :mnethostid)";

        $params = [
            'email1' => $email,
            'email2' => $email,
            'mnethostid' => $CFG->mnet_localhost_id,
        ];

        return $DB->get_record_sql($sql, $params) ?: null;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return true
     */
    public function is_internal(): bool {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return true
     */
    public function can_change_password(): bool {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url|null
     */
    public function change_password_url(): ?moodle_url {
        return null; // Use default internal method.
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return true
     */
    public function can_reset_password(): bool {
        return true;
    }
}
