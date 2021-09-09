# Upgrade Guide

This document describes breaking changes and how to upgrade. For a
complete list of changes including minor and patch releases, please
refer to the [`CHANGELOG`](CHANGELOG.md).

## v1
### Module split
Because this module was getting too big, we decided to split up the module into 4 modules with each well-aligned functionality:
- `wmcontroller`: Adds support for bundle-specific controllers for entities. 
- `wmpage_cache`: Caches pages for anonymous users, with more customisability than the default page cache module.
- `wmpresenter`: Adds support for creating & injecting view presenters on top of entity classes.
- `wmtwig`: Improves the integration of Twig with component and entity-oriented projects.

The complexity of this upgrade depends on whether your site has (custom) modules using wmcontroller services. If so, you
have to follow the _pre-split_ route. If not, you should be fine doing a direct update.

#### Pre-split branch
Since you have other modules using wmcontroller services, which may now be moved to other modules, you'll need to deploy
your update in two parts: first you enable the stub versions of the new modules (without any logic), then you replace 
all old class/service references and update to the v1 release.

1. Update wmcontroller and any submodules to their latest versions:
   - `composer require wieni/wmcontroller:"dev-feature/split-pre as 0.10.2"`
   - `composer require wieni/wmcontroller_cloudfront:dev-release/v0#c737ec0`
2. `drush updb -y && drush cex -y`
3. Enable the stubs of the modules you need: `wmpage_cache`, `wmtwig`, `wmpresenter` and/or any submodules.
4. Deploy your changes to all environments
5. Install the new version of wmcontroller and all modules you previously enabled:
   - `composer require wieni/wmcontroller:"dev-feature/split as 0.10.2"`
   - `composer require wieni/wmtwig wieni/wmpresenter wieni/wmpage_cache`
   - `composer require wieni/wmpage_cache_(cloudfront|flysystem|redis)`
6. Uninstall old wmcontroller submodules:
   - `drush pm-uninstall wmcontroller_(cloudfront|flysystem|redis)`
7. Upgrade your code according to the instructions below.
8. `drush updb -y && drush cex -y`
9. Deploy your changes to all environments
10. Remove old wmcontroller submodules:
   - `composer remove wieni/wmcontroller_(cloudfront|flysystem|redis)`

#### Direct update
1. `composer require wieni/wmcontroller:"dev-feature/split as 0.10.2"`
2. `composer require` and enable the modules you want: `wmpage_cache`, `wmtwig` and/or `wmpresenter`.
3. Upgrade your code according to the instructions below.
8. `drush updb -y && drush cex -y`
9. Deploy your changes to all environments

#### Upgrading code
Most code can be updated automatically by running the following bash script. Paths that should be scanned should be 
passed as arguments:

```bash
./public/modules/contrib/wmcontroller/scripts/update-to-v1.sh public/modules/custom/* public/themes/custom/* public/sites/*
```

### `Controller` plugins
Controllers are now plugins, which means they need an annotation. These annotations look like this:
```php
/**
 * @Controller(
 *     entity_type = "node",
 *     bundle = "article",
 * )
 */
class ArticleController
{
}
```
The namespace and class name are not used anymore to determine the entity type and bundle. This means that you can now 
change the namespace or class name of your controllers.

### ViewBuilder::setEntity
`ViewBuilder::setEntity` has been removed. If your class extends `ControllerBase` or uses `MainEntityTrait`, you can use 
`$this->setEntity` instead. If not, you can use `MainEntityInterface::setEntity` directly.

### Early rendering
Due to changes to caching and the `ViewBuilder` class, controllers are now susceptible to 
[early rendering issues](https://www.lullabot.com/articles/early-rendering-a-lesson-in-debugging-drupal-8). To work 
around this, you can include the patch from [#2638686](https://www.drupal.org/node/2638686).

