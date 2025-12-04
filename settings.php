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
 * Admin settings and defaults.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    // Introductory explanation.
    $settings->add(new admin_setting_heading(
        'auth_invitation/pluginname',
        '',
        new lang_string('description', 'auth_invitation')
    ));

    // Roles to assign to registered users.
    $settings->add(new admin_setting_pickroles(
        'auth_invitation/assignedroles',
        get_string('assignedroles', 'auth_invitation'),
        get_string('assignedroles_help', 'auth_invitation'),
        []
    ));

    // Patterns for allowed and prohibited email addresses.
    $settings->add(new admin_setting_configtextarea(
        'auth_invitation/allowedemailpatterns',
        get_string('allowedemailpatterns', 'auth_invitation'),
        get_string('allowedemailpatterns_help', 'auth_invitation'),
        '*'
    ));

    $settings->add(new admin_setting_configtextarea(
        'auth_invitation/prohibitedemailpatterns',
        get_string('prohibitedemailpatterns', 'auth_invitation'),
        get_string('prohibitedemailpatterns_help', 'auth_invitation'),
        ''
    ));

    // Settings for sign up form.
    $settings->add(new admin_setting_heading(
        'auth_invitation/signupsettings',
        new lang_string('signupsettings', 'auth_invitation'),
        new lang_string('signupsettingsdesc', 'auth_invitation')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'auth_invitation/generateusername',
        get_string('generateusername', 'auth_invitation'),
        get_string('generateusername_help', 'auth_invitation'),
        0
    ));

    $settings->add(new admin_setting_requiredtext(
        'auth_invitation/usernameprefix',
        get_string('usernameprefix', 'auth_invitation'),
        get_string('usernameprefix_help', 'auth_invitation'),
        'temp'
    ));
    $settings->hide_if('auth_invitation/usernameprefix', 'auth_invitation/generateusername');

    require_once($CFG->dirroot . '/user/editlib.php');
    $defaultrequiredfields = useredit_get_required_name_fields();
    $defaultenabledfields = array_merge($defaultrequiredfields, ['city', 'country']);
    /** @var auth_plugin_invitation $authplugin */
    $authplugin = get_auth_plugin('invitation');
    foreach ($authplugin->get_signup_profile_field_definitions() as $field => $definition) {
        if (isset($definition['choices'])) {
            $setting = new admin_setting_configselect('auth_invitation/' . $definition['setting'], $definition['name'], '', '', $definition['choices']);
        } else {
            $setting = new admin_setting_configtext('auth_invitation/' . $definition['setting'], $definition['name'], '', '', $definition['type']);
        }
        $setting->set_enabled_flag_options(true, in_array($field, $defaultenabledfields));
        $setting->set_required_flag_options(true, in_array($field, $defaultrequiredfields));
        $settings->add($setting);
    }

    // Display locking / mapping of profile fields.
    display_auth_lock_options(
        $settings,
        $authplugin->authtype,
        $authplugin->userfields,
        get_string('auth_fieldlocks_help', 'auth'),
        false,
        false
    );
}
