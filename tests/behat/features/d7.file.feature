@d7
Feature: Check that FileTrait works for D7

  @api
  Scenario: Assert "Given managed file:"
    When I am logged in as a user with the "administrator" role
    Given managed file:
      | path                                |
      | example_document.pdf                |
      | example_image.png                   |
      | example_audio.mp3                   |
      | https://via.placeholder.com/150.jpg |
    And "example_document.pdf" file object exists
    And "example_image.png" file object exists
    And "example_audio.mp3" file object exists
    And "150.jpg" file object exists

  @api @trait:D7\FileTrait
  Scenario: Assert that "managed file:" failure works
    Given some behat configuration
    And scenario steps:
      """
      When I am logged in as a user with the "administrator" role
      Given managed file:
        | path                                |
        | non_existing_file.pdf               |
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      Unable to find file "non_existing_file.pdf"
      """

  @api @trait:D7\FileTrait
  Scenario: Assert that "managed file:" throws exception when "path" field is not provided
    Given some behat configuration
    And scenario steps:
      """
      When I am logged in as a user with the "administrator" role
      Given managed file:
        | filename                            |
        | non_existing_file.pdf               |
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      Missing required field "path"
      """

  @api
  Scenario: Assert "Given no managed files:"
    When I am logged in as a user with the "administrator" role
    Given managed file:
      | path                 |
      | example_document.pdf |
    And "example_document.pdf" file object exists
    When no managed files:
      | filename             |
      | example_document.pdf |
    Then "example_document.pdf" file object does not exist

  @api @trait:D7\FileTrait
  Scenario: Assert that "no managed files:" throws exception if "filename" field is not provided
    Given some behat configuration
    And scenario steps:
      """
      When I am logged in as a user with the "administrator" role
      Given managed file:
        | path                 |
        | example_document.pdf |
      And "example_document.pdf" file object exists
      When no managed files:
        | path                 |
        | example_document.pdf |
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      Missing required field "filename".
      """
