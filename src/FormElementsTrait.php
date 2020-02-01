<?php

namespace ivangrynenko\BehatSteps;

/**
 * Trait FormElementsTrait.
 *
 * @package ivangrynenko\BehatSteps
 */
trait FormElementsTrait {

  /**
   * @Given /^I select "([^"]*)" from "([^"]*)" chosen select$/
   */
  public function iSelectFromChosenSelect($option, $select) {
    $select = $this->fixStepArgument($select);
    $option = $this->fixStepArgument($option);

    $page = $this->getSession()->getPage();
    $field = $page->findField($select, TRUE);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value', $select);
    }

    $id = $field->getAttribute('id');
    $opt = $field->find('named', ['option', $option]);
    $val = $opt->getValue();

    $javascript = "jQuery('#$id').val('$val');
                  jQuery('#$id').trigger('chosen:updated');
                  jQuery('#$id').trigger('change');";

    $this->getSession()->executeScript($javascript);
  }

  /**
   * Checks if an option is present in the dropdown
   *
   * @Then /^I should see "([^"]*)" in the dropdown "([^"]*)"$/
   *
   * @param $value
   *   string The option string to be searched for
   * @param $field
   *   string The dropdown field selector
   * @param $fieldLabel
   *   string The label of the field in case $field is not a label
   */
  public function iShouldSeeInTheDropdown($value, $field, $fieldLabel = "") {
    if ($fieldLabel == "") {
      $fieldLabel = $field;
    }
    // Get the object of the dropdown field
    $dropDown = $this->getSession()->getPage()->findField($field);
    if (empty($dropDown)) {
      throw new \Exception('The page does not have the dropdown with label "' . $fieldLabel . '"');
    }
    // Get all the texts under the dropdown field
    $options = $dropDown->getText();
    if (strpos(trim($options), trim($value)) === FALSE) {
      throw new \Exception("The text " . $fieldLabel . " does not have the option " . $value . " " . $this->getSession()
          ->getCurrentUrl());
    }
  }

  /**
   * Checks if an option is not present in the dropdown
   *
   * @Then /^I should not see "([^"]*)" in the dropdown "([^"]*)"$/
   *
   * @param string $value
   *  The option string to be searched for
   * @param string $field
   *   The dropdown field label
   */
  public function iShouldNotSeeInTheDropdown($value, $field) {
    // get the object of the dropdown field
    $dropDown = $this->getSession()->getPage()->findField($field);
    if (empty($dropDown)) {
      throw new \Exception('The page does not have the dropdown with label "' . $field . '"');
    }
    // get all the texts under the dropdown field
    $options = $dropDown->getText();
    if (strpos(trim($options), trim($value)) !== FALSE) {
      throw new \Exception("The dropdown " . $field . " has the option " . $value . " but it should not be");
    }
  }

  /**
   * Checks, that form field with specified id|name|label|value has the <values>
   *
   * @param $field
   *    string The dropdown field selector
   * @param $table
   *    array The list of values to verify
   *
   * @Then /^I should see the following <values> in the dropdown "([^"]*)"$/
   */
  public function iShouldSeeTheFollowingValuesInTheDropdown($field, TableNode $table) {
    if (empty($table)) {
      throw new Exception("No values were provided");
    }
    foreach ($table->getHash() as $value) {
      $this->iShouldSeeInTheDropdown($value['values'], $field);
    }
  }

  /**
   * Checks, that form field with specified id|name|label|value don't have the
   * <values>
   *
   * @param $field
   *    string The dropdown field selector
   * @param $table
   *    array The list of values to verify
   *
   * @Then /^I should not see the following <values> in the dropdown "([^"]*)"$/
   */
  public function iShouldNotSeeTheFollowingValuesInTheDropdown($field, TableNode $table) {
    if (empty($table)) {
      throw new Exception("No values were provided");
    }
    foreach ($table->getHash() as $value) {
      $this->iShouldNotSeeInTheDropdown($value['values'], $field);
    }
  }

  /**
   * Selects the multiple dropdown(single select/multiple select) values
   *
   * @param $table
   *    array The list of values to verify
   * @When /^I select the following <fields> with <values>$/
   */
  public function iSelectTheFollowingFieldsWithValues(TableNode $table) {
    $multiple = TRUE;
    $table = $table->getHash();
    foreach ($table as $key => $value) {
      $select = $this->getSession()
        ->getPage()
        ->findField($table[$key]['fields']);
      if (empty($select)) {
        throw new \Exception("The page does not have the " . $table[$key]['fields'] . " field");
      }
      // The default true value for 'multiple' throws an error 'value cannot be an array' for single select fields
      $multiple = $select->getAttribute('multiple') ? TRUE : FALSE;
      $this->getSession()
        ->getPage()
        ->selectFieldOption($table[$key]['fields'], $table[$key]['values'], $multiple);
    }
  }

  /**
   * Checks if the given value is default selected in the given dropdown
   *
   * @param $option
   *   string The value to be looked for
   * @param $field
   *   string The dropdown field that has the value
   *
   * @Given /^I should see the option "([^"]*)" selected in "([^"]*)" dropdown$/
   */
  public function iShouldSeeTheOptionSelectedInDropdown($option, $field) {
    $selector = $field;
    // Some fields do not have label, so set the selector here
    if (strtolower($field) == "default notification") {
      $selector = "edit-projects-default";
    }
    $chk = $this->getSession()->getPage()->findField($field);
    // Make sure that the dropdown $field and the value $option exists in the dropdown
    $optionObj = $chk->findAll('xpath', '//option[@selected="selected"]');
    // Check if at least one value is selected
    if (empty($optionObj)) {
      throw new \Exception("The field '" . $field . "' does not have any options selected");
    }
    $found = FALSE;
    foreach ($optionObj as $opt) {
      if ($opt->getText() == $option) {
        $found = TRUE;
        break;
      }
    }
    if (!$found) {
      throw new \Exception("The field '" . $field . "' does not have the option '" . $option . "' selected");
    }
  }

  /**
   * Checks, that form field with specified id|name|label|value has specified value
   * Example: Then the "username" field should contain value:
   * """
   * multiline value line 1
   * multiline value line 2
   * """
   * Example: And the "username" field should contain value "bwayne"
   *
   * @Then /^the "(?P<field>(?:[^"]|\\")*)" field should contain value "(?P<value>(?:[^"]|\\")*)"$/
   * @Then /^the "(?P<field>(?:[^"]|\\")*)" field should contain value:$/
   */
  public function assertFieldContains($field, $value) {
    $field = $this->fixStepArgument($field);
    $value = $this->fixStepArgument($value);
    $this->assertSession()->fieldValueEquals($field, $value);
  }

  /**
   * @When I fill in the autocomplete :autocomplete with :text and click :popup
   * @javascript
   */
  public function fillInDrupalAutocomplete($autocomplete, $text, $popup) {
    $el = $this->getSession()->getPage()->findField($autocomplete);
    $el->focus();

    // Set the autocomplete text then put a space at the end which triggers
    // the JS to go do the autocomplete stuff.
    $el->setValue($text);
    $el->keyUp(' ');

    // Sadly this grace of 1 second is needed here.
    sleep(1);
    $this->minkContext->iWaitForAjaxToFinish();

    // Drupal autocompletes have an id of autocomplete which is bad news
    // if there are two on the page.
    $autocomplete = $this->getSession()->getPage()->findById('autocomplete');

    if (empty($autocomplete)) {
      throw new \RuntimeException(t('Could not find the autocomplete popup box'));
    }

    $popup_element = $autocomplete->find('xpath', "//div[text() = '{$popup}']");

    if (empty($popup_element)) {
      throw new \RuntimeException(t('Could not find autocomplete popup text @popup', [
        '@popup' => $popup
      ]));
    }

    $popup_element->click();
  }

  /**
   * Fills in WYSIWYG editor with specified id.
   *
   * @When I fill in :arg1 in WYSIWYG editor :arg2
   */
  public function iFillInInWysiwygEditor($text, $iframe) {
    try {
      $this->getSession()->switchToIFrame($iframe);
    } catch (Exception $e) {
      throw new \Exception(sprintf("No iframe with id '%s' found on the page '%s'.", $iframe, $this->getSession()
        ->getCurrentUrl()));
    }
    $this->getSession()
      ->executeScript("document.body.innerHTML = '<p>" . $text . "</p>'");
    $this->getSession()->switchToIFrame();
  }

  /**
   * Sets an id for the first iframe situated in the element specified by id.
   * Needed when wanting to fill in WYSIWYG editor situated in an iframe
   * without identifier.
   *
   * @Given the iframe in element :arg1 has id :arg2
   */
  public function theIframeInElementHasId($element_id, $iframe_id) {
    $function = <<<JS
(function(){
  var elem = document.getElementById("$element_id");
  var iframes = elem.getElementsByTagName('iframe');
  var f = iframes[0];
  f.id = "$iframe_id";
})()
JS;
    try {
      $this->getSession()->executeScript($function);
    } catch (Exception $e) {
      throw new \Exception(sprintf('No iframe found in the element "%s" on the page "%s".', $element_id, $this->getSession()
        ->getCurrentUrl()));
    }
  }

  /**
   * Quickly adding existing media to the field.
   *
   * Supported values have format like "media:1".
   *
   * @Given /^I fill media field "([^"]*)" with "([^"]*)"$/
   */
  public function iFillMediaFieldWith($field, $value) {
    $this->getSession()->getPage()->find('css',
      'input[id="' . $field . '"]')->setValue($value);
  }

  /**
   * Store the latest edited node in $storageEngine at the storage key given.
   *
   * @Given /^I store the Node as "(?P<storage_key>[^"]*)"$/
   */
  public function iStoreTheNodeAs($storage_key) {
    $node = node_get_recent(1);
    // Reset the array of node since it has only one object.
    $this->storageEngine->{$storage_key} = reset($node);
    $this->validateStorageEngineKey($storage_key);
  }

  /**
   * Fill in the field provided with the Node key value from stored key Node.
   *
   * @Given /^I fill in "(?P<field>[^"]*)" with stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function iFillInWithStoredNodeFrom($field, $node_key, $storage_key) {
    $value = $this->getNodeValueFromStorageEngine($node_key, $storage_key);
    if (!empty($field) && !empty($value)) {
      $this->getSession()->getPage()->fillField($field, $value);
    }
    else {
      $msg = 'Unable to fill ' . $field . ' from stored node data in "' . $storage_key . '"';
      throw new \Exception($msg);
    }
  }

  /**
   * Opens page based on the stored Node and node key value.
   *
   * @Given /^I go to stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function iGoToStoredNodeFrom($node_key, $storage_key) {
    $path = $this->getNodeValueFromStorageEngine($node_key, $storage_key);
    $this->visitPath($path);
  }

  /**
   * Validate the field provided has the Node key value from stored key Node.
   *
   * @Given /^The "(?P<field>[^"]*)" field should contain stored Node "([^"]*)" from "(?P<storage_key>[^"]*)"$/
   */
  public function theFieldShouldContainStoredNodeFrom($field, $node_key, $storage_key) {
    $path = $this->getNodeValueFromStorageEngine($node_key, $storage_key);
    $this->assertSession()
      ->fieldValueEquals(str_replace('\\"', '"', $field), str_replace('\\"', '"', $path));
  }

  /**
   * Clicks on element by css or xpath locator.
   *
   * @Given I click :selector element
   * @Given I click :selector in :area
   * @Given I click :selector :locator_type element
   */
  public function clickElement($selector, $locator_type = 'css') {
    $element = $this->getSession()->getPage()->find($locator_type, $selector);
    if (empty($element)) {
      $msg = 'There is no element with selector ' . $locator_type . ': "' . $selector . '"';
      throw new Exception($msg);
    }
    $element->focus();
    $element->click();
  }

  /**
   * @Given /^The current URL is "(?P<url>[^"]*)"$/
   */
  public function theCurrentURLIs($url) {
    $current_url = $this->getSession()->getCurrentUrl();
    if (!$current_url == $url) {
      $msg = 'URL "' . $url . '" does not match the current URL "' . $current_url . '"';
      throw new \Exception($msg);
    }
  }

  /**
   * @Given Element :element has text :text
   */
  public function elementHasText($element, $text) {
    $element_obj = $this->getSession()->getPage()->find('css', $element);
    // Find the text within the region
    $element_text = $element_obj->getText();
    if (strpos($element_text, $text) === FALSE) {
      throw new \Exception(sprintf("The text '%s' was not found in the element '%s' on the page %s", $text, $element, $this->getSession()->getCurrentUrl()));
    }
  }
}
