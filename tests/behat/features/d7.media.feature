@d7
Feature: Check that MediaTrait works for D7

  @api
  Scenario: Assert "When I attach the file :file to :field_name media field"
    Given managed file:
      | path              |
      | example_image.png |
    Given article content:
      | title             |
      | [TEST] Page title |
    Given I am logged in as a user with the "administrator" role
    And I visit "article" "[TEST] Page title"
    And I should not see an ".field-name-field-image img" element
    Then I edit "article" "[TEST] Page title"
    And I attach the file "example_image.png" to "field_image[und][0][fid]" media field
    When I press the 'Save' button
    Then I should see "[TEST] Page title"
    And I should see an ".field-name-field-image img" element

  @api @trait:D7\ContentTrait,D7\FileTrait,D7\MediaTrait
  Scenario: Assert that "I attach the file :file to :field_name media field" throws an exception when file does not exist
    Given some behat configuration
    And scenario steps:
      """
      Given managed file:
        | path              |
        | example_image.png |
      Given article content:
        | title             |
        | [TEST] Page title |
      Given I am logged in as a user with the "administrator" role
      And I visit "article" "[TEST] Page title"
      And I should not see an ".field-name-field-image img" element
      Then I edit "article" "[TEST] Page title"
      And I attach the file "non_existing_example_image.png" to "field_image[und][0][fid]" media field
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      Unable to find file "non_existing_example_image.png"
      """
