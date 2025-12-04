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
 * Auth plugin for temporary users that were invited using enrol_invitation.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use Random\RandomException;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Auth plugin for temporary users that were invited using enrol_invitation.
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
        global $CFG, $SESSION;

        // Validate invitation token.
        if (empty($user->invitationtoken)) {
            throw new coding_exception('Missing invitationtoken in user data from signup form.');
        }
        $invite = $this->get_valid_invitation($user->invitationtoken);
        if (!$invite) {
            throw new moodle_exception('invalidinvite', 'auth_invitation');
        }
        if (strtolower($invite->email) !== strtolower($user->email)) {
            throw new coding_exception('Email in user data from signup form does not match email in invitation.');
        }
        if ($invite->userid) {
            throw new coding_exception('Cannot sign up using invitation for existing user.');
        }

        // We can confirm the user since the invitation token is valid (and matches their email address).
        $user->confirmed = 1;

        if (get_config('auth_invitation', 'generateusername')) {
            // Generate a unique username for the new user.
            $user->username = $this->generate_unique_username();
        }

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

        if ($notify) {
            // Log in newly created user.
            $user = get_complete_user_data('username', $user->username);
            complete_user_login($user);

            \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

            // Redirect to course enrolment.
            if ($SESSION->wantsurl) {
                redirect(new moodle_url($SESSION->wantsurl));
            } else {
                redirect(new moodle_url('/enrol/invitation/enrol.php', ['token' => $invite->token]));
            }
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
        );
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
        if ($wantsurl->get_path() !== '/enrol/invitation/enrol.php') {
            return null;
        }
        return $wantsurl->param('token');
    }

    /**
     * Return a form to capture user details for account creation.
     * This is used in /login/signup.php and /auth/invitation/signup.php.
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
        if (!$token) {
            throw new moodle_exception('invalidinvite', 'auth_invitation');
        }
        $invite = $this->get_valid_invitation($token);
        if (!$invite) {
            throw new moodle_exception('invalidinvite', 'auth_invitation');
        }
        $customdata = [
            'invitationtoken' => $invite->token,
            'email' => $invite->email,
        ];
        return new \auth_invitation\forms\login_signup_form(null, $customdata, 'post', '', ['autocomplete' => 'on']);
    }

    /**
     * Generate a unique username for an invited user.
     *
     * @throws coding_exception
     * @throws RandomException
     * @throws dml_exception
     */
    protected function generate_unique_username(): string {
        global $DB, $CFG;
        $prefix = get_config('auth_invitation', 'usernameprefix') ?? 'temp';
        $digits = 6;
        $maxtries = 10;
        for ($i = 0; $i < $maxtries; $i++) {
            $number = random_int(0, pow(10, $digits) - 1);
            $username = $prefix . sprintf("%0{$digits}d", $number);
            if (!$DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
                return $username;
            }
        }
        throw new coding_exception("Could not generate a unique username after $maxtries tries.");
    }

    /**
     * Hook called by require_login before redirecting to the login page.
     *
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function pre_loginpage_hook(): void {
        $token = $this->get_invitation_token_from_session();
        if (!$token) {
            return;
        }
        $invite = $this->get_valid_invitation($token);
        if (!$invite) {
            throw new moodle_exception('invalidinvite', 'auth_invitation');
        }
        if ($invite->userid) {
            // Invite was sent to existing user -> let them log in normally.
            return;
        }
        if (!$this->is_allowed_email($invite->email)) {
            // We do not allow self-registration for users with this email -> show login page.
            return;
        }
        $user = $this->get_user_by_email($invite->email);
        if ($user) {
            // Invited user exists in DB -> let them log in normally.
            return;
        }
        // Invited user does not exist in DB -> immediately redirect to our custom signup form.
        redirect(new moodle_url('/auth/invitation/signup.php', ['invitationtoken' => $token]));
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

    /**
     * @return array
     * @throws \core\exception\coding_exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_signup_profile_field_definitions(): array {
        $config = get_config('auth_invitation');
        $signupfields = array_diff($this->userfields, ['email']);
        $definitions = [];
        foreach ($signupfields as $field) {
            $definition = core_user::get_property_definition($field);
            if ($field == 'lang') {
                $fieldname = get_string('language');
            } else {
                $fieldname = get_string($field);
            }
            $definition['name'] = $fieldname;
            $setting = "signup_$field";
            $definition['setting'] = $setting;
            $enabledsetting = "{$setting}_enabled";
            $requiredsetting = "{$setting}_required";
            $definition['default'] = $config->$setting ?: ($definition['default'] ?? '');
            $definition['enabled'] = $config->$enabledsetting ?? false;
            $definition['required'] = $config->$requiredsetting ?? false;
            $definitions[$field] = $definition;
        }
        return $definitions;
    }
}
