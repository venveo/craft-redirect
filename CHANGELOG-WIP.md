# Redirect Changelog

## Unreleased

### Added

- Added support for Craft 5

### Fixed

- Fixed a duplicate-source redirect crash in Craft 5 element indexes by deferring redirect warning lookups until edit-form render time.
- Fixed front-end redirect matching so only live redirects fire, dynamic redirects are matched consistently in PHP, and invalid dynamic patterns are skipped with a warning.
- Fixed race-prone redirect and registered-404 hit counters with atomic database updates.
- Fixed registered-404 deduplication with a migration that adds a unique key for site, URI, and query string.
- Fixed catch-all redirect creation permissions, query-string handling, cleanup scope, and CP/widget redirect actions.
- Fixed the registered-404 site switcher to use Craft's native breadcrumb site selector.
