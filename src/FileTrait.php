<?php

namespace ivangrynenko\BehatSteps\D8;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;

/**
 * Trait FileTrait.
 *
 * @package ivangrynenko\BehatSteps\D8
 */
trait FileTrait {

  /**
   * The mink context.
   *
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $contentMinkContext;

  /**
   * @BeforeScenario
   */
  public function contentGetMinkContext(BeforeScenarioScope $scope) {
    $this->contentMinkContext = $scope->getEnvironment()
      ->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * @Given managed file:
   */
  public function fileCreateManaged(TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      if (empty($node->path)) {
        throw new \RuntimeException('"path" property is required');
      }
      $path = ltrim($node->path, '/');

      // Get fixture file path.
      if ($this->getMinkParameter('files_path')) {
        $full_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
        if (is_file($full_path)) {
          $path = $full_path;
        }
      }

      if (!is_readable($path)) {
        throw new \RuntimeException('Unable to find file ' . $path);
      }

      $destination = 'public://' . basename($path);
      $file = file_save_data(file_get_contents($path), $destination, FILE_EXISTS_REPLACE);

      if (!$file) {
        throw new \RuntimeException('Unable to save managed file ' . $path);
      }

      $this->files[$file->id()] = $path;
    }
  }
}
