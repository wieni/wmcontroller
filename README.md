<a href="https://www.wieni.be">
    <img src="https://www.wieni.be/themes/custom/drupack/logo.svg" alt="Wieni logo" title="Wieni" align="right" height="60" />
</a>

Wieni Controller
======================

[![Latest Stable Version](https://poser.pugx.org/wieni/wmcontroller/v/stable)](https://packagist.org/packages/wieni/wmcontroller)
[![Total Downloads](https://poser.pugx.org/wieni/wmcontroller/downloads)](https://packagist.org/packages/wieni/wmcontroller)
[![License](https://poser.pugx.org/wieni/wmcontroller/license)](https://packagist.org/packages/wieni/wmcontroller)

> Use bundle-specific controllers for nodes and taxonomies.

## Installation

```
composer require wieni\wmcontroller
drush en wmcontroller
```

configure wmcontroller in your services.yml (see config below)

### Controllers

Create bundle-specific controllers with the following convention:

`src\Controller\<entityType>\<bundle>Controller`

`<entityType>` and `<bundle>` are **singular and camelCased**.

For example:

`src\Controller\TaxonomyTerm\CategoryController` will be matched against a `taxonomy_term` with bundle `categories`.

We will call the `show()` method on your controller, so make sure your controller has this method.

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
        // Loads mytheme/src/templates/article/detail.html.twig
        return $this->view(
            'article.detail',
            [
                'article' => $article,
            ]
        );
    }
}
```

## Presenters

Auto convert models into presenters when they are `@included`.

### Config

Add `\Drupal\wmcontroller\Entity\AbstractPresenter::class` to
`$settings['twig_sandbox_whitelisted_classes']`

e.g.:
```php
$settings['twig_sandbox_whitelisted_classes'] = [
    \Drupal\wmcontroller\Entity\AbstractPresenter::class,
    \Drupal\Core\Template\Attribute::class,
];
```

### Usage

Model.php:

```php
class Model ... implements \Drupal\wmcontroller\Entity\HasPresenterInterface
{
    public function getPresenterService()
    {
        return 'mymodule.presenter.entity_type.model';
    }
}
```

ModelPresenter.php:

```php
class ModelPresenter extends \Drupal\wmcontroller\Entity\AbstractPresenter
{
    public function language()
    {
        return $this->entity->language()->getName();
    }
}
```

mymodule.services.yml:

```yaml
services:
    mymodule.presenter.entity_type.model:
        class: Drupal\mymodule\Entity\Presenter\EntityType\ModelPresenter
        parent: wmcontroller.presenter
        shared: false # !
```

## Caching

Handles full page cache for anonymous users.

Kind of reinvents the page_cache wheel but handles cache tags automatically
if you don't like drupal's way of rendering and use twig as it was meant to
be used:

Inject your models and use includes instead of the filthy theme suggestions.


To enable: set `wmcontroller.cache.store` and `wmcontroller.cache.tags` to true.

*Note*: we currently only disable the page_cache middleware if you're allowing
wmcontroller to handle page cache. Render cache on the other hand we don't
interfere with as it's up to you to assess whether there's a need for it or not.

Disabling the entire render cache is a simple as setting
`$settings['cache']['bins']['render']` to a noop backend in settings.php.


*Note2*: automatic adding of entity tags requires you to inject each entity
in the root of the array you pass to twig's include call.
This seems a natural/logical way to structure you templates anyway,
so pretty minor issue imo.

*Note3*: to mitigate query param 'attacks' they are ignored.

```twig
{{ article.getTitle() }}
{% for paragraph in article.getParagraphs() %}
    {%
        include '@thing/article/paragraph/small.html.twig'
        with {paragraph: paragraph} only
    %}
{% endfor %}
```

Updating one of these paragraphs (without triggering an article save) will
result in this page being purged.


###  Config

Config is stored as service parameters: 

You can override these in one of your container yamls.

e.g.: `public/sites/default/services.yml`

e.g.:
```yaml
parameters:
    # Main wmcontroller settings
    wmcontroller.settings:

        # The module that has controllers for your entities
        # and if theme (below) is left empty also where your templates ought
        # to be.
        module: ''

        # The theme where your templates can be found (optional)
        theme: ''

        # The relative path your template reside in.
        # (relative to your module / theme dir)
        path: 'templates'

    # Expiry rules.
    # maxage = client side caching duration
    # s-maxage = server side caching duration (this can be drupal db or a cdn)
    # wm-s-maxage = custom cache-control directive for different local cache ttl
    wmcontroller.cache.expiry:
        # Determine max and s-max based on content-type and/or bundle.
        # _default is used when no definition is available for any given bundle.
        entities:
            node:
                _default: { maxage: 120, s-maxage: 300 }
                # example
                #   Client side caching for 2 minutes
                #   CDN caching for 5 minutes
                #   Local db caching for 1 hour
                #
                # article: { maxage: 120, s-maxage: 300, wm-s-maxage: 3600 }
            taxonomy_term:
                _default: { maxage: 120, s-maxage: 300 }

        # If the current page isn't rendering some kind of entity these regexes
        # will determine which maxage will be set.
        # The paths these regexes are matched against are the actual request paths,
        # not the route name or route path. Query parameters are ignored
        paths:
            '^/$':           { maxage: 120, s-maxage: 300 }
            # '^/admin/.*$':   { maxage: 0, s-maxage: 0 }
            # '^/user(/.*)?$': { maxage: 0, s-maxage: 0 }
            '.':             { maxage: 0, s-maxage: 0 }

    # Ignore purges for tags that match these regexes.
    wmcontroller.cache.ignored_tags:
        - 'config:block.*'

    # Triggers a flush for tags that match these regexes.
    wmcontroller.cache.flush_triggers:
        - ''

    # Store the contents of the response and serve it.
    # If disabled, only tags will be stored.
    # This could be useful if the site is proxied by a cdn.
    wmcontroller.cache.store: false

    # Disables caching in its entirety, only add s-maxage and maxage headers.
    # (Also implies wmcontroller.cache.store = false)
    wmcontroller.cache.tags: false

    # Add the X-Wm-Cache: HIT/MISS header.
    wmcontroller.cache.hitheader: true

    # Disable caching for authenticated users.
    # Note: There is a small performance penalty when this is set to false.
    wmcontroller.cache.ignore_authenticated_users: true

    # If wmcontroller.cache.ignore_authenticated_users = false
    # Skip cache entirely for these roles.
    wmcontroller.cache.ignore_roles:
        - 'admin'
        - 'editor'

    # If wmcontroller.cache.ignore_authenticated_users = false
    # Group cache entries for these roles.
    # Note: This allows one path to cache different content based on roles.
    # Make sure your caching layers ( CDN, Varnish, ... ) can handle this!
    wmcontroller.cache.grouped_roles:
          # The cache name.
        - name: 'editors'

          # Set strict to true if the user needs to have all roles to belong here.
          strict: false

          # The required role(s), if strict is true, the user needs to have all
          # roles that are defined here to belong to this group.
          roles:
              - 'editor'

        - name: 'anonymous'
          roles:
              - 'anonymous'

    # Whitelisted query parameters.
    # These query parameters become part of the internal cache key.
    wmcontroller.cache.query.whitelist:
        - 'page'

    # Amount of items that should be purged during each cron run.
    wmcontroller.cache.purge_per_cron: 100

    # Flush all entries on `drush cr` or require `drush cc wmcontroller`
    wmcontroller.cache.flush_on_cache_rebuild: false

    # The service responsible for storing cache entries
    wmcontroller.cache.storage: wmcontroller.cache.storage.mysql

    # The service responsible for deciding the max-age
    wmcontroller.cache.maxage: wmcontroller.cache.maxage.default

    # The service responsible for building the cache item
    wmcontroller.cache.builder: wmcontroller.cache.builder.default

    # The service responsible for deciding the max-age
    # Note: make sure the serializer returns a format that is expected by the
    # cache storage.
    wmcontroller.cache.serializer: wmcontroller.cache.builder.default

    # The service responsible for invalidating tags
    wmcontroller.cache.invalidator: wmcontroller.cache.invalidator.default

    # The service responsible for building the cache key
    wmcontroller.cache.keygenerator: wmcontroller.cache.keygenerator.default

    # List of routes that need to have their ?page= query param rewritten to a
    # route param.
    wmcontroller.pager_routes: []

    # List of response headers to strip
    wmcontroller.cache.stripped_headers:
        - 'x-ua-compatible'

    # If an invalidation causes removal of more than this amount of pages
    # a purge will be done instead. Useful if your CDN charges per path.
    # Note: By default this number is set to an insanely high number. If you
    # use a CDN that charges for invalidations. Set this number much lower.
    wmcontroller.max_purges_per_invalidation: 100000
```

your-module.routing.yml: adding _smaxage and/or _maxage to the route defaults
will add these max-ages if no entity specific rules were found in the config
above.

```yaml

module.search:
    path: 'search'
    defaults:
        _controller: '\Drupal\module\Controller\SearchController::index'
        _smaxage: 1234
        _maxage: 123
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
/** @var Drupal\wmcontroller\Service\Cache\InvalidatorInterface */
$invalidator = injectedService('wmcontroller.cache.invalidator');
$invalidator->invalidateCacheTags($tags);
```

The default implementation purges immediately.

#### a full purge

```php
/** @var Drupal\wmcontroller\Service\Cache\Storage\StorageInterface; */
$storage = injectedService('wmcontroller.cache.storage');
$storage->flush();
```

#### Custom storage

Create your own storage that implements `Drupal\wmcontroller\Service\Cache\Storage\StorageInterface` and register it as a service.

Then set `wmcontroller.cache.storage` to it's service name.

- A CloudFront storage is available at [wieni/wmcontroller_cloudfront](https://github.com/wieni/wmcontroller_cloudfront)
- A Redis storage is available at [wieni/wmcontroller_redis](https://github.com/wieni/wmcontroller_redis)
- A FlySystem storage is available at [wieni/wmcontroller_flysystem](https://github.com/wieni/wmcontroller_flysystem)

```
// production.services.yml
wmcontroller.cache.storage: wmcontroller.cache.storage.cloudfront
```

## Example config

```yml
# services.yml
parameters:
    wmcontroller.settings:
        module: 'mymodule' # Controllers will be searched in mymodule/src/Controller
        theme: 'mytheme'
        path: 'templates' # Twig templates will be searched in mytheme/src/templates/

    wmcontroller.cache.expiry:
        entities:
            node:
                article:   { maxage: 60, s-maxage: 3600 }
                _default:   { maxage: 60, s-maxage: 300 }
            taxonomy_term:
                _default:   { maxage: 60, s-maxage: 300 }

        paths:
            '.': { maxage: 0, s-maxage: 0 }

    wmcontroller.cache.store: true
    wmcontroller.cache.tags: true
    wmcontroller.cache.hitheader: true
    wmcontroller.cache.flush_on_cache_rebuild: true
    wmcontroller.cache.ignore_authenticated_users: true
```
