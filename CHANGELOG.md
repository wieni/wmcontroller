# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0-alpha2] - 2022-06-28
### Fixed
- Fix wmtwig version constraint

## [2.0.0-alpha1] - 2022-06-28
### Added
- Add support for node previews
- Add support for the [Preview Link](https://www.drupal.org/project/preview_link) module
- Add support for content moderation

### Changed
- Change controllers to plugins
- Allow early rendering in controllers

### Removed
- Remove the `wmcontroller.settings.module` option since controllers can now be provided in any module.

## [1.1.0] - 2022-06-28
### Added
- Add v2 upgrade guide & script
- Add stubs of new modules, including only database schema

## [1.0.1] - 2022-01-20
### Fixed
- Avoid null notice on strlen

## [1.0.0] - 2021-08-24
### Added
- Add support for all entities with canonical routes
- Add issue & pull request templates
- Add coding standard fixers
- Add Drupal 9 support

### Changed
- Increase PHP dependency to 8.0
- Update module name & description
- Make bundle-specific controllers optional, falling back to the default 
 controller
- Add helpful error message when throwing 404 because the entity is not 
 translated
- Stop extending `Drupal\Core\Controller\ControllerBase` in 
 `Drupal\wmcontroller\Controller\FrontController`
- Apply code style related fixes
- Only validate language in FrontController when entity is translatable
- Allow adding attachments to ViewBuilder

### Removed
- Remove dependency on the node module
