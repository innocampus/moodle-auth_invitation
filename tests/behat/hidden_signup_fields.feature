@auth @auth_invitation
Feature: Signing up for an account using an invitation when some fields are hidden from the signup form.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |
      | registerauth          | invitation        |
      | enrol_plugins_enabled | manual,invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |

  Scenario Outline: As an unregistered user, when I accept a valid invitation, then I do not see fields which are hidden from the signup form
    Given the following config values are set as admin:
      | <setting> | <value> | auth_invitation |
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I follow "Create new account"
    And "New account" "heading" should exist
    Then I <shouldorshouldnot> see "<field>"
    And I set the following fields to these values:
      | Username   | test1     |
      | Password   | Test-1234 |
      | First name | Test1     |
      | Last name  | User1     |
    And I click on "Create my new account" "button"
    Then "Signup complete" "heading" should exist
    And I open my profile in edit mode
    And the following fields match these values:
      | First name       | Test1             |
      | Last name        | User1             |
      | Email address    | test1@example.com |

    Examples:
      | setting                  | value | field     | shouldorshouldnot |
      | hidecityfieldonsignup    | 0     | City/town | should            |
      | hidecityfieldonsignup    | 1     | City/town | should not        |
      | hidecountryfieldonsignup | 0     | Country   | should            |
      | hidecountryfieldonsignup | 1     | Country   | should not        |
