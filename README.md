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

use ivangrynenko\BehatSteps\FileTrait;
use ivangrynenko\BehatSteps\FormElementsTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends DrupalContext {

  use FieldTrait;
  use FormElementsTrait;

}
```

## Exceptions
- `\Exception` is thrown for all assertions.
- `\RuntimeException` is thrown for any unfulfilled requirements within a step. 

## Development

### Local environment setup
- Make sure that you have latest versions of all required software installed:
  - [Docker](https://www.docker.com/)
  - [Pygmy](https://pygmy.readthedocs.io/)
  - [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).  
- `pygmy up`
- `ahoy build` for Drupal 8 build or `DRUPAL_VERSION=7 ahoy build` for Drupal 7.
- Access built site at http://behat-steps.docker.amazee.io/  

Please note that you will need to rebuild to work on a different Drupal version.

Use `ahoy --help` to see the list of available commands.   

### Behat tests
After every `ahoy build`, a new installation of Drupal is created in `build` directory.
This project uses fixture Drupal sites (sites with pre-defined configuration)
in order to simplify testing (i.e., the test does not create a content type
but rather uses a content type created from configuration during site installation).

- Run all tests: `ahoy test-bdd`
- Run all scenarios in specific feature file: `ahoy test-bdd path/to/file`
- Run all scenarios tagged with `@wip` tag: `ahoy test-bdd -- --tags=wip`
- Tests tagged with `@d7` or `@d8` will be ran for Drupal 7 and Drupal 8 respectively.
- Tests tagged with both `@d7` and `@d8` are agnostic to Drupal version and will run for both versions. 

To debug tests from CLI:
- `ahoy debug`
- Set breakpoint and run tests - your IDE will pickup incoming debug connection.

To update fixtures:
- Make required changes in the install fixture site
- Run `ahoy cli drush cex -y` for Drupal 8 or `ahoy cli drush fua -y` for Drupal 7
- Run `ahoy update-fixtures` for Drupal 8 or `DRUPAL_VERSION=7 ahoy update-fixtures` for Drupal 7 to export configuration changes from build directory to the fixtures directory. 
