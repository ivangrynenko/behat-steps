@d7
Feature: Check that ContentTrait works for D7

  @api
  Scenario: Assert visiting page with title of specified content type
    Given page content:
      | title             |
      | [TEST] Page title |
    When I am logged in as a user with the "administrator" role
    And I visit "page" "[TEST] Page title"
    Then I should see "[TEST] Page title"

  @api @trait:D7\ContentTrait
  Scenario: Assert visiting non-existing page with title of specified content type should fail
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And I visit "page" "[TEST] Page title"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find "page" page "[TEST] Page title"
      """

  @api
  Scenario: Assert editing page with title of specified content type
    Given page content:
      | title             |
      | [TEST] Page title |
    When I am logged in as a user with the "administrator" role
    And I edit "page" "[TEST] Page title"
    Then I should see "[TEST] Page title"

  @api @trait:D7\ContentTrait
  Scenario: Assert editing non-existing page with title of specified content type should fail
    Given some behat configuration
    And scenario steps:
      """
      Given I am logged in as a user with the "administrator" role
      And I edit "page" "[TEST] Page title"
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find "page" page "[TEST] Page title"
      """

  @api
  Scenario: Assert removing page with title and specified type
    Given page content:
      | title             |
      | [TEST] Page title |
    When I am logged in as a user with the "administrator" role
    And I go to "content/test-page-title"
    Then I should get a 200 HTTP response
    When no page content:
      | title             |
      | [TEST] Page title |
    And I go to "content/test-page-title"
    Then I should get a 404 HTTP response
