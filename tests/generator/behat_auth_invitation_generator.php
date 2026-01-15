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
 * Behat generator class for auth_invitation.
 *
 * @package    auth_invitation
 * @copyright  2026 Lars Bonczek, TU Berlin <bonczek@tu-berlin.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_auth_invitation_generator extends behat_generator_base {
    /**
     * Get a list of the entities that can be created for this component.
     *
     * This function must be overridden in subclasses. See class comment
     * above for a description of the data structure.
     * See {@see behat_core_generator} for an example.
     *
     * @return array entity name => information about how to generate.
     */
    protected function get_creatable_entities(): array {
        return [
            'invitations' => [
                'datagenerator' => 'invitation',
                'required' => ['token', 'course', 'email', 'timeexpiration'],
                'switchids' => ['role' => 'roleid', 'course' => 'courseid'],
            ],
        ];
    }
}
