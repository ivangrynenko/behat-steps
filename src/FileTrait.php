<?php

namespace ivangrynenko\BehatSteps;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Trait FileTrait.
 *
 * @package ivangrynenko\BehatSteps
 */
trait FileTrait {

  /**
   * Keeps track of media files added by tests so they can be cleaned up.
   *
   * @var array
   */
  public $media = [];

  /**
   * Keeps track of files added by tests so they can be cleaned up.
   *
   * @var array
   */
  public $files = [];

  /**
   * Storage Engine, a stdClass object to store values by key.
   *
   * @var \stdClass
   */
  private $storageEngine;
  /**
   * Valid $node_key values for test validation.
   *
   * @var array
   */
  private $nodeKeys;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
    $this->storageEngine = new stdClass();
    $this->nodeKeys = [
      'reference_fill',
      'system_url',
      'alias_url',
      'edit_url',
    ];
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

  /**
   * Cleans up media files after every scenario.
   *
   * @AfterScenario @media
   */
  public function cleanUpMediaFiles($event) {
    // Delete each file in the array.
    foreach ($this->media as $media_id => $field_name) {
      $media_entity = Media::load($media_id);
      $file_fields = $media_entity->get($field_name)->getValue();
      $media_entity->delete();
      unset($this->media[$media_id]);

      $file_field = reset($file_fields);
      if (!empty($file_field['target_id'])) {
        $file = File::load($file_field['target_id']);
        if (!empty($file)) {
          $file_uri = $file->getFileUri();
          $file->delete();
          if (file_exists(('public://' . $file_uri))) {
            if (unlink('public://' . $file_uri)) {
              echo '==> Clearing file: ' . $file_uri . '; ......  OK' . "\n";
            }
          }
        }
      }
    }
  }

  /**
   * Cleans up files after every scenario.
   *
   * @AfterScenario @file
   */
  public function cleanUpFiles($event) {
    // Delete each file in the array.
    foreach ($this->files as $id => $path) {
      $file = File::load($id);
      if (!empty($file)) {
        $file_uri = $file->getFileUri();
        $file->delete();
        unset($this->files[$id]);
        if (file_exists(('public://' . $file_uri))) {
          if (unlink('public://' . $file_uri)) {
            echo '==> Clearing file: ' . $file_uri . '; ......  OK' . "\n";
          }
        }
      }
    }
  }
}
