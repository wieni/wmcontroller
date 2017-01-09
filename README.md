# Wieni Controller

Use bundle-specific controllers for nodes and taxonomies.

## Installation

```
composer require wieni\wmcontroller
drush en wmcontroller
```

Visit `/admin/config/services/wmcontroller` and tell wmcontroller in which module it has to look for bundle-specific controllers.

### Controllers

Create bundle-specific controllers with the following convention:

`src\Controller\<entityType>\<bundle>Controller`

`<entityType>` and `<bundle>` are **singular and camelCased**.

For example:

`src\Controller\TaxonomyTerm\CategoryController` will be matched against a `taxonomy_term` with bundle `categories`.

We will call the `show()` method on your controller, so make sure your controller has this method.

```
// src/Controller/Node/ArticleController.php
<?php

namespace Drupal\mymodule\Controller\Node;

use Drupal\Core\Controller\ControllerBase;

class ArticleController extends ControllerBase
{

    public function show($node)
    {
        return [
            '#markup' => "Hello, i'm $node->id()"
        ];
    }

}
```