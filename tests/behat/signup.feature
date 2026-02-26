@auth @auth_invitation
Feature: Signing up for an account using an invitation.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |
      | registerauth          | invitation        |
      | enrol_plugins_enabled | manual,invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
      | C2        | Test course 2 |
    And the following "auth_invitation > invitations" exist:
      | token    | email                     | course | timeexpiration     | role           |
      | 1234asdf | test1@example.com         | C1     | ## 2 weeks ##      | student        |
      | foobar42 | test2@example.com         | C2     | ## 1 day ##        | teacher        |
      | token123 | test3@example.com         | C2     | ## 1 hour ##       | editingteacher |

  Scenario Outline: As an unregistered user, when I accept a valid invitation, then I can sign up for an account
    When I am on the "<token>" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I should see "You have been invited to a course on this site"
    And I should see "If you already have an account with a different email address, please contact the course organizers"
    And I follow "Create new account"
    And "New account" "heading" should exist
    And the following fields match these values:
      | Email address | <email> |
    And the "Email address" "field" should be disabled
    And I set the following fields to these values:
      | Username   | <username>  |
      | Password   | <password>  |
      | First name | <firstname> |
      | Last name  | <lastname>  |
      | City/town  | <city>      |
      | Country    | <country>   |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
    And I should see "Your account was created successfully."
    And I follow "Proceed to course"
    And "<course>" "heading" should exist
    And I click on "Participants" "link"
    And the following should exist in the "participants" table:
      | First name             | Roles  |
      | <firstname> <lastname> | <role> |
    And I open my profile in edit mode
    And the following fields match these values:
      | First name       | <firstname> |
      | Last name        | <lastname>  |
      | Email address    | <email>     |
      | City/town        | <city>      |
      | Select a country | <country>   |

    Examples:
      | token    | email             | course        | role                | username   | password       | firstname | lastname | city        | country       |
      | 1234asdf | test1@example.com | Test course 1 | Student             | test1      | Test-1234      | Test1     | User1    |             |               |
      | foobar42 | test2@example.com | Test course 2 | Non-editing teacher | test_user2 | FooBar42!?     | Not Ä.    | Pérson   | Los Angeles | United States |
      | token123 | test3@example.com | Test course 2 | Teacher             | liu_cixin  | 3_Body_Problem | 慈欣         | 刘       | Beijing     | China         |
