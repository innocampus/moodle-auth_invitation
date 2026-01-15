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
 * Generator class for auth_invitation.
 *
 * @package    auth_invitation
 * @copyright  2026 Lars Bonczek, TU Berlin <bonczek@tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_invitation_generator extends component_generator_base {
    /**
     * Create an invitation record.
     *
     * @param array $attributes
     * @return void
     * @throws dml_exception
     */
    public function create_invitation(array $attributes): void {
        global $DB;
        $attributes = array_merge(
            [
                'roleid' => $DB->get_field('role', 'id', ['shortname' => 'student']),
            ],
            $attributes
        );
        $DB->insert_record('enrol_invitation', $attributes);
    }
}
