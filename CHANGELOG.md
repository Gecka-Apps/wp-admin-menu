# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project
follows [Semantic Versioning](https://semver.org/).

## [1.2.0] - 2026-10-09

### Added

- `Menu::network()` and `Page::network()`: a menu, or a page under
  Settings, added to the network admin of a multisite instead of the admin
  of each site, with addresses from `network_admin_url()`.

## [1.1.0] - 2026-10-08

### Changed

- A help link, `gecka-admin-help-link`, shows as a question mark icon
  instead of its text, which screen readers still announce.

## [1.0.3] - 2026-10-08

### Fixed

- The text of a toggle wraps beside the switch instead of going back
  under it, on small screens as well.

## [1.0.2] - 2026-10-08

### Changed

- Wide pages, from `Page::wide()`, are 1400px wide instead of 1100px.

## [1.0.1] - 2026-10-08

### Fixed

- Notices no longer show above the header band while the page loads:
  they stay hidden until WordPress moves them under it.

### Added

- The SCSS sources of the stylesheet, in `assets/scss/`. The compiled
  `assets/admin.css` still ships with the package.

## [1.0.0] - 2026-10-08

### Added

- A top-level admin menu several plugins share: `Menus::menu()`,
  `Menu::page()` and `Page::tab()` create what they name on its first
  mention, so plugins naming the same slugs add to the same menu and pages.
  The first value given to a menu or a page wins.
- Pages under Settings with the same tabs and layout, through
  `Menus::options()`.
- The header band and tab bar of the Site Health and Privacy screens, with
  no tab bar for a lone tab, an 800px body or 1100px with `Page::wide()`.
- Capabilities per tab, page or menu, `manage_options` by default. Users
  get the tabs, pages and menus they may see, every page reached by its own
  slug.
- Badges on tabs, summed up to the page and the menu in the sidebar. A
  callable badge runs once a request.
- Screens named after the slugs, `{menu}_page_{page}`, whatever the title,
  its translation or the badge.
- `Tab::load()` on the `load-{screen}` of the tab shown, for list tables,
  screen options and Help tabs, and `Tab::render()` for its body.
- Several copies on a site: the copies the plugins ship are found through
  their Composer autoloaders and the most recent one, by `version.php`,
  serves them all.
- Classes to reuse inside a tab: switches, radio choices, button rows and
  links opening a Help tab. Colours come from the admin colour scheme.
- The action `gecka_admin_menu`, fired before the pages are added, for late
  declarations.

[1.1.0]: https://github.com/Gecka-Apps/wp-admin-menu/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/Gecka-Apps/wp-admin-menu/compare/v1.0.2...v1.0.3
[1.0.2]: https://github.com/Gecka-Apps/wp-admin-menu/compare/v1.0.1...v1.0.2
[1.0.1]: https://github.com/Gecka-Apps/wp-admin-menu/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Gecka-Apps/wp-admin-menu/releases/tag/v1.0.0
