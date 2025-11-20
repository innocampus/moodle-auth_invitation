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
use core\output\renderer_base;
use core_user;
use moodleform;
use renderable;
use stdClass;
use templatable;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/login/lib.php');

class login_signup_form extends moodleform implements renderable, templatable {

    function definition(): void {
        global $CFG;

        if (empty($this->_customdata['invitationtoken'])) {
            throw new coding_exception('Missing "invitationtoken" in customdata.');
        }
        $invitationtoken = $this->_customdata['invitationtoken'];

        if (empty($this->_customdata['email'])) {
            throw new coding_exception('Missing "email" in customdata.');
        }
        $email = $this->_customdata['email'];

        $mform = $this->_form;

        $mform->addElement('html', \html_writer::tag('p', get_string('registerhere', 'auth_invitation')));

        // Check whether enrol_invitation plugin allows mismatching email addresses.
        $allowmismatchingemails = get_config('enrol_invitation', 'allowmismatchingemails');
        if ($allowmismatchingemails) {
            $mform->addElement('html', \html_writer::tag('p', get_string('registeredusersloginhere', 'auth_invitation'), ['class' => 'fw-bold']));
            $mform->addElement('html', \html_writer::tag('p',
                \html_writer::link(get_login_url(), get_string('alreadyregistered', 'auth_invitation'), ['class' => 'btn btn-secondary']),
                ['class' => 'text-center']
            ));
        } else {
            $mform->addElement('html', \html_writer::tag('p', get_string('registereduserscontactteachers', 'auth_invitation'), ['class' => 'fw-bold']));
        }

        $mform->addElement('hidden', 'invitationtoken');
        $mform->setConstant('invitationtoken', $invitationtoken);

        if (!get_config('auth_invitation', 'generateusername')) {
            $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12" autocapitalize="none"');
            $mform->setType('username', PARAM_RAW);
            $mform->addRule('username', get_string('missingusername'), 'required', null, 'client');
        }

        if (!empty($CFG->passwordpolicy)){
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('password', 'password', get_string('password'), [
            'maxlength' => MAX_PASSWORD_CHARACTERS,
            'size' => 12,
            'autocomplete' => 'new-password'
        ]);
        $mform->setType('password', core_user::get_property_type('password'));
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');
        $mform->addRule('password', get_string('maximumchars', '', MAX_PASSWORD_CHARACTERS),
            'maxlength', MAX_PASSWORD_CHARACTERS, 'client');

        $mform->addElement('text', 'email', get_string('email'), 'disabled="disabled"');
        $mform->setConstant('email', $email);
        $mform->setForceLtr('email');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30"');
            $mform->setType($field, core_user::get_property_type('firstname'));
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        $mform->addElement('text', 'city', get_string('city'), 'maxlength="120" size="20"');
        $mform->setType('city', core_user::get_property_type('city'));
        if (!empty($CFG->defaultcity)) {
            $mform->setDefault('city', $CFG->defaultcity);
        }

        $country = get_string_manager()->get_list_of_countries();
        $default_country[''] = get_string('selectacountry');
        $country = array_merge($default_country, $country);
        $mform->addElement('select', 'country', get_string('country'), $country);

        if( !empty($CFG->country) ){
            $mform->setDefault('country', $CFG->country);
        }else{
            $mform->setDefault('country', '');
        }

        profile_signup_fields($mform);

        // Hook for plugins to extend form definition.
        core_login_extend_signup_form($mform);

        // Add "Agree to sitepolicy" controls. By default it is a link to the policy text and a checkbox but
        // it can be implemented differently in custom sitepolicy handlers.
        $manager = new \core_privacy\local\sitepolicy\manager();
        $manager->signup_form($mform);

        // buttons
        $this->set_display_vertical();
        $this->add_action_buttons(true, get_string('createaccount'));

    }

    function definition_after_data(): void {
        $mform = $this->_form;
        $mform->applyFilter('username', 'trim');

        // Trim required name fields.
        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }
    }

    /**
     * Validate user supplied data on the signup form.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files): array {
        global $CFG;

        $errors = parent::validation($data, $files);

        // Extend validation for any form extensions from plugins.
        $errors = array_merge($errors, core_login_validate_extend_signup_form($data));

        // Construct fake user object to check password policy against required information.
        $tempuser = new stdClass();
        // To prevent errors with check_password_policy(),
        // the temporary user and the guest must not share the same ID.
        $tempuser->id = (int) $CFG->siteguest + 1;
        $tempuser->username = 'user';
        $tempuser->firstname = $data['firstname'];
        $tempuser->lastname = $data['lastname'];
        $tempuser->email = $data['email'];

        $errmsg = '';
        if (!check_password_policy($data['password'], $errmsg, $tempuser)) {
            $errors['password'] = $errmsg;
        }

        // Validate customisable profile fields. (profile_validation expects an object as the parameter with userid set).
        $dataobject = (object)$data;
        $dataobject->id = 0;
        $errors += profile_validation($dataobject, $files);

        return $errors;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        ob_start();
        $this->display();
        $formhtml = ob_get_contents();
        ob_end_clean();
        return [
            'formhtml' => $formhtml
        ];
    }
}
