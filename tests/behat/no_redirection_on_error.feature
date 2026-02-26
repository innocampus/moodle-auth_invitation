@auth @auth_invitation
Feature: Signing up for an account using an invitation when redirection to signup is skipped due to an error.
  Background:
    Given the following config values are set as admin:
      | auth                  | invitation        |
      | registerauth          | invitation        |
      | enrol_plugins_enabled | manual,invitation |
    And the following "courses" exist:
      | shortname | fullname      |
      | C1        | Test course 1 |
    And the following "users" exist:
      | username      | email                     |
      | existinguser1 | existinguser1@example.com |

  Scenario: As an unregistered user, when I accept an invitation that was sent to an email address which is already in use, then I should not be redirected to signup
    Given the following "auth_invitation > invitations" exist:
      | token      | email                     | course | timeexpiration |
      | invalid123 | existinguser1@example.com | C1     | ## 1 day ##    |
    When I am on the "invalid123" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist

  Scenario: As an unregistered user, when I accept an invitation that was sent to a registered user, then I should not be redirected to signup
    Given the following "auth_invitation > invitations" exist:
      | token      | email            | course | timeexpiration | user          |
      | invalid123 | test@example.com | C1     | ## 1 day ##    | existinguser1 |
    When I am on the "invalid123" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist

  Scenario: As an unregistered user, when I reject an invitation that was sent to a registered user, then I should not be redirected to signup
    Given the following "auth_invitation > invitations" exist:
      | token      | email            | course | timeexpiration | user          |
      | invalid123 | test@example.com | C1     | ## 1 day ##    | existinguser1 |
    When I am on the "invalid123" "auth_invitation > reject invitation" page
    Then "Log in to Acceptance test site" "heading" should exist

  Scenario: As an unregistered user, when I accept an invitation but the auth_invitation plugin is not active, then I should not be redirected to signup
    Given the following config values are set as admin:
      | auth | email |
    And the following "auth_invitation > invitations" exist:
      | token    | email            | course | timeexpiration |
      | token123 | test@example.com | C1     | ## 1 day ##    |
    When I am on the "token123" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist

  Scenario: As an unregistered user, when I accept an invitation but the auth_invitation plugin is not selected for self-registration, then I should not be redirected to signup
    Given the following config values are set as admin:
      | registerauth | email |
    And the following "auth_invitation > invitations" exist:
      | token    | email            | course | timeexpiration |
      | token123 | test@example.com | C1     | ## 1 day ##    |
    When I am on the "token123" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist

  Scenario: As an unregistered user, when I accept an invitation but the enrol_invitation plugin is not active, then I should not be redirected to signup
    Given the following config values are set as admin:
      | enrol_plugins_enabled | manual |
    And the following "auth_invitation > invitations" exist:
      | token    | email            | course | timeexpiration |
      | token123 | test@example.com | C1     | ## 1 day ##    |
    When I am on the "token123" "auth_invitation > accept invitation" page
    Then "Log in to Acceptance test site" "heading" should exist
