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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat step definitions for auth_invitation.
 *
 * @package    auth_invitation
 * @copyright  2026 Lars Bonczek, TU Berlin <bonczek@tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_auth_invitation extends behat_base {
    /**
     * Convert page names to URLs for steps like 'When I am on the "[identifier]" "[page type]" page'.
     *
     * A typical example might be:
     *     When I am on the "Test quiz" "mod_quiz > Responses report" page
     * which would cause this method in behat_mod_quiz to be called with
     * arguments 'Responses report', 'Test quiz'.
     *
     * You should override this as appropriate for your plugin. The method
     * {@see behat_navigation::resolve_core_page_instance_url()} is a good example.
     *
     * Your overridden method should document the recognised page types with
     * a table like this:
     *
     * Recognised page names are:
     * | Type      | identifier meaning | Description                                     |
     *
     * @param string $type identifies which type of page this is, e.g. 'Attempt review'.
     * @param string $identifier identifies the particular page, e.g. 'Test quiz > student > Attempt 1'.
     * @return moodle_url the corresponding URL.
     * @throws Exception with a meaningful error message if the specified page cannot be found.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        if ($type === 'accept invitation') {
            return new moodle_url('/enrol/invitation/enrol.php', ['token' => $identifier]);
        }
        if ($type === 'reject invitation') {
            return new moodle_url('/enrol/invitation/enrol.php', ['token' => $identifier, 'reject' => 1]);
        }
        throw new Exception('Unknown auth_invitation page type: ' . $type);
    }
}
