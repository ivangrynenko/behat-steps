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

  /**
   * @Given media file entities
   */
  public function mediaFileEntities(TableNode $table) {
    foreach ($table->getHash() as $nodeHash) {
      $node = (object) $nodeHash;

      // Create file first, so we can attach it to the media entity.
      if (empty($node->name)) {
        throw new \RuntimeException('"name" property is required');
      }
      if (empty($node->path)) {
        throw new \RuntimeException('"path" property is required');
      }
      if (empty($node->type)) {
        throw new \RuntimeException('"type" property is required - e.g. "image"');
      }
      $file_path = $path = ltrim($node->path, '/');

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

      // Pre-set field names
      switch ($node->type) {
        case 'image':
          $field_name = 'image';
          break;

        default:
          throw new \RuntimeException('Media entity type is not supported yet.');
          break;
      }

      $media = Media::create([
        'bundle' => $node->type,
        'name' => $node->name,
        'field_media_image' => [
          [
            'target_id' => $file->id(),
            'alt' => $node->alt,
            'title' => $node->title,
          ],
        ],
      ]);
      $media->save();

      $this->media[$media->id()] = $field_name;
    }
  }
}
