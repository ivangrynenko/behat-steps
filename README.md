# Behat Steps
Collection of Behat steps for Drupal 8 and Drupal 7 development.

# Why traits?
Usually, such packages implement own Drupal driver with several contexts, service containers and a lot of other useful architectural structures.
But for this simple library, using traits helps to lower entry barrier for usage, maintenance and support. 
This package may later be refactored to use proper architecture. 

# Installation
`composer require --dev ivangrynenko/behat-steps`

# Usage
In `composer.json':

```
"autoload": {
        "psr-4": {
            "ivangrynenko\\BehatSteps\\": "src/"
        }
    },
```

In `FeatureContext.php`:

```
<?php

use ivangrynenko\BehatSteps\IgContentTrait;
use ivangrynenko\BehatSteps\FileTrait;
use ivangrynenko\BehatSteps\FormElementsTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use IgContentTrait;
  use FieldTrait;
  use FormElementsTrait;

}
```

## Exceptions
- `\Exception` is thrown for all assertions.
- `\RuntimeException` is thrown for any unfulfilled requirements within a step. 
