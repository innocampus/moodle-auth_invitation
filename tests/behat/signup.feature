@auth @auth_invitation
Feature: Signing up for an account using an invitation.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |
      | registerauth          | invitation        |
      | enrol_plugins_enabled | manual,invitation |
    Given the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |

  Scenario: As an unregistered user, when I accept a valid invitation, then I can sign up for an account with a custom username
    Given the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I should see "You have been invited to a course on this site"
    And I should see "If you already have an account with a different email address, please contact the course organizers"
    And I follow "Create new account"
    And "New account" "heading" should exist
    And the following fields match these values:
      | Email address | test1@example.com |
    And the "Email address" "field" should be disabled
    And I set the following fields to these values:
      | Username   | test1     |
      | Password   | Test-1234 |
      | First name | Test1     |
      | Last name  | User1     |
      | City/town  | Berlin    |
      | Country    | Germany   |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
    And I should see "Your account was created successfully."
    And I follow "Proceed to course"
    And "Test course 1" "heading" should exist
    And I click on "Participants" "link"
    And the following should exist in the "participants" table:
      | First name  | Roles   |
      | Test1 User1 | Student |
    And I open my profile in edit mode
    And the following fields match these values:
      | First name       | Test1             |
      | Last name        | User1             |
      | Email address    | test1@example.com |
      | City/town        | Berlin            |
      | Select a country | Germany           |

  Scenario: As an unregistered user, when I accept a valid invitation, then I can sign up for an account with an auto-generated username
    Given the following config values are set as admin:
      | generateusername | 1 | auth_invitation |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration | role    |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  | teacher |
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    And the following fields match these values:
      | Email address | test1@example.com |
    And the "Email address" "field" should be disabled
    And I set the following fields to these values:
      | Password   | Test-1234 |
      | First name | Test1     |
      | Last name  | User1     |
      | City/town  |           |
      | Country    |           |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
    And I follow "Proceed to course"
    And "Test course 1" "heading" should exist
    And I click on "Participants" "link"
    And the following should exist in the "participants" table:
      | First name  | Roles               |
      | Test1 User1 | Non-editing teacher |
    And I open my profile in edit mode
    And the following fields match these values:
      | First name       | Test1             |
      | Last name        | User1             |
      | Email address    | test1@example.com |
      | City/town        |                   |
      | Select a country |                   |

  Scenario: As an unregistered user, when I accept a valid invitation and automatic redirection to signup is disabled, then I must choose the right option on the login page to get to the signup form
    Given the following config values are set as admin:
      | redirecttosignup | 0 | auth_invitation |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration | role    |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  | teacher |
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Log in to Acceptance test site" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    And the following fields match these values:
      | Email address | test1@example.com |
    And the "Email address" "field" should be disabled
    And I set the following fields to these values:
      | Username   | test1     |
      | Password   | Test-1234 |
      | First name | Test1     |
      | Last name  | User1     |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
    And I follow "Proceed to course"
    And "Test course 1" "heading" should exist
    And I click on "Participants" "link"
    And the following should exist in the "participants" table:
      | First name  | Roles               |
      | Test1 User1 | Non-editing teacher |
    And I open my profile in edit mode
    And the following fields match these values:
      | First name       | Test1             |
      | Last name        | User1             |
      | Email address    | test1@example.com |
