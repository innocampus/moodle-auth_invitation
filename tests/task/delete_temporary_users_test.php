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
 * Tests for the task to delete inactive temporary users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_invitation\task;

use coding_exception;
use dml_exception;
use moodle_exception;

/**
 * Tests for the {@see delete_temporary_users} class.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \auth_invitation\task\delete_temporary_users
 */
final class delete_temporary_users_test extends \advanced_testcase {
    /**
     * Test for the {@see delete_temporary_users::get_name()} function.
     *
     * @covers ::get_name
     * @throws coding_exception
     */
    public function test_get_name(): void {
        $task = new delete_temporary_users();
        $this->assertEquals('Delete inactive temporary users', $task->get_name());
    }

    /**
     * Test for the {@see delete_temporary_users::execute()} function.
     *
     * @dataProvider execute_provider
     * @covers ::execute
     * @param int $time Task execution time.
     * @param int $deletiondays Value for the auth_invitation/autodeleteusersafterdays setting.
     * @param int $notificationdays Value for the auth_invitation/autodeleteusersnoticedays setting.
     * @param array[] $users Users to create for the test. The value of the `deletiontime` field will be stored in the account
     * deletion time user preference.
     * @param string $expectedoutputregex Regex for the expected output of the task.
     * @param array $expectednotified List of users who we expect to receive a notice, with the usernames as keys and the expected
     * "deletion after"-dates printed in the emails as values.
     * @param array $expecteddeleted List of usernames of the users who we expect to be deleted.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_execute(
            int $time,
            int $deletiondays,
            int $notificationdays,
            array $users,
            string $expectedoutputregex,
            array $expectednotified,
            array $expecteddeleted
    ): void {
        global $DB;
        $this->resetAfterTest();
        $this->expectOutputRegex($expectedoutputregex);

        $deletedbeforeids = [];
        $expecteddeletedids = [];
        $originalusers = [];
        foreach ($users as $user) {
            $userobj = $this->getDataGenerator()->create_user($user);
            if (isset($user['deletiontime'])) {
                set_user_preference(delete_temporary_users::ACCOUNT_DELETION_TIME_USER_PREFERENCE, $user['deletiontime'], $userobj);
            }
            if ($userobj->deleted) {
                $deletedbeforeids[] = $userobj->id;
            }
            if (isset($user['username']) && in_array($user['username'], $expecteddeleted)) {
                // Username is updated when the user is deleted, so we store the id instead.
                $expecteddeletedids[] = $userobj->id;
                // Email and names are updated on deletion, so we store the original.
                $originalusers[$userobj->id] = $userobj;
            }
        }

        // Verify that no unknown users where given in $expectednotified and $expecteddeleted.
        $usernames = $DB->get_fieldset('user', 'username');
        $this->assertEquals([], array_diff(array_keys($expectednotified), $usernames), 'Unknown username in $expectednotified.');
        $this->assertEquals([], array_diff($expecteddeleted, $usernames), 'Unknown username in $expecteddeleted.');

        unset_config('noemailever');
        $sink = $this->redirectEmails();

        set_config('autodeleteusers', true, 'auth_invitation');
        set_config('autodeleteusersafterdays', $deletiondays, 'auth_invitation');
        set_config('autodeleteusersnoticedays', $notificationdays, 'auth_invitation');

        $task = new delete_temporary_users();
        $task->set_timestarted($time);
        $task->execute();

        $messages = $sink->get_messages();
        $messages = array_column($messages, null, 'to'); // Reindex with recipient email address.

        foreach ($DB->get_records('user') as $user) {
            $deleted = in_array($user->id, $deletedbeforeids) || in_array($user->id, $expecteddeletedids);
            $this->assertEquals($deleted, $user->deleted, "Assertion failed for user $user->username.");

            if (array_key_exists($user->username, $expectednotified)) {
                $this->assertArrayHasKey($user->email, $messages);
                $message = $messages[$user->email];
                unset($messages[$user->email]);
                $expecteddeletionafter = $expectednotified[$user->username];
                $this->assertEquals("PHPUnit test site: account deletion in $notificationdays days", $message->subject);
                $this->assertStringContainsString($expecteddeletionafter, $message->body);
                $encodedname = quoted_printable_encode($user->firstname);
                $this->assertStringContainsStringIgnoringLineEndings(
                    <<<EOD
                    Hi $encodedname,

                    Your account at 'PHPUnit test site' will be deleted automatically if you do
                    not log in to the site until $expecteddeletionafter.

                    Note that you will not be able to access your account after this date. This
                    includes any courses you may be enrolled in as well as your submissions and
                    grades in these courses.

                    To stop your account from being deleted, please log in here until $expecteddeletionafter:

                    https://www.example.com/moodle/login/index.php
                    EOD,
                    $message->body
                );
                $prefname = delete_temporary_users::ACCOUNT_DELETION_TIME_USER_PREFERENCE;
                $this->assertEquals($time + DAYSECS * $notificationdays, get_user_preferences($prefname, null, $user));
            }
            if (in_array($user->id, $expecteddeletedids)) {
                $originaluser = $originalusers[$user->id];
                $this->assertArrayHasKey($originaluser->email, $messages);
                $message = $messages[$originaluser->email];
                unset($messages[$originaluser->email]);
                $this->assertEquals('PHPUnit test site: account deleted', $message->subject);
                $encodedname = quoted_printable_encode($originaluser->firstname);
                $this->assertStringContainsStringIgnoringLineEndings(
                    <<<EOD
                    Hi $encodedname,

                    Your account at 'PHPUnit test site' was deleted automatically due to
                    inactivity.

                    If this was done in error, please contact us immediately by responding to
                    this email. Otherwise, you can ignore this email.
                    EOD,
                    $message->body
                );
            }
        }

        $this->assertEquals([], $messages);
    }

    /**
     * Data provider for the {@see test_execute} method.
     *
     * @return array
     */
    public static function execute_provider(): array {
        return [
            'No notifications or deletions' => [
                1765289895, // Current time: December 9, 2025 2:18:15 PM GMT.
                180, // Deletion days.
                14, // Notification days.
                [
                    ['username' => 'temp1', 'auth' => 'invitation', 'lastaccess' => 1765289895 - DAYSECS * 5],
                    ['username' => 'temp2', 'auth' => 'invitation', 'lastaccess' => 1765289895 - DAYSECS * 166],
                    ['username' => 'temp3', 'auth' => 'invitation', 'lastaccess' => 1765289895 - DAYSECS * 180, 'deleted' => 1],
                    ['username' => 'temp4', 'auth' => 'email', 'lastaccess' => 1765289895 - DAYSECS * 180],
                ],
                <<<EOD
                /^Notifying 0 users about the pending deletion of their accounts\.\.\.
                0 of 0 users were successfully notified about the pending deletion of their accounts\.
                Deleting 0 users\.\.\.
                0 of 0 users were successfully deleted\.$/
                EOD, // Task output regex.
                [], // Notified users.
                [], // Deleted users.
            ],
            'Only notifications' => [
                1765289895, // Current time: December 9, 2025 2:18:15 PM GMT.
                100, // Deletion days.
                20, // Notification days.
                [
                    [
                        'username' => 'temp1',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 5, // Last access not more than 80 days ago.
                        // Not notified.
                    ],
                    [
                        'username' => 'temp2',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 80, // Last access not more than 80 days ago.
                        // Not notified.
                    ],
                    [
                        'username' => 'temp3',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 80 - 1, // Last access more than 80 days ago.
                        'timezone' => 'Europe/Berlin',
                        // Notified.
                    ],
                    [
                        'username' => 'temp4',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 80 - 1, // Last access more than 80 days ago.
                        'timezone' => 'Australia/Sydney', // Different timezone different date in notification.
                        // Notified.
                    ],
                    [
                        'username' => 'temp5',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 150, // Last access more than 80 days ago.
                        'timezone' => 'Europe/Berlin',
                        // Notified.
                    ],
                    [
                        'username' => 'temp6',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 150, // Last access more than 80 days ago.
                        'deleted' => 1, // Already deleted.
                        // Not notified.
                    ],
                ],
                // phpcs:disable moodle.Files.LineLength.TooLong
                <<<EOD
                /^Notifying 3 users about the pending deletion of their accounts\.\.\.
                User temp3 with ID \d+ \(last seen at 2025-09-20T22:18:14\+08:00\) was notified about the pending deletion of their account after 2025-12-29T22:18:15\+08:00\.
                User temp4 with ID \d+ \(last seen at 2025-09-20T22:18:14\+08:00\) was notified about the pending deletion of their account after 2025-12-29T22:18:15\+08:00\.
                User temp5 with ID \d+ \(last seen at 2025-07-12T22:18:15\+08:00\) was notified about the pending deletion of their account after 2025-12-29T22:18:15\+08:00\.
                3 of 3 users were successfully notified about the pending deletion of their accounts\.
                Deleting 0 users\.\.\.
                0 of 0 users were successfully deleted\.$/
                EOD, // Task output regex.
                // phpcs:enable moodle.Files.LineLength.TooLong
                ['temp3' => '28/12/25', 'temp4' => '29/12/25', 'temp5' => '28/12/25'], // Notified users.
                [], // Deleted users.
            ],
            'Only deletions' => [
                1765289895, // Current time: December 9, 2025 2:18:15 PM GMT.
                100, // Deletion days.
                20, // Notification days.
                [
                    [
                        'username' => 'temp1',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 5, // Last access not more than 100 days ago.
                        // Not deleted.
                    ],
                    [
                        'username' => 'temp2',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 100, // Last access not more than 100 days ago.
                        'deletiontime' => 1765289895, // Deletion scheduled right now.
                        // Not deleted.
                    ],
                    [
                        'username' => 'temp3',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 100 - 1, // Last access just more than 100 days ago.
                        'deletiontime' => 1765289895, // Deletion scheduled right now.
                        // Deleted.
                    ],
                    [
                    'username' => 'temp4',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 150, // Last access more than 100 days ago.
                        'deletiontime' => 1765289895 + 1, // Deletion scheduled in the future.
                        // Not deleted.
                    ],
                    [
                        'username' => 'temp5',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 150, // Last access more than 100 days ago.
                        'deletiontime' => 1765289895 - DAYSECS * 50, // Deletion scheduled in the past.
                        // Deleted.
                    ],
                ],
                <<<EOD
                /^Notifying 0 users about the pending deletion of their accounts\.\.\.
                0 of 0 users were successfully notified about the pending deletion of their accounts\.
                Deleting 2 users\.\.\.
                User temp3 with ID \d+ \(last seen at 2025-08-31T22:18:14\+08:00\) was deleted\.
                User temp5 with ID \d+ \(last seen at 2025-07-12T22:18:15\+08:00\) was deleted\.
                2 of 2 users were successfully deleted\.$/
                EOD, // Task output regex.
                [], // Notified users.
                ['temp3', 'temp5'], // Deleted users.
            ],
            'Notifications and deletions' => [
                1765289895, // Current time: December 9, 2025 2:18:15 PM GMT.
                20, // Deletion days.
                5, // Notification days.
                [
                    [
                        'username' => 'temp1',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 15 - 1, // Last access more than 15 days ago.
                        'deletiontime' => 1765289895 + DAYSECS * 5 - 1, // Deletion scheduled 20 days after last access.
                        // Not notified.
                    ],
                    [
                        'username' => 'temp2',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 15 - 1, // Last access more than 15 days ago.
                        'timezone' => 'Europe/London',
                        'deletiontime' => 1765289895 + DAYSECS * 5 - 2, // Deletion scheduled less than 20 days after last access.
                        // Notified (again).
                    ],
                    [
                        'username' => 'temp3',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 15 - 1, // Last access more than 15 days ago.
                        'timezone' => 'Europe/London',
                        'deletiontime' => 1765289895 - DAYSECS * 5 - 2, // Deletion scheduled before last access.
                        // Notified (again).
                    ],
                    [
                        'username' => 'temp4',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 20 - 1, // Last access more than 20 days ago.
                        'deletiontime' => 1765289895 - 2, // Deletion scheduled less than 20 days after last access.
                        // Not deleted.
                        // Notified (again).
                    ],
                    [
                        'username' => 'temp5',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 20 - 1, // Last access more than 20 days ago.
                        'deletiontime' => 1765289895 - 1, // Deletion scheduled 20 days after last access.
                        // Deleted.
                    ],
                ],
                // phpcs:disable moodle.Files.LineLength.TooLong
                <<<EOD
                /^Notifying 3 users about the pending deletion of their accounts\.\.\.
                User temp2 with ID \d+ \(last seen at 2025-11-24T22:18:14\+08:00\) was notified about the pending deletion of their account after 2025-12-14T22:18:15\+08:00\.
                User temp3 with ID \d+ \(last seen at 2025-11-24T22:18:14\+08:00\) was notified about the pending deletion of their account after 2025-12-14T22:18:15\+08:00\.
                User temp4 with ID \d+ \(last seen at 2025-11-19T22:18:14\+08:00\) was notified about the pending deletion of their account after 2025-12-14T22:18:15\+08:00\.
                3 of 3 users were successfully notified about the pending deletion of their accounts\.
                Deleting 1 users\.\.\.
                User temp5 with ID \d+ \(last seen at 2025-11-19T22:18:14\+08:00\) was deleted\.
                1 of 1 users were successfully deleted\.$/
                EOD, // Task output regex.
                // phpcs:enable moodle.Files.LineLength.TooLong
                ['temp2' => '13/12/25', 'temp3' => '13/12/25', 'temp4' => '13/12/25'], // Notified users.
                ['temp5'], // Deleted users.
            ],
            'Only deletions because notifications disabled' => [
                1765289895, // Current time: December 9, 2025 2:18:15 PM GMT.
                1, // Deletion days.
                0, // Notification days.
                [
                    [
                        'username' => 'temp1',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 1, // Last access not more than 1 day ago.
                        'deletiontime' => 1765289895, // Deletion scheduled now (ignored).
                        // Not deleted.
                    ],
                    [
                        'username' => 'temp2',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 1 - 1, // Last access more than 1 day ago.
                        'deletiontime' => 1765289895 + 1, // Deletion scheduled in the future (ignored).
                        // Deleted.
                    ],
                    [
                        'username' => 'temp3',
                        'auth' => 'invitation',
                        'lastaccess' => 1765289895 - DAYSECS * 2, // Last access more than 1 day ago.
                        // Deleted.
                    ],
                ],
                <<<EOD
                /^Not sending notices because the auth_invitation\/autodeleteusersnoticedays setting is set to 0\.
                Deleting 2 users\.\.\.
                User temp2 with ID \d+ \(last seen at 2025-12-08T22:18:14\+08:00\) was deleted\.
                User temp3 with ID \d+ \(last seen at 2025-12-07T22:18:15\+08:00\) was deleted\.
                2 of 2 users were successfully deleted\.$/
                EOD, // Task output regex.
                [], // Notified users.
                ['temp2', 'temp3'], // Deleted users.
            ],
        ];
    }

    /**
     * Test for the {@see delete_temporary_users::execute()} function when automatic deletion is disabled.
     *
     * @covers ::execute
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_execute_when_disabled(): void {
        $this->resetAfterTest();
        $this->expectOutputString("Automatic deletion of temporary users is disabled. " .
            "Please enable the auth_invitation/autodeleteusers setting to activate this task.\n");

        set_config('autodeleteusers', false, 'auth_invitation');

        $task = new delete_temporary_users();
        $task->execute();
    }
}
