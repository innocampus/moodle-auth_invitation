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
use core\exception\moodle_exception;
use moodle_url;

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
     * Test for the {@see auth_plugin_invitation::get_invitation_token_from_session()} function.
     *
     * @covers ::get_invitation_token_from_session
     * @dataProvider get_invitation_token_from_session_provider
     * @throws moodle_exception
     */
    public function test_get_invitation_token_from_session(string|null $wantsurl, string|null $expected): void {
        global $SESSION;

        $SESSION->wantsurl = $wantsurl;

        /** @var auth_plugin_invitation $auth */
        $auth = get_auth_plugin('invitation');

        $actual = $auth->get_invitation_token_from_session();

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
     * @covers ::validate_signup_prerequisites
     * @dataProvider validate_signup_prerequisites_provider
     * @throws moodle_exception
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
