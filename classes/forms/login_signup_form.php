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
 * User sign-up form.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_invitation\forms;

use core\exception\coding_exception;
use core_user;
use dml_exception;
use Random\RandomException;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/login/signup_form.php');

/**
 * User sign-up form. Inherits from {@see \login_signup_form}.
 *
 * Only users who were invited to a course using enrol_invitation can use this form to sign up. The invitation token and the email
 * address which the invitation was sent to must be given in the form's custom data, e.g.
 *
 *     $customdata = [
 *         'invitationtoken' => $invite->token,
 *         'email' => $invite->email,
 *     ];
 *     new \auth_invitation\forms\login_signup_form(null, $customdata, 'post', '', ['autocomplete' => 'on']);
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class login_signup_form extends \login_signup_form {
    /**
     * Form definition.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public function definition(): void {
        if (empty($this->_customdata['invitationtoken'])) {
            throw new coding_exception('Missing "invitationtoken" in customdata.');
        }
        $invitationtoken = $this->_customdata['invitationtoken'];

        if (empty($this->_customdata['email'])) {
            throw new coding_exception('Missing "email" in customdata.');
        }
        $email = $this->_customdata['email'];

        parent::definition();

        $config = get_config('auth_invitation');

        $mform = $this->_form;

        // Add invitation token.
        $mform->addElement('hidden', 'invitationtoken');
        $mform->setConstant('invitationtoken', $invitationtoken);

        // Hide username field if username is auto-generated.
        if ($config->generateusername) {
            $mform->removeElement('username');
            $mform->addElement('hidden', 'username');
            $mform->setType('username', PARAM_RAW);
            // Value is set in definition_after_data.
        }

        // Password confirmation.
        if ($config->confirmpasswordonsignup) {
            $password2el = $mform->createElement(
                'password',
                'password2',
                get_string('confirmpassword', 'auth_invitation'),
                [
                    'maxlength' => MAX_PASSWORD_CHARACTERS,
                    'size' => 12,
                    'autocomplete' => 'new-password',
                ]
            );
            $mform->insertElementBefore($password2el, 'email');
            $mform->setType('password2', core_user::get_property_type('password'));
            $mform->addRule('password2', get_string('missingpassword'), 'required', null, 'client');
        }

        // Redefine email fields with preset values.
        $mform->removeElement('email');
        $emailel = $mform->createElement('text', 'email', get_string('email'), 'disabled="disabled"');
        $mform->insertElementBefore($emailel, 'email2');
        $mform->setConstant('email', $email);
        $mform->setForceLtr('email');
        $mform->removeElement('email2');
        $mform->addElement('hidden', 'email2');
        $mform->setConstant('email2', $email);

        // Remove city and country fields if not desired.
        if ($config->hidecityfieldonsignup) {
            $mform->removeElement('city');
        }
        if ($config->hidecountryfieldonsignup) {
            $mform->removeElement('country');
        }
    }

    /**
     * Form definition after data.
     *
     * @throws coding_exception
     * @throws RandomException
     * @throws dml_exception
     */
    public function definition_after_data(): void {
        $config = get_config('auth_invitation');
        if ($config->generateusername) {
            $mform = $this->_form;
            // Generate username after form submission to prevent race condition.
            $prefix = $config->usernameprefix ?? 'inviteduser';
            $username = $this->generate_unique_username($prefix);
            $mform->setConstant('username', $username);
        }

        parent::definition_after_data();
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     * or an empty array if everything is OK (true allowed for backwards compatibility too).
     * @throws coding_exception
     * @throws dml_exception
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        $config = get_config('auth_invitation');
        if ($config->confirmpasswordonsignup && $data['password'] !== $data['password2']) {
            $errors['password2'] = get_string('mismatchingpasswords', 'auth_invitation');
        }

        return $errors;
    }

    /**
     * Generate a unique username for an invited user.
     *
     * @param string $prefix Prefix prepended to the generated username.
     * @throws coding_exception
     * @throws RandomException
     * @throws dml_exception
     */
    public function generate_unique_username(string $prefix): string {
        global $DB, $CFG;
        $digits = 6;
        $maxtries = 10;
        for ($i = 0; $i < $maxtries; $i++) {
            $number = random_int(0, pow(10, $digits) - 1);
            $username = $prefix . sprintf("%0{$digits}d", $number);
            if (!$DB->record_exists('user', ['username' => $username, 'mnethostid' => $CFG->mnet_localhost_id])) {
                return $username;
            }
            if ($i % 3 == 2) {
                // Three tries failed at this number of digits -> increase number of digits.
                $digits++;
            }
        }
        throw new coding_exception("Could not generate a unique username after $maxtries tries.");
    }
}
