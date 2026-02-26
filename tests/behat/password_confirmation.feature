@auth @auth_invitation
Feature: Signing up for an account using an invitation when password confirmation is required.
  Background:
    Given the following config values are set as admin:
      | auth                    | invitation        |                 |
      | registerauth            | invitation        |                 |
      | enrol_plugins_enabled   | manual,invitation |                 |
      | confirmpasswordonsignup | 1                 | auth_invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |

  Scenario: As an unregistered user, when I sign up using a valid invitation and password confirmation is required, then I need to confirm my password
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    And I set the following fields to these values:
      | Username         | test1     |
      | Password         | Test-1234 |
      | Confirm password | Test-1234 |
      | First name       | Test1     |
      | Last name        | User1     |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist

  Scenario: As an unregistered user, when I sign up using a valid invitation and password confirmation is required and I type the wrong password, then I should see an error message
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    And I set the following fields to these values:
      | Username         | test1     |
      | Password         | Test-1234 |
      | Confirm password | Test-123  |
      | First name       | Test1     |
      | Last name        | User1     |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should not exist
    And I should see "Passwords do not match"
