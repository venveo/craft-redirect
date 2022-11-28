# Redirect Changelog

## 3.0.9.1 - 2021-10-14

### Fixed

- Fixed error for unauthenticated users on the control panel

## 3.0.9 - 2021-10-14

### Added

- Automatic redirects will now get created when an entry is moved in the structure.
- Added the ability to view redirects pointing to an entry on the entry edit page

### Fixed

- Fixed automatic-redirect creation not working on Craft 3.7+

### Changed

- Requires Craft 3.7.5+

## 3.0.8 - 2021-06-17

### Changed

- Remove php constraint to allow PHP 8 installations

## 3.0.7 - 2020-11-11

### Added

- Console controller for bulk-resaving redirects (vredirect/resave/redirects)

### Fixed

- Console error when viewing 404s on a single-site install
- Bug that could prevent redirects from being saved

## 3.0.6 - 2020-03-04

### Changed

- New icons

## 3.0.5 - 2020-02-25

### Added

- Added support support for specifying a post date and expiry date on redirects

### Changed

- Don't show the site picker on the edit redirect screen if there's only one editable site

### Security

- Missing permission check on 404s table

## 3.0.4 - 2020-02-22

### Fixed

- Fixed issue where external redirects would not show the correct value in the destination site dropdown
- Issue with "page" query string not getting removed from redirect logic
- Don't track 404s with URIs or queries over 255 characters

### Changed

- Registered 404 URIs will now truncate in the table to prevent the table from blowing up. Hovering a row will show the entire URL

## 3.0.3 - 2020-02-20

### Fixed

- Bug where clicking the "Save" button did not work on edit redirect pages

## 3.0.2 - 2020-02-09

### Fixed

- Bug where it was impossible to change a redirect site ID to null after it had already been set
- Bug where there were two "Save" buttons on edit redirect screens

### Changed

- You can now change the site on the 404 list pages without refreshing the page

## 3.0.1 - 2020-02-06

### Added

- Feed me support for dateCreated and dateUpdated
- Dashboard widget
- Added setting to automatically create element redirects
- Added logic to delete redirects for deleted source elements
- Added details pane to "Edit" redirects screen
- You may now explicitly select source and destination sites
- Redirects now have statuses and may be disabled and enabled

### Changed

- Moved some redirect settings to the sidebar
- Change sources from 301/302 to static/dynamic

### Fixed

- PHP 7.1 incompatibilities
- Broken Feed Me Hit Count import
- Don't show site switcher if there's only one site
- Issue migrating from Dolphiq Redirect
- Improved element validation
- Fixed "Stale" redirects source not working properly

## 3.0.0.1 - 2020-01-28

### Fixed

- Incorrect source URI when creating a redirect from a 404

## 3.0.0 - 2020-01-28

### Changed

- Redirect now requires Craft 3.4
- Swapped registered 404s table for new native Vue table
- Split out registered 404s and ignored 404s into 2 tables
- Query parameters and request URI are now stored in separate columns
- Changed column types more appropriate settings

### Added

- Setting to strip query parameters from registered 404s
- Added Feed Me support to import "Hit Count" on redirects
- "Registered 404s" nav item has a badge count now

### Fixed

- Improved compatibility with older versions of php

## 2.1.0 - 2019-12-23

### Added

- Added setting to disable storing referral URLs

### Fixed

- Fixed issues creating redirects in multi-site mode

### Changed

- Improved the workflow for editing redirects (stay on page of edited redirect on save)
- Only update a referrer if its set
- Added foreign key to sites in registered 404s
- Registered 404s no longer store the site base path
- Renamed table prefixes

## 2.0.12.1 - 2019-07-12

### Changed

- Only update a referrer if its set

## 2.0.12 - 2019-07-12

### Added

- We now keep track of the last referrer for a 404

### Changed

- Create redirect button on 404 page now opens in a new window
- You can now "Save and Add Another" redirects
- You can now press "CMD/CTRL+S" to save the redirect and add another

### Fixed

- 404 table not respecting "ignored" filter
- Error saving existing redirect

## 2.0.11 - 2019-07-03

### Added

- Added ability to truncate registered 404s after a certain number of hours of inactivity
- Permissions for managing redirects and registered 404s

### Fixed

- Added additional hardening to install migration

## 2.0.10 - 2019-06-19

### Fixed

- Fixed an install error that can occur if you fixed your dolphiq catch-all table prior to install

## 2.0.9 - 2019-05-19

### Fixed

- Bug that can occur in some environment where PCRE \d is not available

## 2.0.8 - 2019-04-19

### Fixed

- Error in RegEx matching on dynamic patterns

## 2.0.7 - 2019-04-16

### Fixed

- Unexpected behavior that can occur on dynamic redirects matching without replacement

### Changed

- Static redirects will now take precedence over dynamic redirects
- 404 table now defaults to only show unignored items

## 2.0.6 - 2019-04-16

### Added

- Added automatic migration for updating form Dolphiq Redirect

## 2.0.5 - 2019-04-15

### Changed

- Improved styles of 404 list to be full width
- Hide "Site" column if there's only one site
- Disable sorting on non-sortable fields
- Prevent row from being checked when clicking "Create Redirect"
- Ignored column now says "Yes" or "No" rather than 0 or 1
- Improved redirect path pre-parsing

### Added

- Added setting to trim trailing slashes from redirect paths on save

## 2.0.4 - 2019-04-11

### Added

- New "Registered 404s" screen to support filtering, bulk deleting, and bulk ignoring
- Added "ignoring" of 404s

## 2.0.3 - 2019-04-10

### Changed

- Switched Feed Me integration to use Craft namespaced version. If you're using Feed Me, make sure you're on the new
  official channel: craftcms/feed-me

### Fixed

- Feed-me integration had stopped working on latest version of feed-me
- Added validation to ensure statusCode is 301 or 302 to prevent feed-me mismatches

## 2.0.2.2 - 2019-04-05

### Changed

- Default Catch All to enabled

### Fixed

- Regex cleaning bug

## 2.0.2.1 - 2019-04-05

### Fixed

- Fixed string checking error

## 2.0.2 - 2019-04-05

### Fixed

- Side nav now shows correct selected page
- Fixed inline editing of redirects

### Changed

- Refactored code

### Removed

- Disabled Dashboard page until its ready

## 2.0.1.1 - 2019-04-04

### Fixed

- Fixed undefined variable error

### Changed

- Trim trailing slashes off of request URI

## 2.0.1 - 2019-04-04

### Fixed

- Fixed broken install migration

## 2.0.0 - 2019-04-04

### Added

- Soft delete redirects
- Pagination to Catch All URLs
- Auto-delete caught 404 a redirect is spawned from it

### Removed

- "Catch All Template" setting - just render the normal 404 page

### Changed

- Forked for Venveo
- Now requires Craft 3.1.19
- Deleted unused code
- Improved comments
- Changed behavior of redirects to only fire when Craft encounters a 404 error
- Optimized plugin code by bailing out early when possible to avoid additional calls
- Added explicit redirect type selection: dynamic or static
- Dynamic redirects now rely on RegEx rather than Yii routes
- Redirects lookups are now performed as database queries rather than PHP lgoic
- Dropped support for config file redirects
- Catch All URLs can now show all of them

### Fixed

- Fixed potential compatibility issues with PostgreSQL

## 1.0.17 - 2018-07-04

### Fixed

- Fixed icon not shown in newer Craft CMS 3 release
- Fixed an index not found error if you enable Catch-all in the settings on some systems

## 1.0.16 - 2018-04-18

### Fixed

- Fixed migration scripts to create all tables on first install
- Small text changes

## 1.0.15 - 2018-02-21

### Fixed

- Fixed a bug causing the settings routes and section not available in with Craft CMS 3.0.0-RC11

## 1.0.14 - 2018-01-28

### Added

- Ignore not existing static files like fonts, images or video files from the catch all functionality

### Fixed

- Fixed the error "Cannot use craft\base\Object because 'Object' is a special class name" in some environments
- Fixed a not working back link in the plugin

## 1.0.13 - 2018-01-10

### Added

- Added settings screen to enable / disable all the redirects with one click
- Added a catch all setting to catch all the other url's (404) and define a twig template to enable you to create a good stylish 404 page with the correct http code
- Register the catched (not existing) url's in the database and show the last 100 in an interface. The plugin let you create new redirect rules directly from this overview by simply clicking on it.

### Changed

- The required minimal Craft version and checked the compatibility

## 1.0.12 - 2018-01-03

### Added

- Inactive redirects filter (show the redirects not visited for 60 days)

### Changed

- The required minimal Craft version and checked the compatibility
- New screenshot
- Added a link to the URL rules in the edit screen

## 1.0.11 - 2017-12-12

### Changed

- Changed hardcoded tablenames to accept table prefix settings
- New icon

## 1.0.10 - 2017-12-11

### Fixed

- The Add new button dissapeared in Craft RC1 due to changes in the craft template. We fixed this! NOTE: RC1 is required now.

# Redirect Changelog

## 1.0.9 - 2017-12-07

### Fixed

- Fixed a bug resulted in a query exception when using the plugin with Postgres and visiting a redirect url.

## 1.0.8 - 2017-11-06

### Fixed

- validateCustomFields was removed from the last Craft version. We changed the settings controller for that.

## 1.0.7 - 2017-10-22

### Fixed

- The branch was not merged correctly last build, we fixed it.

## 1.0.6 - 2017-10-19

### Fixed

- The introduced fix in version 1.0.5 created an error in some other database environments.

## 1.0.5 - 2017-10-11

### Fixed

- Fixed a bug resulted in a query exception when using the plugin with Postgres.

## 1.0.4 - 2017-10-04

### Fixed

- Fixed a bug that resets the hitAt and hitCount in the migration process.
- Fixed the form validation process and error message.

### Changed

- Added a simple url beautifier/formatter when saving the redirect.
- Cleanup some code.

### Added

- Added a main selection to filter on All redirects, Permanent redirects or Temporarily redirects.

## 1.0.3 - 2017-10-03

- Multi site support.
- Searchable and sortable list.
- Small fixes.

## 1.0.2 - 2017-07-07

- Fix for non default value in hitCount column needed for some database engines.

## 1.0.1 - 2017-06-02

- Added hit count and last hit date functionality.

## 1.0.0 - 2017-06-01

- Initial release.
