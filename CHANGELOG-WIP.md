# Redirect Changelog

## 4.0.0-beta.2 - 2022-08-01

### Added

- Add support for new unified element editor experience
- Add warning for when a source URI matches a URL on the site
- Add warning for when a source URI matches an existing redirect
- Added groups to help organize redirects
- Added flag to track if redirects were created automatically or not

### Changed

- Redirect source and destination URLs now get normalized when set on the element rather than on-save.
- Removed element deletion redirect pruning
- Creating a new redirect will now always open in a slide-out

### Fixed

- Fixed error caused by permissions & use new element permissions interface
- Fixed ignored 404s getting pruned if not hit for a while
- When a redirect is created for a changed slug, the original URI is still stored in addition to the element ID.
- Redirects that are matched that don't have a valid destination now 404 properly.

## 4.0.0-beta.1 - 2021-03-19

### Changed

- Added support for Craft 4
- Dropped support for Feed Me (plugin is not available for Craft 4 yet)
