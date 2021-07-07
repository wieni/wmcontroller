# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Add support for all entities with canonical routes
- Add issue & pull request templates
- Add coding standard fixers
- Add Drupal 9 support
- Add support for node previews
- Add support for the [Preview Link](https://www.drupal.org/project/preview_link) module

### Changed
- Change controllers to plugins
- Increase PHP dependency to 7.1
- Update module name & description
- Make bundle-specific controllers optional, falling back to the default 
 controller
- Add helpful error message when throwing 404 because the entity is not 
 translated
- Stop extending `Drupal\Core\Controller\ControllerBase` in 
 `Drupal\wmcontroller\Controller\FrontController`
- Apply code style related fixes
- Only validate language in FrontController when entity is translatable
- Allow early rendering in controllers
- Allow adding attachments to ViewBuilder

### Removed
- Remove dependency on the node module
- Remove the `wmcontroller.settings.module` option since controllers can now be provided in any module.
