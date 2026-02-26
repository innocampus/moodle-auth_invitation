@auth @auth_invitation
Feature: Signing up for an account using an invitation when signup is prohibited because of the email address.
  Background:
    Given the following config values are set as admin:
      | auth                    | invitation         |                 |
      | registerauth            | invitation         |                 |
      | enrol_plugins_enabled   | manual,invitation  |                 |
      | prohibitedemailpatterns | *@organization.com | auth_invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "auth_invitation > invitations" exist:
      | token    | email                 | course | timeexpiration |
      | 1234asdf | test@organization.com | C1     | ## 2 weeks ##  |

  Scenario: As an unregistered user, when I accept an invitation but signup is prohibited because of my email, then I should not be redirected to signup
    When I am on the "1234asdf" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist
