<?php

namespace ivangrynenko\BehatSteps;

use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Behat\Context\Snippet\Generator\ContextSnippetGenerator;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Language\Language;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Trait IgContentTrait.
 *
 * @package ivangrynenko\BehatSteps
 */
trait IgContentTrait {

  /**
   * Entities by entity type and key.
   *
   * $entities array
   *   To delete after scenario.
   *
   * @var array
   */
  protected $entities = [];

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
    $this->storageEngine = new \stdClass();
    $this->nodeKeys = [
      'reference_fill',
      'system_url',
      'alias_url',
      'edit_url',
    ];
  }

  /**
   * @var \Drupal\DrupalExtension\Context\MinkContext
   */
  protected $minkContext;

  /**
   * @BeforeScenario
   */
  public function contentGetMinkContext(BeforeScenarioScope $scope) {
    /** @var \Behat\Behat\Context\Environment\InitializedContextEnvironment $environment */
    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext('Drupal\DrupalExtension\Context\MinkContext');
  }

  /**
   * @Given deleted any content of type :arg1
   */
  public function noContentOfType($arg1) {
    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['type' => $arg1]);

    if (empty($nodes)) {
      return;
    }
    foreach ($nodes as $node) {
      $node->delete();
    }
  }

  /**
   * @Given /^I scroll to element with id "([^"]*)"$/
   * @param string $id
   *
   * @javascript
   */
  public function iScrollToElementWithId($id) {
    $this->getSession()->executeScript(
      "
                var element = document.getElementById('" . $id . "');
                element.scrollIntoView( true );
            "
    );
  }

  /**
   * @Given I scroll to :arg1 element by xpath
   * @javascript
   */
  public function iScrollToElementByXpath($arg1) {
    $this->getSession()->executeScript(
      "
                var element = document.getElementByXpath('" . $arg1 . "');
                element.scrollIntoView( true );
            "
    );
  }

  /**
   * @Then /^I click by selector "([^"]*)" via JavaScript$/
   * @param string $selector
   *
   * @javascript
   */
  public function clickBySelector(string $selector) {
    $this->getSession()
      ->executeScript("document.querySelector('" . $selector . "').click()");
  }

  /**
   * @Given /^I set browser window size to "([^"]*)" x "([^"]*)"$/
   * @javascript
   */
  public function iSetBrowserWindowSizeToX($width, $height) {
    $this->getSession()->resizeWindow((int) $width, (int) $height, 'current');
  }

  /**
   * @Given no :vocabulary terms:
   */
  public function taxonomyDeleteTerms($vocabulary, TableNode $termsTable) {
    foreach ($termsTable->getColumn(0) as $name) {
      $terms = \Drupal::service('entity_type.manager')
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $name, 'vid' => $vocabulary]);
      /** @var \Drupal\taxonomy\Entity\Term $term */
      foreach ($terms as $term) {
        $term->delete();
      }
    }
  }

  /**
   * Wait for AJAX to complete.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertWaitOnAjaxRequest()
   *
   * @Given I wait for AJAX to complete
   */
  public function iWaitForAjaxToComplete() {
    $condition = <<<JS
    (function() {
      function isAjaxing(instance) {
        return instance && instance.ajaxing === true;
      }
      return (
        // Assert no AJAX request is running (via jQuery or Drupal) and no
        // animation is running.
        (typeof jQuery === 'undefined' || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
        (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || !Drupal.ajax.instances.some(isAjaxing))
      );
    }());
JS;
    $result = $this->getSession()->wait(300000, $condition);
    if (!$result) {
      throw new \RuntimeException('Unable to complete AJAX request.');
    }
  }

  /**
   * Creates content of a given type provided in the form:
   * | KEY         | title    | status | created           | field_reference_name |
   * | my node key | My title | 1      | 2014-10-17 8:00am | text key             |
   * | ...         | ...      | ...    | ...               | ...                  |
   *
   * @Given I create :bundle content:
   */
  public function iCreateNodes($bundle, TableNode $table) {
    $this->createNodes($bundle, $table->getHash());
  }

  /**
   * Creates content of a given type provided in the form:
   * | KEY                   | my node key       | ... |
   * | title                 | My title          | ... |
   * | status                | 1                 | ... |
   * | created               | 2014-10-17 8:00am | ... |
   * | field_reference_name  | text key          | ... |
   *
   * @Given I create large :bundle content:
   */
  public function iCreateLargeNodes($bundle, TableNode $table) {
    $this->createNodes($bundle, $this->getColumnHashFromRows($table));
  }

  /**
   * Creates content of the given type, provided in the form:
   * | KEY                  | my node key    |
   * | title                | My node        |
   * | Field One            | My field value |
   * | author               | Joe Editor     |
   * | status               | 1              |
   * | field_reference_name | text key       |
   * | ...                  | ...            |
   *
   * @Given I view a/an :bundle content:
   */
  public function iViewNode($bundle, TableNode $table) {
    $saved_array = $this->createNodes($bundle, $this->getColumnHashFromRows($table));
    // createNodes() returns array of saved nodes we are only concerned about
    // the last one created for this.
    $saved = array_pop($saved_array);
    $this->goToEntity($saved);
  }

  /**
   * View an existing entity/node by key value.
   *
   * @Given /^I view node "(?P<key>[^"]*)"$/
   * @Given /^I view entity "(?P<key>[^"]*)"$/
   */
  public function iViewKey($key) {
    $saved = $this->getEntityByKey($key);
    $this->goToEntity($saved);
  }

  /**
   * Creates entity of given entity type and bundle.
   * | KEY  | name | field_color  | field_reference_name  |
   * | Blue | Blue | 0000FF       | text key              |
   * | ...  | ...  | ...          | ...                   |
   *
   * @Given I create :entity_type of type :bundle:
   */
  public function iCreateEntity($entity_type, $bundle, TableNode $table) {
    $this->createEntities($entity_type, $bundle, $table->getHash());
  }

  /**
   * Creates entity of given entity type and bundle.
   * | KEY                   | Blue     | ... |
   * | name                  | Blue     | ... |
   * | field_color           | 0000FF   | ... |
   * | field_reference_name  | text key | ... |
   *
   * @Given I create large :entity_type of type :bundle:
   */
  public function iCreateLargeEntity($entity_type, $bundle, TableNode $table) {
    $this->createEntities($entity_type, $bundle, $this->getColumnHashFromRows($table));
  }

  /**
   * Create Menu link content.
   *
   * @Given /^I create menu_link_content:$/
   */
  public function iCreateMenuLinkContent(TableNode $table) {
    $table_hash = $table->getHash();
    foreach($table_hash as $link_hash) {
      if (empty($link_hash['title']) || empty($link_hash['uri']) || empty($link_hash['menu_name'])) {
        throw new \Exception("Menu title, uri, and menu_name are required.");
      }
      if (empty($link_hash['expanded'])) {
        $link_hash['expanded'] = 1;
      }
      $menu_array = [
        'title' => $link_hash['title'],
        'link' => ['uri' => $link_hash['uri']],
        'menu_name' => $link_hash['menu_name'],
        'expanded' => $link_hash['expanded'],
      ];
      // If parent uri & parent name set search in menu links for it.
      if (!empty($link_hash['parent_uri']) && !empty($link_hash['parent_title'])) {
        $query = Drupal::entityQuery('menu_link_content')
          ->condition('bundle', 'menu_link_content')
          ->condition('link__uri', $link_hash['parent_uri'])
          ->condition('menu_name', $link_hash['menu_name'])
          ->condition('title', $link_hash['parent_title']);
        $result = $query->execute();
        if (!empty($result)) {
          $parent_id = array_pop($result);
          $parent_menu_link = MenuLinkContent::load($parent_id);
          if (!empty($parent_menu_link)) {
            $menu_array['parent'] = 'menu_link_content:'
              . $parent_menu_link->uuid();
          }
        }
      }
      // If icon image set create image file.
      if (!empty($link_hash['icon_image'])) {
        $file = $this->createTestFile($link_hash['icon_image']);
        $options = [
          'menu_icon' => [
            'fid' => $file->id(),
          ],
        ];
        $menu_array['link']['options'] = serialize($options);
      }
      $menu_link = MenuLinkContent::create($menu_array);
      $menu_link->save();
      $this->saveEntity($menu_link);
    }
  }

  /**
   * Process fields from entity hash to allow referencing by key.
   *
   * @param $entity_hash array
   *   Array of field value pairs.
   * @param $entity_type string
   *   String entity type.
   */
  protected function preProcessFields(&$entity_hash, $entity_type) {
    foreach ($entity_hash as $field_name => $field_value) {
      // Get field info.
      $fiend_info = FieldStorageConfig::loadByName($entity_type, $field_name);
      if ($fiend_info == NULL || !in_array(($field_type = $fiend_info->getType()), ['entity_reference', 'entity_reference_revisions', 'image', 'file'])) {
        continue;
      }
      // Explode field value on ', ' to get values/keys.
      $field_values = explode(', ', $field_value);
      unset($entity_hash[$field_name]);
      $value_id = [];
      $target_revision_id = [];
      foreach ($field_values as $value_or_key) {
        if ($field_type == 'image' || $field_type == 'file') {
          $file = $this->createTestFile($value_or_key);
          $value_id[] = $file->id();
        }
        else {
          $entity_id = $this->getEntityIDByKey($value_or_key);
          $entity_revision_id = $this->getEntityRevisionIDByKey($value_or_key);
          if ($field_type == 'entity_reference') {
            // Set the target id.
            $value_id[] = $entity_id;
          }
          elseif ($field_type == 'entity_reference_revisions') {
            // Set target revision id.
            $target_id[] = $entity_id;
            $target_revision_id[] = $entity_revision_id;
          }
        }
      }
      if (!empty($value_id)) {
        $entity_hash[$field_name] = implode(', ', $value_id);
      }
      if (!empty($target_revision_id) && !empty($target_id)) {
        $entity_hash[$field_name . ':target_id'] = implode(', ', $target_id);
        $entity_hash[$field_name . ':target_revision_id'] = implode(', ', $target_revision_id);
      }
    }
  }

  /**
   * Create Nodes from bundle and TableNode column hash.
   *
   * @param $bundle string
   *   Bundle type id.
   * @param $hash array
   *   Table hash
   *
   * @return array
   *   Saved entities.
   */
  public function createNodes($bundle, $hash) {
    return $this->createEntities('node', $bundle, $hash);
  }

  /**
   * Create Keyed Entities
   *
   * @param $entity_type string
   *   Entity type id.
   * @param $bundle string
   *   Bundle type id.
   * @param $hash array
   *   Table hash
   *
   * @return array
   *   Saved entities.
   */
  protected function createEntities($entity_type, $bundle, $hash) {
    $saved = [];
    foreach ($hash as $entity_hash) {
      $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type);
      $entity_storage_keys = $entity_storage->getEntityType()->getKeys();
      if (!empty($entity_storage_keys['bundle']) && is_string($entity_storage_keys['bundle'])) {
        $bundle_key = $entity_storage_keys['bundle'];
        $entity_hash[$bundle_key] = $bundle;
      }
      // Allow KEY as optional.
      $entity_key = NULL;
      if (!empty($entity_hash['KEY'])) {
        $entity_key = $entity_hash['KEY'];
        unset($entity_hash['KEY']);
      }
      $this->preProcessFields($entity_hash, $entity_type);
      $entity_obj = (object) $entity_hash;
      $this->parseEntityFields($entity_type, $entity_obj);
      // Create entity.
      $entity = $entity_storage->create((array) $entity_obj);
      $entity->save();
      $saved[] = $entity;
      $this->saveEntity($entity, $entity_key);
    }
    return $saved;
  }

  /**
   * Saves entity by entity key.
   *
   * @param $entity_key
   *   Entity key value.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  protected function saveEntity(EntityInterface $entity, $entity_key = NULL) {
    $entity_type = $entity->getEntityTypeId();
    if ($entity_key != NULL) {
      $this->entities[$entity_type][$entity_key] = $entity;
    }
    else {
      $this->entities[$entity_type][] = $entity;
    }
  }

  /**
   * Get entity by key from created test scenario entities.
   *
   * @param $key string
   *   Key string
   *
   * @return mixed|\Drupal\Core\Entity\EntityInterface
   *   Entity.
   *
   * @throws \Exception
   */
  protected function getEntityByKey($key) {
    foreach ($this->entities as $entities) {
      if (!empty($entities[$key])) {
        return $entities[$key];
      }
    }
    $msg = 'Key "' . $key . '" does not match existing entity key';
    throw new \Exception($msg);
  }

  /**
   * Get entity id by key.
   *
   * @param $key string
   *   Key string to lookup saved entity.
   * @return mixed
   *   Entity id.
   */
  protected function getEntityIDByKey($key) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    if (($entity = $this->getEntityByKey($key)) != NULL) {
      return $entity->id();
    }
  }

  /**
   * Get entity revision id by key.
   *
   * @param $key string
   *   Key string to lookup saved entity.
   * @return mixed
   *   Entity revision id.
   */
  protected function getEntityRevisionIDByKey($key) {
    /* @var \Drupal\Core\Entity\EntityInterface $entity */
    if (($entity = $this->getEntityByKey($key)) != NULL) {
      if (!method_exists($entity, 'getRevisionId')) {
        $msg = 'Entity with Key "' . $key . '" entity does not have method getRevisionId()';
        throw new \Exception($msg);
      }
      return $entity->getRevisionId();
    }
  }

  /**
   * Get TableNode column hash from rows based TableNode table.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   From pipe delimited table input.
   * @return array
   *   A TableNode column hash.
   */
  public function getColumnHashFromRows(TableNode $table) {
    $hash = [];
    $rows = $table->getRowsHash();
    foreach ($rows as $field => $values) {
      if (is_array($values)) {
        foreach ($values as $key => $value) {
          $hash[$key][$field] = $value;
        }
      }
      elseif (empty($hash)) {
        $hash[] = $rows;
      }
    }
    return $hash;
  }

  /**
   * Load the page belonging to the entity provided.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   */
  public function goToEntity($entity) {
    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath($entity->toUrl()->toString()));
  }

  /**
   * Deletes all entities created during the scenario.
   *
   * @AfterScenario
   */
  public function cleanEntities() {
    foreach ($this->entities as $entity_type => $entities) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      foreach ($entities as $entity) {
        // Clean up the entity's alias, if there is one.
        if (method_exists($entity, 'tourl')) {
          try {
            $path = '/' . $entity->toUrl()->getInternalPath();
            $alias = \Drupal::service('path.alias_manager')
              ->getAliasByPath($path);
            if ($alias != $path) {
              \Drupal::service('path.alias_storage')
                ->delete(['alias' => $alias]);
            }
          } catch (Exception $e) {
            // do nothing
          }
        }
      }
      $storage_handler = \Drupal::entityTypeManager()->getStorage($entity_type);
      // If this is a Multiversion-aware storage handler, call purge() to do a
      // hard delete.
      if (method_exists($storage_handler, 'purge')) {
        $storage_handler->purge($entities);
      }
      else {
        $storage_handler->delete($entities);
      }
    }
  }

  /**
   * Create test file from name, it may use a real file from the mink file_path.
   *
   * @param $file_name string
   *   A file name the may exist in the mink file_path folder.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed|static
   *
   * @throws \Exception
   */
  public function createTestFile($file_name) {
    $file = str_replace('\\"', '"', $file_name);
    $file_destination = 'public://' . $file_name;
    if ($this->getMinkParameter('files_path')) {
      $file_path = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
      if (is_file($file_path)) {
        if (! ($file_destination = @file_unmanaged_copy($file_path, $file_destination))) {
          $msg = 'File copy fail, "' . $file_path . '" to ' . $file_destination;
          throw new \Exception($msg);
        }
      }
    }
    $file = File::create([
      'filename' => $file_name,
      'uri' => $file_destination,
      'status' => 1,
    ]);
    $file->save();
    $this->saveEntity($file);
    return $file;
  }

  /**
   * Throw exception if invalid $storage_key is provided.
   *
   * @param string $storage_key
   *   A storage key string.
   *
   * @throws \Exception
   */
  public function validateStorageEngineKey($storage_key) {
    if (!property_exists($this->storageEngine, $storage_key)) {
      $msg = 'Invalid $storage_key value "' . $storage_key . '" used.';
      throw new \Exception($msg);
    }
  }

  /**
   * Throw exception if invalid $node_key is provided.
   *
   * @param string $node_key
   *   A node key string.
   *
   * @throws \Exception
   */
  public function validateNodeKey($node_key) {
    if (!in_array($node_key, $this->nodeKeys)) {
      $msg = 'Invalid $node_key value used.';
      throw new \Exception($msg);
    }
  }

  /**
   * Get stored Node values based on node key and storage key.
   *
   * @param string $node_key
   *   A node key string.
   * @param string $storage_key
   *   A storage key string.
   *
   * @return string
   *   URL based on the Node key and storage key.
   *
   * @throws \Exception
   */
  public function getNodeValueFromStorageEngine($node_key, $storage_key) {
    $this->validateNodeKey($node_key);
    $this->validateStorageEngineKey($storage_key);
    $value = NULL;
    /* @var \Drupal\node\Entity\Node $node */
    $node = $this->storageEngine->{$storage_key};
    switch ($node_key) {
      case 'reference_fill':
        $value = $node->getTitle() . ' (' . $node->id() . ')';
        break;
      case 'system_url':
        $value = $node->url();
        break;
      case 'alias_url':
        $value = \Drupal::service('path.alias_manager')
          ->getAliasByPath($node->url());
        break;
      case 'edit_url':
        $value = $node->url('edit-form');
        break;
    }
    if (is_null($value)) {
      $msg = 'Invalid path returned from getNodeValueFromStorageEngine()';
      throw new \Exception($msg);
    }
    return $value;
  }

  /**
   * Creates a menu item with specified name in the specified menu.
   *
   * @Given /^I create an item "([^"]*)" in the "([^"]*)" menu$/
   */
  public function iCreateItemInTheMenu($menu_item, $menu_name) {
    $path = '/admin/structure/menu/manage/' . $menu_name . '/add';
    $this->getSession()->visit($this->locatePath($path));
    $element = $this->getSession()->getPage();
    $element->fillField('Menu link title', $menu_item);
    $element->fillField('Link', 'http://example.com');
    $element->checkField("Show as expanded");
    $element->findButton('Save')->click();
  }

  /**
   * Creates a term in the respective taxonomy.
   *
   * @Given /^I create a "([^"]*)" term in the "([^"]*)" taxonomy$/
   */
  public function iCreateTaxonomyTerm($term, $taxonomy_name) {
    $taxonomy = strtolower(str_replace(' ', '_', $taxonomy_name));
    $path = '/admin/structure/taxonomy/manage/' . $taxonomy . '/add';
    $this->getSession()->visit($this->locatePath($path));
    $element = $this->getSession()->getPage();
    $element->fillField('Name', $term);
    $element->findButton('Save')->click();
  }
}
