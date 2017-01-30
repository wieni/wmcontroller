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

## Caching

###  Config

Config is stored as service parameters:

e.g.:
```yaml
parameters:
    # Expiry rules
    # maxage = client side caching duration
    # s-maxage = server side caching duration (this can be drupal db or a cdn)
    wmcontroller.cache.expiry:

        # Determine max and s-max based on content-type and/or bundle.
        # _default is used when no definition is available for any given bundle.
        entities:
            node:
                _default: { maxage: 120, s-maxage: 300 }
                page:     { maxage: 300, s-maxage: 7200 }
                article:  { maxage: 300, s-maxage: 1200 }
                video:    { maxage: 300, s-maxage: 3600 }
            taxonomy_term:
                _default: { maxage: 120, s-maxage: 300 }

        # If the current page isn't rendering some kind of entity these regexes
        # will determine which maxage will be set.
        # The paths these regexes are matched against are the actual request paths,
        # not the route name or route path.
        paths:
            '^/$':           { maxage: 250, s-maxage: 3600 }
            '^/about$':      { maxage: 250, s-maxage: 3600 }
            '^/admin/.*$':   { maxage: 0, s-maxage: 0 }
            '^/user(/.*)?$': { maxage: 0, s-maxage: 0 }


    # Store the contents of the response and serve it.
    # If disabled, only tags will be stored.
    # This could be useful if the site is proxied by a cdn.
    wmcontroller.cache.store: true

    # Disables caching in its entirety, only add s-maxage and maxage headers.
    # (Also implies wmcontroller.cache.store = false)
    wmcontroller.cache.tags: true

    # Amount of items that should be purged during each cron run.
    # This also determines the amount of times the wmcontroller.purge event
    # is triggered.
    wmcontroller.cache.purge_per_cron: 100

```

### API

#### custom route / controller that renders an entity

(this is not necessary for the core entity routes)

```php

/** @var Drupal\Core\Entity\EntityInterface */
$entity = get();

/** @var Drupal\wmcontroller\Service\Cache\Dispatcher */
$dispatcher = injectedService('wmcontroller.cache.dispatcher');

$dispatcher->dispatchMainEntity($entity); // To determine maxages

// To attach the entity's cache tags to the current request
// Only required if the item is not being injected into a twig tpl.
$dispatcher->dispatchPresented($entity);
```

#### adding custom tags

```php
/** @var Drupal\wmcontroller\Service\Cache\Dispatcher */
$dispatcher = injectedService('wmcontroller.cache.dispatcher');

$dispatcher->dispatchTags(['front', 'article:list']);
```

#### purge a tag

```php
/** @var Drupal\wmcontroller\Service\Cache\Manager; */
$manager = injectedService('wmcontroller.cache.manager');
$manager->purgeByTag($tag);
```

#### purge your cdn

Listen for the `Drupal\wmcontroller\WmcontrollerEvents::CACHE_PURGE` event. Your listener will receive an instance of `Drupal\wmcontroller\Event\CachePurgeEvent`
