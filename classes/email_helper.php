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
 * Helper class for sending information emails.
 *
 * @package    auth_invitation
 * @copyright  2026 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_invitation;

use moodle_exception;
use stdClass;

/**
 * Helper class for sending information emails.
 *
 * @package    auth_invitation
 * @copyright  2026 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class email_helper {
    /**
     * Returns an object with common placeholders required for all emails.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws moodle_exception
     */
    public static function get_common_email_data(stdClass $user): stdClass {
        global $CFG;
        $site = get_site();
        $data = new stdClass();
        $data->sitefullname = format_string($site->fullname);
        $data->siteshortname = format_string($site->shortname);
        $data->wwwroot = $CFG->wwwroot;
        $data->admin = generate_email_signoff();
        // Add user name fields to $data based on $user.
        $placeholders = \core_user::get_name_placeholders($user);
        foreach ($placeholders as $field => $value) {
            $data->{$field} = $value;
        }
        return $data;
    }

    /**
     * Sends an email to a user in that user's language.
     *
     * @param stdClass $user The recipient.
     * @param string $subjectstringid The string id of the subject lang string.
     * @param string $messagestringid The string id of the body lang string.
     * @param stdClass $data Placeholder data.
     * @return bool Whether the email was sent successfully.
     */
    public static function send_localized_email(stdClass $user, string $subjectstringid, string $messagestringid, stdClass $data): bool {
        $sm = get_string_manager();
        $subject = $sm->get_string($subjectstringid, 'auth_invitation', $data, lang: $user->lang);
        $message = $sm->get_string($messagestringid, 'auth_invitation', $data, lang: $user->lang);
        $messagehtml = text_to_html($message, smileyignored: false, para: false);
        return email_to_user($user, \core_user::get_support_user(), $subject, $message, $messagehtml);
    }
}
