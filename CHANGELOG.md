# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased

## [1.2.2] - 2023-09-13
### Fixed
- Stop caching preview_link preview routes

## [1.2.1] - 2023-10-30
### Fixed
- Mark http responses with status codes `300`, `301`, `302` and `410` cacheable

## [1.2.0] - 2023-09-13
### Added
- Add Drupal 10 support. Drop Drupal 9 support.

## [1.1.1] - 2023-07-19
### Added
- Support `stale-while-revalidate` and `stale-if-error` cache control header
  - [https://developer.fastly.com/learning/concepts/stale](http://web.archive.org/web/20230719193134/https://developer.fastly.com/learning/concepts/stale/)

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
- Inject ViewBuilder in Drupal\wmcontroller\Controller\ControllerBase
- Apply code style related fixes
- Only validate language in FrontController when entity is translatable
- Allow early rendering in controllers
- Allow adding attachments to ViewBuilder

### Removed
- Remove dependency on the node module
