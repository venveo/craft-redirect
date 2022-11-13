# Craft CMS Redirect Manager

**Important Note**
This is a fork of the original redirect plugin by Dolphiq with efforts to better
maintain and support it. The codebase has been overhauled to have minimal impact on
the website when not in use. 404s are now registered in the database and matched via
regular expressions rather than application routes. This means your dynamic route
format will have changed from the previous version of the plugin.

Additionally, the registered 404s has been rebuilt to better handle more numerous entries.

## Features

- Create 301 and 302 redirects
- Create dynamic redirects to match regular expression patterns
- Track how many hits a redirect gets and when it was last hit
- Schedule redirects to publish and expire at certain dates
- Create intelligent redirects that will link to an existing element - if that element's URL changes, so will the redirect destination URL
- Intelligently normalizes input redirects to remove base URLs when appropriate, making it hard for content editors to break things
- Track 404s registered on the website and quickly create redirects for them
- Automatically create redirects when an element URI changes
- Per-site permissions
- Supports multi-site
- Supports Feed Me for importing redirects

## What it doesn't have

- Support guarantees
- GraphQL support
- Graphs/analytics

## Why another redirect plugin?

Trust us - we didn't want to make this plugin; however, we found that many websites we inherit use Dolphiq redirect and wanted a smooth transition. Further, as a Digital Marketing Agency, we want to have explicit control over the feature-set of the redirect plugin and its data.

For most people starting fresh, we strongly recommend using the excellent [Retour plugin by nystudio107](https://plugins.craftcms.com/retour)

## Installation

[Click here](INSTALL.md) for the installation readme.

### Example of the redirect overview

![Screenshot](resources/screenshots/redirects-screen.png)

### Example of the missed URLs overview

![Screenshot](resources/screenshots/registered-404s-screen.png)

### Creating a redirect from a 404 entry

![Screenshot](resources/screenshots/redirect-from-404.png)

## Importing redirects using Feed Me

![Screenshot](resources/screenshots/import-from-feedme.png)

[Click here](RULES.md) for the complete overview of rule examples.
