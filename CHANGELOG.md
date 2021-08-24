# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

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
- Inject ViewBuilder in Drupal\wmcontroller\Controller\ControllerBase
- Apply code style related fixes
- Only validate language in FrontController when entity is translatable
- Allow early rendering in controllers
- Allow adding attachments to ViewBuilder

### Removed
- Remove dependency on the node module
