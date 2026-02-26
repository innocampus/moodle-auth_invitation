@auth @auth_invitation
Feature: Signing up for an account with an auto-generated username using an invitation.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |                 |
      | registerauth          | invitation        |                 |
      | enrol_plugins_enabled | manual,invitation |                 |
      | generateusername      | 1                 | auth_invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |

  Scenario: As an unregistered user, when I accept a valid invitation, then I can sign up for an account with an auto-generated username
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    And I should not see "Username"
    And I set the following fields to these values:
      | Password   | Test-1234 |
      | First name | Test1     |
      | Last name  | User1     |
      | City/town  |           |
      | Country    |           |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
