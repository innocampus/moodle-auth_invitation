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
 * Task to automatically delete inactive users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_invitation\task;

use coding_exception;
use core\task\scheduled_task;
use dml_exception;
use moodle_exception;
use stdClass;

/**
 * Task to automatically delete inactive users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_inactive_users extends scheduled_task {
    /** @var string Name of the user preference that stores the scheduled account deletion time. */
    public const ACCOUNT_DELETION_TIME_USER_PREFERENCE = 'auth_invitation_account_deletion_time';

    /** @var stdClass Configuration of the auth_invitation plugin. */
    private stdClass $config;

    /**
     * {@inheritDoc}
     *
     * @return string
     * @throws coding_exception
     */
    public function get_name(): string {
        return get_string('deleteinactiveusers', 'auth_invitation');
    }

    /**
     * {@inheritDoc}
     *
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public function execute(): void {
        $this->config = get_config('auth_invitation');

        if (!$this->config->autodeleteusers) {
            mtrace('Automatic deletion of inactive users is disabled. ' .
                    'Please enable the auth_invitation/autodeleteusers setting to activate this task.');
            return;
        }

        $deleteaftersecs = DAYSECS * $this->config->autodeleteusersafterdays;
        $noticesecs = DAYSECS * $this->config->autodeleteusersnoticedays;

        $now = $this->get_timestarted();
        $deletethreshold = $now - $deleteaftersecs;
        $noticethreshold = $deletethreshold + $noticesecs;

        // Get all inactive users who qualify for a notice or for deletion.
        // The resulting list of user records includes the value of the scheduled deletion time user preference in the
        // field `deletiontime`.
        $inactiveusers = $this->get_inactive_users($noticethreshold);

        // Notify all users who need to be notified.
        if ($this->config->autodeleteusersnoticedays > 0) {
            $notifyusers = array_filter($inactiveusers, function ($user) use ($deleteaftersecs) {
                // Only notify if no notice has been sent, yet, or if not enough time has passed between the user's last
                // access and the scheduled deletion time announced in the previous notice.
                return empty($user->deletiontime) || ($user->deletiontime - $user->lastaccess < $deleteaftersecs);
            });
            $deletiontimestamp = $now + $noticesecs;
            $this->notify_users($notifyusers, $deletiontimestamp);
        } else {
            mtrace('Not sending notices because the auth_invitation/autodeleteusersnoticedays setting is set to 0.');
        }

        // Delete all users who can be deleted.
        $deleteusers = array_filter($inactiveusers, fn($user) => $user->lastaccess < $deletethreshold);
        if ($this->config->autodeleteusersnoticedays > 0) {
            $deleteusers = array_filter($deleteusers, function ($user) use ($deleteaftersecs, $noticethreshold) {
                // Only delete if a notice has been sent, enough time has passed between the user's last access and the
                // scheduled deletion time announced in the notice, and the deletion time is not in the future.
                return !empty($user->deletiontime) && $user->deletiontime - $user->lastaccess >= $deleteaftersecs &&
                        $user->deletiontime <= $this->get_timestarted();
            });
        }
        $this->delete_users($deleteusers);
    }

    /**
     * Returns an array of users who signed up using this authentication method whose last login was before the specified time.
     *
     * Each user record returned by this function also includes the value of the user preference
     * {@see self::ACCOUNT_DELETION_TIME_USER_PREFERENCE} in the field `deletiontime`, or null if the setting is not set.
     *
     * @param int $lastaccessbefore
     * @return stdClass[] Array of user records including the `deletiontime` field.
     * @throws dml_exception
     */
    private function get_inactive_users(int $lastaccessbefore): array {
        global $DB;
        return $DB->get_records_sql(
            "SELECT u.*, pref.value AS deletiontime
            FROM {user} u
            LEFT JOIN {user_preferences} pref ON pref.userid = u.id AND pref.name = :prefname
            WHERE u.deleted = 0
              AND u.auth = 'invitation'
              AND u.lastaccess > 0
              AND u.lastaccess < :lastaccessbefore
            ORDER BY u.id",
            [
                'prefname' => self::ACCOUNT_DELETION_TIME_USER_PREFERENCE,
                'lastaccessbefore' => $lastaccessbefore,
            ]
        );
    }

    /**
     * Notifies users who have not been notified since their last login, informing them that their account is pending deletion.
     *
     * @param stdClass[] $users
     * @param int $deletiontime Scheduled deletion time.
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function notify_users(array $users, int $deletiontime): void {
        $totalcount = count($users);
        $deletiontimestr = date(DATE_ATOM, $deletiontime);
        mtrace("Notifying $totalcount users about the pending deletion of their accounts...");
        $notifiedcount = 0;
        foreach ($users as $user) {
            $lastaccessstr = date(DATE_ATOM, $user->lastaccess);
            if (!$this->notify_user($user, $deletiontime)) {
                mtrace("ERROR: Failed to notify user $user->username with ID $user->id (last seen at $lastaccessstr) about the " .
                        "pending deletion of their account. See debugging output for more info.");
                // Will be tried again the next time this task is run.
                continue;
            }
            set_user_preference(self::ACCOUNT_DELETION_TIME_USER_PREFERENCE, $deletiontime, $user);
            $notifiedcount++;
            mtrace("User $user->username with ID $user->id (last seen at $lastaccessstr) was notified about the pending " .
                    "deletion of their account after $deletiontimestr.");
        }
        mtrace("$notifiedcount of $totalcount users were successfully notified about the pending deletion of their accounts.");
    }

    /**
     * Sends an email to a user notifying them about the pending deletion of their account.
     *
     * @param stdClass $user The user to notify.
     * @param int $deletiontime Scheduled deletion time.
     * @return bool Whether the email was sent successfully.
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function notify_user(stdClass $user, int $deletiontime): bool {
        $sm = get_string_manager();

        $data = $this->get_common_email_data($user);
        $data->loginurl = get_login_url();
        $dateformat = $sm->get_string('strftimedatefullshort', lang: $user->lang);
        // Last full day in user's timezone before the account will be deleted.
        $data->deletionafter = userdate($deletiontime - DAYSECS, $dateformat, $user->timezone);
        $data->deletionindays = $this->config->autodeleteusersnoticedays;

        return $this->send_localized_email($user, 'accountdeletionnoticesubject', 'accountdeletionnotice', $data);
    }

    /**
     * Deletes users who have already been notified about the pending deletion of their accounts (if notifications are enabled).
     *
     * @param stdClass[] $users Users to delete.
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function delete_users(array $users): void {
        $totalcount = count($users);
        mtrace("Deleting $totalcount users...");
        $deletedcount = 0;
        foreach ($users as $user) {
            $lastaccessstr = date(DATE_ATOM, $user->lastaccess);
            if (!delete_user($user)) {
                mtrace("ERROR: Failed to delete user $user->username with ID $user->id (last seen at $lastaccessstr). " .
                        "See debugging output for more info.");
                // Will be tried again the next time this task is run.
                continue;
            }
            mtrace("User $user->username with ID $user->id (last seen at $lastaccessstr) was deleted.");
            $deletedcount++;
            if (!$this->notify_deleted_user($user)) {
                mtrace("WARNING: Failed to notify user $user->username with ID $user->id that their account was deleted. " .
                        "See debugging output for more info.");
            }
        }
        mtrace("$deletedcount of $totalcount users were successfully deleted.");
    }

    /**
     * Sends an email to a user notifying them about the deletion of their account.
     *
     * @param stdClass $user The user to notify.
     * @return bool Whether the email was sent successfully.
     * @throws coding_exception
     * @throws moodle_exception
     */
    private function notify_deleted_user(stdClass $user): bool {
        $data = $this->get_common_email_data($user);
        return $this->send_localized_email($user, 'accountdeletedsubject', 'accountdeleted', $data);
    }

    /**
     * Returns an object with common placeholders required for all emails.
     *
     * @param stdClass $user
     * @return stdClass
     * @throws moodle_exception
     */
    private function get_common_email_data(stdClass $user): stdClass {
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
    private function send_localized_email(stdClass $user, string $subjectstringid, string $messagestringid, stdClass $data): bool {
        $sm = get_string_manager();
        $subject = $sm->get_string($subjectstringid, 'auth_invitation', $data, lang: $user->lang);
        $message = $sm->get_string($messagestringid, 'auth_invitation', $data, lang: $user->lang);
        $messagehtml = text_to_html($message, smileyignored: false, para: false);
        return email_to_user($user, \core_user::get_support_user(), $subject, $message, $messagehtml);
    }
}
