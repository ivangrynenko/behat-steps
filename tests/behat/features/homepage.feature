@d8
Feature: Homepage

  Ensure that homepage is displayed as expected.

  @api
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    Then I save screenshot

  @api @javascript
  Scenario: Anonymous user visits homepage
    Given I go to the homepage
    And I set browser window size to "1600" x "1600"
    Then I save screenshot
