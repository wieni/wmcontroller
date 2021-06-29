Wieni Controller
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmcontroller/v/stable)](https://packagist.org/packages/wieni/wmcontroller)
[![Total Downloads](https://poser.pugx.org/wieni/wmcontroller/downloads)](https://packagist.org/packages/wieni/wmcontroller)
[![License](https://poser.pugx.org/wieni/wmcontroller/license)](https://packagist.org/packages/wieni/wmcontroller)

> Adds support for bundle-specific controllers for Drupal 8 entities.

## Why?
- Improve the developer experience of the Entity API by providing the
 ability to render entities of different bundles in different ways.
- A new way of building layouts: gather your data in the controller and use it to render a Twig template. Inspired by
 Laravel and other MVC frameworks. Completely optional.

## Installation

This package requires PHP 7.1 and Drupal 8 or higher. It can be
installed using Composer:

```bash
 composer require wieni/wmcontroller
```

You should also include the patch from [#2638686](https://www.drupal.org/node/2638686) if you're getting early rendering errors in your controllers.

## Configuration

Before you get started, make sure you have configured at least the `module` and `path` options
 or the module will not work.

Configuration is stored as service parameters. You can override these in a service YAML file defined in 
 `$settings['container_yamls']` or in the `services.yml` file of a (custom) module.

```yaml
parameters:
    # Main wmcontroller settings
    wmcontroller.settings:

        # The module that has controllers for your entities
        # and if theme (below) is left empty also where your templates ought to be.
        module: ''

        # The theme where your templates are stored (optional)
        theme: ''

        # The path to the folder your templates are stored.
        # (relative to your module / theme directory)
        path: 'templates'

        # The controller responsible for forwarding to bundle-specific controllers.
        # Only override this if you know what you're doing.
        frontcontroller: 'Drupal\wmcontroller\Controller\FrontController'

        # Throw a 404 NotFoundHttpException when an entity is not translated
        # in the current language. ( /en/node/123 gives 404 if node/123 has no
        # en translation )
        404_when_not_translated: true

        # Routes to never reroute through the front controller
        ignore_routes: []
```

## How does it work?

### Creating controllers

- Create bundle-specific controllers by creating new classes with the following naming convention:
    > `src\Controller\<entityType>\<bundle>Controller`
    >                                                                                                  
    > (`<entityType>` and `<bundle>` are **singular and camelCased**)

    For example: `src\Controller\TaxonomyTerm\CategoryController` will be matched against a `taxonomy_term` with bundle 
 `category`.

- This module will always call the `show` method on the controller class.

- A `ControllerBase` class including [`ViewBuilderTrait`](src/Controller/ViewBuilderTrait.php), 
 [`MainEntityTrait`](src/Controller/MainEntityTrait.php) and 
 [`RedirectBuilderTrait`](src/Controller/RedirectBuilderTrait.php) is provided, but extending this class is not required.

#### Example
```php
// src/Controller/Node/ArticleController.php
<?php

namespace Drupal\mymodule\Controller\Node;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;

class ArticleController extends ControllerBase
{
    public function show(NodeInterface $node)
    {
        return [
            '#theme' => 'article_node',
            '#node' => $node,
        ];
    }
}
```

### Rendering Twig templates
Using the [`ViewBuilder`](src/Service/ViewBuilder.php) class, you can easily render Twig
 templates without having to mess with render arrays.
 
This module automatically resolves view builders to render arrays, so it's safe to return instances of this class 
 in controllers.
 
The easiest way of building views is using the `view` method included in [ControllerBase](src/Controller/ControllerBase.php) and [ViewBuilderTrait](src/Controller/ViewBuilderTrait.php). Just pass
 the template name, any parameters and you're good to go. 

The template name is the path to the template file, but with dots as path separators and without the file extension.
 Note that you can only use templates in the configured theme and path.

#### Example
```php
// src/Controller/Node/ArticleController.php
<?php

namespace Drupal\mymodule\Controller\Node;

use Drupal\Core\Controller\ControllerBase;
use Drupal\mymodule\Entity\Node\Article; # See wieni/wmmodel

class ArticleController extends ControllerBase
{
    public function show(Article $article)
    {
        // Loads mytheme/templates/article/detail.html.twig
        return $this->view(
            'article.detail',
            [
                'article' => $article,
            ]
        );
    }
}
```

### Accessing the main entity
It's often useful to access the main entity of the current request, e.g. on canonical or edit routes.
It has always been possible to access this entity by extracting it from the route parameters of the current route match,
 but the [`MainEntity`](src/Service/MainEntity.php) service makes that easier.
 
Apart from having easier access to the entity, it's also possible to manually set the main entity of custom routes 
 using the [MainEntityTrait](src/Controller/MainEntityTrait.php) or the `wmcontroller.main_entity` service directly.  

If the [`wmpage_cache`](https://github.com/wieni/wmpage_cache) module is installed, this main entity is also used to 
 determine cachability metadata of the current request.
 
## Changelog
All notable changes to this project will be documented in the
[CHANGELOG](CHANGELOG.md) file.

## Security
If you discover any security-related issues, please email
[security@wieni.be](mailto:security@wieni.be) instead of using the issue
tracker.

## License
Distributed under the MIT License. See the [LICENSE](LICENSE) file
for more information.
