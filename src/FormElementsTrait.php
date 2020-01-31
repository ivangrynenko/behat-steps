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
}
