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
 * Tests for the task to delete inactive users.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace auth_invitation;

use auth_invitation_generator;
use auth_plugin_invitation;
use context_system;
use core\exception\coding_exception;
use core\exception\moodle_exception;
use dml_exception;
use dml_read_exception;
use Exception;
use moodle_url;
use Throwable;

/**
 * Tests for the {@see auth_plugin_invitation} class.
 *
 * @package    auth_invitation
 * @copyright  2025 Lars Bonczek (@innoCampus, TU Berlin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \auth_plugin_invitation
 */
final class auth_plugin_invitation_test extends \advanced_testcase {
    /**
     * Test for the {@see auth_plugin_invitation::user_login()} function.
     *
     * @param array $config
     * @param array $users
     * @param string $username
     * @param string $password
     * @param bool|string $expected
     * @covers ::user_login
     * @dataProvider user_login_provider
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_user_login(array $config, array $users, string $username, string $password, bool|string $expected): void {
        $this->resetAfterTest();

        foreach ($config as $key => $value) {
            set_config($key, $value, 'auth_invitation');
        }
        foreach ($users as $user) {
            $this->getDataGenerator()->create_user($user);
        }

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        if (is_string($expected)) {
            $this->expectExceptionObject(new moodle_exception($expected, 'auth_invitation'));
        }

        $actual = $auth->user_login($username, $password);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for the {@see test_user_login} method.
     *
     * @return array[]
     */
    public static function user_login_provider(): array {
        return [
            'successful login' => [
                'config' => [],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'testuser',
                'password' => 'pass1234',
                'expected' => true,
            ],
            'wrong password' => [
                'config' => [],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'testuser',
                'password' => 'wrongpass42',
                'expected' => false,
            ],
            'wrong username' => [
                'config' => [],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'otheruser',
                'password' => 'pass1234',
                'expected' => false,
            ],
            'wrong mnethostid' => [
                'config' => [],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234', 'mnethostid' => 2],
                ],
                'username' => 'testuser',
                'password' => 'pass1234',
                'expected' => false,
            ],
            'failed login with allowed email' => [
                'config' => [
                    'prohibitedemailloginerror' => true,
                    'allowedemailpatterns' => '*@example.com',
                    'prohibitedemailpatterns' => '',
                ],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'test@example.com',
                'password' => 'pass1234',
                'expected' => false,
            ],
            'failed login with prohibited email' => [
                'config' => [
                    'prohibitedemailloginerror' => true,
                    'allowedemailpatterns' => '*@example.com',
                    'prohibitedemailpatterns' => '',
                ],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'test@acme.com',
                'password' => 'pass1234',
                'expected' => 'loginprohibitedbyemail',
            ],
            'failed login with prohibited email, but error turned off' => [
                'config' => [
                    'prohibitedemailloginerror' => false,
                    'allowedemailpatterns' => '*@example.com',
                    'prohibitedemailpatterns' => '',
                ],
                'users' => [
                    ['username' => 'testuser', 'password' => 'pass1234'],
                ],
                'username' => 'test@acme.com',
                'password' => 'pass1234',
                'expected' => false,
            ],
        ];
    }

    /**
     * Test for the {@see auth_plugin_invitation::user_signup()} function.
     *
     * @param array $invitation
     * @param array $user
     * @param array $config
     * @param moodle_exception|string|null $expectederror
     * @covers ::user_signup
     * @dataProvider user_signup_provider
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function test_user_signup(
        array $invitation,
        array $user,
        array $config = [],
        moodle_exception|string|null $expectederror = null
    ): void {
        global $DB, $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback();

        // Set required config values.
        $config = array_merge([
            'sendwelcomeemail' => true,
            'assignedroles' => '',
            'allowedemailpatterns' => '*',
            'prohibitedemailpatterns' => '',
        ], $config);
        foreach ($config as $key => $value) {
            set_config($key, $value, 'auth_invitation');
        }

        set_config('enrol_plugins_enabled', 'invitation');
        set_config('registerauth', 'invitation');

        // Create two test roles to be used in the `assignedroles` setting.
        $this->getDataGenerator()->create_role([
            'id' => 100,
            'name' => 'testrole1',
        ]);
        $this->getDataGenerator()->create_role([
            'id' => 101,
            'name' => 'testrole2',
        ]);

        // Create a dummy course.
        $course = $this->getDataGenerator()->create_course();

        // Create the invitation record.
        /** @var auth_invitation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_invitation');
        $invite = $generator->create_invitation(
            array_merge(
                ['courseid' => $course->id],
                $invitation
            )
        );

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        // Redirect emails and events.
        $emailsink = $this->redirectEmails();
        $eventsink = $this->redirectEvents();

        // Store plaintext password to try logging in later.
        $plaintextpassword = $user['password'] ?? '';

        // Prepare the user object with standard fields.
        require_once($CFG->dirroot . '/user/editlib.php');
        $user = signup_setup_new_user((object) $user);

        // Run the actual test.
        try {
            if (is_object($expectederror)) {
                $this->expectExceptionObject($expectederror);
            } else if (is_string($expectederror)) {
                $this->expectException($expectederror);
            }

            $actual = $auth->user_signup($user, notify: false); // TODO: Find a way to test with `notify: true`.

        } catch (Exception | Throwable $e) {
            // On error, we make sure that no email was sent out and no user account was created.
            // There might be events in the event sink, so we don't check for that.
            $this->assertEmpty($emailsink->get_messages());
            $this->assertFalse($DB->record_exists('user', ['username' => $user->username]));
            throw $e; // Rethrow.
        }
        $this->assertTrue($actual);

        // Verify that the user record exists and contains the expected data.
        $userrecord = $DB->get_record('user', ['username' => $user->username, 'mnethostid' => $CFG->mnet_localhost_id]);
        $this->assertNotFalse($userrecord);
        foreach ((array) $user as $field => $value) {
            if (in_array($field, ['username', 'password', 'invitationtoken'])) {
                continue;
            }
            $this->assertEquals($value, $userrecord->$field, "Unexpected value in field '$field' of new user record.");
        }
        $this->assertEquals('1', $userrecord->confirmed); // User should be confirmed automatically.

        // Confirm that the invitation's `userid` field was set.
        $this->assertEquals($userrecord->id, $DB->get_field('enrol_invitation', 'userid', ['id' => $invite->id]));

        // Confirm that the welcome email was sent out, if applicable.
        if (!empty($config['sendwelcomeemail'])) {
            $this->assertEquals(1, $emailsink->count());
            $email = $emailsink->get_messages()[0];
            $this->assertEquals("PHPUnit test site: account created", $email->subject);
            $encodedname = quoted_printable_encode($userrecord->firstname);
            $this->assertStringContainsStringIgnoringLineEndings(
                <<<EOD
                    Hi $encodedname,

                    Welcome to 'PHPUnit test site'!

                    Your account has been created successfully. If you have not already done
                    so, you may now access the course which you were invited to by following
                    the link in the invitation email.

                    Please note that the invitation link can only be used once. To access the
                    course in the future, please log in here using your chosen credentials and
                    select the course from the list on the 'My courses' page:

                    https://www.example.com/moodle/login/index.php
                    EOD,
                $email->body
            );
        } else {
            $this->assertEquals(0, $emailsink->count());
        }

        // Confirm that all necessary roles have been assigned.
        $assignedroleids = array_unique(array_filter(array_map('trim', explode(',', $config['assignedroles'] ?? '')), 'strlen'));
        foreach ($assignedroleids as $roleid) {
            $this->assertTrue(user_has_role_assignment($userrecord->id, intval($roleid), context_system::instance()->id));
        }

        // Confirm that there is one user_created event and count($assignedroleids) role_assigned events.
        $this->assertEquals(1 + count($assignedroleids), $eventsink->count());
        $this->assertInstanceOf(\core\event\user_created::class, $eventsink->get_events()[0]);
        for ($i = 0; $i < count($assignedroleids); $i++) {
            $this->assertInstanceOf(\core\event\role_assigned::class, $eventsink->get_events()[1 + $i]);
        }

        // Confirm that the new user can log in using their chosen password.
        $this->assertTrue($auth->user_login($user->username, $plaintextpassword));
    }

    /**
     * Data provider for the {@see test_user_signup} method.
     *
     * @return array[]
     */
    public static function user_signup_provider(): array {
        return [
            'successful signup' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'user' => [
                    'invitationtoken' => 'asdf1234',
                    'username' => 'testuser',
                    'password' => 'pass1234',
                    'email' => 'test@example.com',
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                    'city' => 'Berlin',
                    'country' => 'DE',
                ],
                'config' => [
                    'sendwelcomeemail' => true,
                    'assignedroles' => '100,101',
                ],
            ],
            'successful signup without welcome email or role assignments' => [
                'invitation' => [
                    'token' => 'foobar42',
                    'tokenused' => 0,
                    'timeexpiration' => time() + 60,
                    'email' => 'jane@acme.com',
                ],
                'user' => [
                    'invitationtoken' => 'foobar42',
                    'username' => 'jane',
                    'password' => 'securepassword',
                    'email' => 'jane@acme.com',
                    'firstname' => 'Jane',
                    'lastname' => 'Doe',
                ],
                'config' => [
                    'sendwelcomeemail' => false,
                    'assignedroles' => '',
                ],
            ],
            'missing invitation token' => [
                'invitation' => [
                    'token' => 'asdf1234',
                ],
                'user' => [
                    'username' => 'testuser',
                ],
                'expectederror' => new coding_exception('Missing invitationtoken in user data from signup form.'),
            ],
            'invalid invitation token' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() + DAYSECS * 14,
                ],
                'user' => [
                    'invitationtoken' => 'foobar',
                    'username' => 'testuser',
                ],
                'expectederror' => new moodle_exception('invalidinvite', 'auth_invitation'),
            ],
            'invalid invitation' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() - 1,
                ],
                'user' => [
                    'invitationtoken' => 'asdf1234',
                    'username' => 'testuser',
                ],
                'expectederror' => new moodle_exception('invalidinvite', 'auth_invitation'),
            ],
            'prohibited email' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'user' => [
                    'invitationtoken' => 'asdf1234',
                    'username' => 'testuser',
                    'email' => 'test@example.com',
                ],
                'config' => [
                    'allowedemailpatterns' => '',
                ],
                'expectederror' => new moodle_exception('signupprohibitedbyemail', 'auth_invitation'),
            ],
            'mismatching email' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'user' => [
                    'invitationtoken' => 'asdf1234',
                    'username' => 'testuser',
                    'email' => 'foo@bar.com',
                ],
                'expectederror' => new coding_exception('Email in user data from signup form does not match email in invitation.'),
            ],
            'error during signup' => [
                'invitation' => [
                    'token' => 'asdf1234',
                    'tokenused' => 0,
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'user' => [
                    'invitationtoken' => 'asdf1234',
                    'username' => 'testuser',
                    'password' => 'pass1234',
                    'email' => 'test@example.com',
                    'firstname' => 'Max',
                    'lastname' => 'Mustermann',
                ],
                'config' => [
                    'sendwelcomeemail' => true,
                    'assignedroles' => 'invalid',
                ],
                'expectederror' => dml_read_exception::class,
            ],
        ];
    }

    /**
     * Test for the {@see auth_plugin_invitation::get_invitation_token_from_session()} function.
     *
     * @param string|null $wantsurl
     * @param string|null $expected
     * @covers ::get_invitation_token_from_session
     * @dataProvider get_invitation_token_from_session_provider
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function test_get_invitation_token_from_session(string|null $wantsurl, string|null $expected): void {
        global $SESSION;

        $SESSION->wantsurl = $wantsurl;

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        $actual = $auth->get_invitation_token_from_session();

        unset($SESSION->wantsurl);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Data provider for the {@see test_get_invitation_token_from_session} method.
     *
     * @return array[]
     * @throws moodle_exception
     */
    public static function get_invitation_token_from_session_provider(): array {
        return [
            'wrong path' => [
                'wantsurl' => (new moodle_url('/wrong/path/enrol/invitation/enrol.php', ['token' => 'test']))->out(false),
                'expected' => null,
            ],
            'missing param' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php'))->out(false),
                'expected' => null,
            ],
            'reject invitation link' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => '1234abcd', 'reject' => 1]))->out(false),
                'expected' => null,
            ],
            'accept invitation link' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => '1234abcd']))->out(false),
                'expected' => '1234abcd',
            ],
            'invitation token is array' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => ['a', 'b', 'c']]))->out(false),
                'expected' => null,
            ],
            'invitation token contains invalid characters' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => '<%_malicious$!>']))->out(false),
                'expected' => 'malicious',
            ],
            'invitation token is completely invalid' => [
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => '<%_$!>']))->out(false),
                'expected' => null,
            ],
            'invitation token is uppercase' => [ // Since enrol_invitation is case-sensitive, we should also be.
                'wantsurl' => (new moodle_url('/enrol/invitation/enrol.php', ['token' => 'UPPERCASE']))->out(false),
                'expected' => 'UPPERCASE',
            ],
        ];
    }

    /**
     * Test for the {@see auth_plugin_invitation::get_valid_invitation()} function.
     *
     * @covers ::get_valid_invitation
     * @throws moodle_exception
     */
    public function test_get_valid_invitation(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        /** @var auth_invitation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_invitation');
        $invite1 = $generator->create_invitation([
            'courseid' => $course->id,
            'token' => '1234asdf',
            'tokenused' => 0,
            'timeexpiration' => time() + 60,
        ]);
        $generator->create_invitation([
            'courseid' => $course->id,
            'token' => 'foobar42',
            'tokenused' => 0,
            'timeexpiration' => time() - 1, // Just expired.
        ]);
        $generator->create_invitation([
            'courseid' => $course->id,
            'token' => 'someothertoken',
            'tokenused' => 1, // Already used.
            'timeexpiration' => time() + 60,
        ]);

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        // Enrol plugin is disabled -> all invitations are invalid.
        $this->assertNull($auth->get_valid_invitation('1234asdf'));
        $this->assertNull($auth->get_valid_invitation('foobar42'));
        $this->assertNull($auth->get_valid_invitation('someothertoken'));

        // Enable enrol plugin.
        set_config('enrol_plugins_enabled', 'invitation');

        $this->assertEquals($invite1, $auth->get_valid_invitation('1234asdf'));
        $this->assertNull($auth->get_valid_invitation('foobar42'));
        $this->assertNull($auth->get_valid_invitation('someothertoken'));
    }

    /**
     * Test for the {@see auth_plugin_invitation::validate_signup_prerequisites()} function.
     *
     * @param array $invitation
     * @param string|null $token
     * @param string|null $expectederror
     * @covers ::validate_signup_prerequisites
     * @dataProvider validate_signup_prerequisites_provider
     * @throws moodle_exception
     * @throws dml_exception
     */
    public function test_validate_signup_prerequisites(array $invitation, string|null $token, string|null $expectederror): void {
        $this->resetAfterTest();

        set_config('allowedemailpatterns', '*', 'auth_invitation');
        set_config('prohibitedemailpatterns', '*@forbidden.com', 'auth_invitation');
        set_config('enrol_plugins_enabled', 'invitation');

        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_user([
            'email' => 'existinguser@example.com',
        ]);

        /** @var auth_invitation_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('auth_invitation');
        $expectedinvite = $generator->create_invitation(
            array_merge(
                ['courseid' => $course->id],
                $invitation
            )
        );

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        if ($expectederror) {
            $this->expectExceptionObject(new moodle_exception($expectederror, 'auth_invitation'));
        }

        $actualinvite = $auth->validate_signup_prerequisites($token);

        $this->assertEquals($expectedinvite, $actualinvite);
    }

    /**
     * Data provider for the {@see test_validate_signup_prerequisites} method.
     *
     * @return array[]
     */
    public static function validate_signup_prerequisites_provider(): array {
        return [
            'missing token' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'token' => null,
                'expectederror' => 'signuponlywithinvite',
            ],
            'invalid token' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'token' => 'foobar',
                'expectederror' => 'invalidinvite',
            ],
            'invite already used' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '1',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'token' => '1234asdf',
                'expectederror' => 'invalidinvite',
            ],
            'invite expired' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() - 1,
                    'email' => 'test@example.com',
                ],
                'token' => '1234asdf',
                'expectederror' => 'invalidinvite',
            ],
            'account exists' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                    'userid' => 1,
                ],
                'token' => '1234asdf',
                'expectederror' => 'signupaccountexists',
            ],
            'prohibited email' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'mail@forbidden.com',
                ],
                'token' => '1234asdf',
                'expectederror' => 'signupprohibitedbyemail',
            ],
            'email exists' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'existinguser@example.com',
                ],
                'token' => '1234asdf',
                'expectederror' => 'signupaccountexists',
            ],
            'success' => [
                'invitation' => [
                    'token' => '1234asdf',
                    'tokenused' => '0',
                    'timeexpiration' => time() + DAYSECS * 14,
                    'email' => 'test@example.com',
                ],
                'token' => '1234asdf',
                'expectederror' => null,
            ],
        ];
    }

    /**
     * Test for the {@see auth_plugin_invitation::is_allowed_email()} function.
     *
     * @param string $allowedemailpatterns
     * @param string $prohibitedemailpatterns
     * @param array $examples
     * @covers ::is_allowed_email
     * @dataProvider is_allowed_email_provider
     * @throws moodle_exception
     */
    public function test_is_allowed_email(string $allowedemailpatterns, string $prohibitedemailpatterns, array $examples): void {
        $this->resetAfterTest();

        set_config('allowedemailpatterns', $allowedemailpatterns, 'auth_invitation');
        set_config('prohibitedemailpatterns', $prohibitedemailpatterns, 'auth_invitation');

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');
        foreach ($examples as $email => $expected) {
            $actual = $auth->is_allowed_email($email);
            $this->assertEquals($expected, $actual, "Unexpected result from `\$auth->is_allowed_email('$email')`.");
        }
    }

    /**
     * Data provider for the {@see test_is_allowed_email} method.
     *
     * @return array[]
     */
    public static function is_allowed_email_provider(): array {
        return [
            'empty patterns' => [
                'allowedemailpatterns' => '',
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => false,
                    'foobar@organization.com' => false,
                    '' => false,
                ],
            ],
            'all allowed, none prohibited' => [
                'allowedemailpatterns' => '*',
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => true,
                    'foobar@organization.com' => true,
                ],
            ],
            'all allowed, all prohibited' => [
                'allowedemailpatterns' => '*',
                'prohibitedemailpatterns' => '*',
                'examples' => [
                    'test@example.com' => false,
                    'foobar@organization.com' => false,
                ],
            ],
            'specific address allowed' => [
                'allowedemailpatterns' => 'test@example.com',
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => true,
                    'TEST@EXAMPLE.COM' => true,
                    'othertest@example.com' => false,
                    'foobar@organization.com' => false,
                ],
            ],
            'specific address prohibited' => [
                'allowedemailpatterns' => '*',
                'prohibitedemailpatterns' => 'test@example.com',
                'examples' => [
                    'test@example.com' => false,
                    'othertest@example.com' => true,
                    'foobar@organization.com' => true,
                ],
            ],
            'pattern with extra whitespace' => [
                'allowedemailpatterns' => "\n  test@example.com\t\n  \n ",
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => true,
                    'TEST@EXAMPLE.COM' => true,
                    'othertest@example.com' => false,
                    ' ' => false,
                    '  ' => false,
                ],
            ],
            'domain allowed, two exceptions' => [
                'allowedemailpatterns' => '*@organization.com',
                'prohibitedemailpatterns' => "exception1@organization.com\nexception2@organization.com",
                'examples' => [
                    'test@example.com' => false,
                    'foobar@organization.com' => true,
                    'other@organization.com' => true,
                    'exception1@organization.com' => false,
                    'exception2@organization.com' => false,
                    'email@subdomain.organization.com' => false,
                ],
            ],
            'domain and subdomains allowed' => [
                'allowedemailpatterns' => "*@organization.com\n*@*.organization.com",
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => false,
                    'foobar@organization.com' => true,
                    'other@organization.com' => true,
                    'email@subdomain.organization.com' => true,
                    'email@deeper.subdomain.organization.com' => true,
                ],
            ],
            'domain and subdomains allowed, one subdomain prohibited' => [
                'allowedemailpatterns' => "*@organization.com\n*@*.organization.com",
                'prohibitedemailpatterns' => '*@forbidden.organization.com',
                'examples' => [
                    'test@example.com' => false,
                    'foobar@organization.com' => true,
                    'other@organization.com' => true,
                    'email@subdomain.organization.com' => true,
                    'email@forbidden.organization.com' => false,
                ],
            ],
            'domain prohibited' => [
                'allowedemailpatterns' => '*',
                'prohibitedemailpatterns' => '*@organization.com',
                'examples' => [
                    'test@example.com' => true,
                    'foobar@organization.com' => false,
                    'other@organization.com' => false,
                    'email@subdomain.organization.com' => true,
                    'email@deeper.subdomain.organization.com' => true,
                ],
            ],
            'domain and subdomains prohibited' => [
                'allowedemailpatterns' => '*',
                'prohibitedemailpatterns' => "*@organization.com\n*@*.organization.com",
                'examples' => [
                    'test@example.com' => true,
                    'foobar@organization.com' => false,
                    'other@organization.com' => false,
                    'email@subdomain.organization.com' => false,
                    'email@deeper.subdomain.organization.com' => false,
                ],
            ],
            'complex patterns' => [
                'allowedemailpatterns' => "student_*@example.com\n"
                    . "user_???@*.organization.com\n"
                    . "[abc]_[0-9]@[!x].domain.com\n"
                    . '"\\[a\\]\\*_\\?\\\\"@localhost',
                'prohibitedemailpatterns' => '',
                'examples' => [
                    'test@example.com' => false,
                    'student_@example.com' => true,
                    'student_foobar@example.com' => true,
                    'student_1234@example.com' => true,
                    'foobar@test.organization.com' => false,
                    'user_123@organization.com' => false,
                    'user_123@test.organization.com' => true,
                    'user_abc@test.organization.com' => true,
                    'user_1234@test.organization.com' => false,
                    'a_1@a.domain.com' => true,
                    'c_7@f.domain.com' => true,
                    'd_1@a.domain.com' => false,
                    'a_x@a.domain.com' => false,
                    'a_1@x.domain.com' => false,
                    'aa_11@a.domain.com' => false,
                    '"[a]*_?\"@localhost' => true,
                ],
            ],
        ];
    }
}
