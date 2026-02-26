@auth @auth_invitation
Feature: Signing up for an account using an invitation when automatic redirection to signup is disabled.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |                 |
      | registerauth          | invitation        |                 |
      | enrol_plugins_enabled | manual,invitation |                 |
      | redirecttosignup      | 0                 | auth_invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |

  Scenario: As an unregistered user, when I accept a valid invitation and automatic redirection to signup is disabled, then I must choose the right option on the login page to get to the signup form
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Log in to Acceptance test site" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
