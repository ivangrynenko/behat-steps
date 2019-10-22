@d7
Feature: Check that email assertions work for D7

  @api
  Scenario: As a developer, I want to know that email step definitions work as
  expected.
    Given I enable the test email system
    When I send test email to "test@example.com" with:
      """
      Line one of the test email content
      Line two of the test email content
      Line three   with   tabs and    spaces
      """
    Then an email is sent to "test@example.com"
    And an email body contains:
      """
      Line two of the test email content
      """
    And an email body does not contain:
      """
      Line four of the test email content
      """
    And an email body contains exact:
      """
      Line three   with   tabs and    spaces
      """
    And an email body does not contain exact:
      """
      Line three with tabs and spaces
      """
    But an email body contains:
      """
      Line three with tabs and spaces
      """
    And an email body contains:
      """
      Line   three   with  tabs and spaces
      """
    And I disable the test email system

  @api @trait:D7\EmailTrait
  Scenario: Assert that "an email is sent to:" failure works
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        Line two of the test email content
        Line three   with   tabs and    spaces
        '''
      Then an email is sent to "other@example.com"

      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find email sent to "other@example.com" retrieved from test email collector.
      """

  @api @trait:D7\EmailTrait
  Scenario: Assert that "an email :field contains:" failure works
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        Line two of the test email content
        Line three   with   tabs and    spaces
        '''
      Then an email is sent to "test@example.com"
      And an email body contains:
        '''
        Non-existing line two of the test email content
        '''
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find email with text "Non-existing line two of the test email content" in field "body" retrieved from test email collector.
      """

  @api @trait:D7\EmailTrait
  Scenario: Assert that "an email :field contains:" exception is thrown when invalid field is provided
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        '''
      Then an email is sent to "test@example.com"
      And an email somefield contains:
        '''
        Line one of the test email content
        '''
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
       Invalid email field "somefield" was specified for assertion.
      """

  @api @trait:D7\EmailTrait
  Scenario: Assert that "email :field does not contain:" failure works
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        '''
      Then an email is sent to "test@example.com"
      And an email body does not contain:
        '''
        Line one of the test email content
        '''
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Found email with text "Line one of the test email content" in field "body" retrieved from test email collector, but should not.
      """

  @api @trait:D7\EmailTrait
  Scenario: Assert that "email :field does not contain:" exception is thrown when invalid field is provided
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        '''
      Then an email is sent to "test@example.com"
      And an email otherfield does not contain:
        '''
        Mon-existing line one of the test email content
        '''
      """
    When I run "behat --no-colors"
    Then it should fail with an exception:
      """
      Invalid email field "otherfield" was specified for assertion.
      """

  @api @trait:D7\EmailTrait
  Scenario: Assert that "an email :field contains exact:" failure works
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line three   with   tabs and    spaces
        '''
      Then an email is sent to "test@example.com"
      And an email body contains exact:
        '''
        Line three with tabs and spaces
        '''
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
      Unable to find email with exact text "Line three with tabs and spaces" in field "body" retrieved from test email collector.
      """

  Scenario: As a developer, I want to know that test email system is activated
  as before and after scenario steps.
    Given I enable the test email system
    When I send test email to "test@example.com" with:
      """
      Line one of the test email content
      Line two of the test email content
      Line three of the test email content
      """
    Then an email is sent to "test@example.com"
    And an email "body" contains:
      """
      Line two of the test email content
      """
    And an email body does not contain:
      """
      Line four of the test email content
      """
    And I disable the test email system

  Scenario: As a developer, I want to know that test email system queue clearing
  step is working.
    Given I enable the test email system
    When I send test email to "test@example.com" with:
      """
      Line one of the test email content
      Line two of the test email content
      Line three of the test email content
      """
    Then an email is sent to "test@example.com"
    And an email body contains:
      """
      Line two of the test email content
      """
    And an email body does not contain:
      """
      Line four of the test email content
      """
    When I clear the test email system queue
    And an email body does not contain:
      """
      Line two of the test email content
      """
    And I disable the test email system

  @email
  Scenario: As a developer, I want to know that test email system is automatically
  activated when @email tag is added to the scenario.
    When I send test email to "test@example.com" with:
      """
      Line one of the test email content
      Line two of the test email content
      Line three of the test email content
      """
    Then an email is sent to "test@example.com"
    And an email body contains:
      """
      Line two of the test email content
      """

  @email
  Scenario Outline: As a developer, I want to know that following a link from
  the email is working.
    Given I send test email to "test@example.com" with:
      """
      Line one of the test email content
      "<content>"
      Line two of the test email content
      """
    Then an email is sent to "test@example.com"

    And I follow the link number "<number>" in the email with the subject:
      """
      Test Email
      """
    Then the response status code should be 200
    And I should see "Example Domain"
    Examples:
      | content                                                       | number |
      | http://example.com                                            | 1      |
      | http://www.example.com                                        | 1      |
      | www.example.com                                               | 1      |
      | Link is a part of content http://example.com                  | 1      |
      | http://1.example.com http://example.com  http://3.example.com | 2      |
      | http://1.example.com http://2.example.com  http://example.com | 3      |

  @email
  Scenario: As a developer, I want to know that no emails assertions works as expected.
    Given no emails were sent
    Given I send test email to "test@example.com" with:
      """
      Line one of the test email content
      "<content>"
      Line two of the test email content
      """
    Then an email is sent to "test@example.com"

    When I clear the test email system queue
    Then no emails were sent

  @api @trait:D7\EmailTrait
  Scenario: Assert that "no emails were sent" failure works
    Given some behat configuration
    And scenario steps:
      """
      Given I enable the test email system
      When I send test email to "test@example.com" with:
        '''
        Line one of the test email content
        Line two of the test email content
        Line three   with   tabs and    spaces
        '''
      Then an email is sent to "test@example.com"
      Then no emails were sent
      """
    When I run "behat --no-colors"
    Then it should fail with an error:
      """
       Expected no emails to be sent, but sent "1" email(s).
      """
