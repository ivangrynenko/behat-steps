<?php

namespace IntegratedExperts\BehatSteps\D8;

use Behat\Gherkin\Node\TableNode;

/**
 * Trait FileTrait.
 *
 * @package IntegratedExperts\BehatSteps\D8
 */
trait FileTrait {

  /**
   * Delete managed files defined by provided properties.
   *
   * @code
   * Given no managed files:
   * | filename      |
   * | myfile.jpg    |
   * | otherfile.jpg |
   * @endcode
   *
   * @Given no managed files:
   */
  public function contentDeleteManagedFiles(TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $hash) {
      $files = file_load_multiple([], $hash);
      file_delete_multiple(array_keys($files));
    }
  }

}
