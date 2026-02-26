@auth @auth_invitation
Feature: Signing up for an account using an invitation when accepting invitations with mismatching emails is allowed.
  Background:
    Given the following config values are set as admin:
      | auth                   | invitation        |                  |
      | registerauth           | invitation        |                  |
      | enrol_plugins_enabled  | manual,invitation |                  |
      | allowmismatchingemails | 1                 | enrol_invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email             | course | timeexpiration |
      | 1234asdf | test1@example.com | C1     | ## 2 weeks ##  |

  Scenario: As a logged out user, when I accept a valid invitation and accepting invitations with mismatching emails is allowed, then I am giving the option to log in instead of signing up
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    And "Accept invitation" "heading" should exist
    And I should see "You have been invited to a course on this site"
    And I should see "If you already have an account with a different email address, please instead use the button \"I already have an account\" to log in."
    And "Create new account" "link" should exist
    And I follow "I already have an account"
    And "Log in to Acceptance test site" "heading" should exist
