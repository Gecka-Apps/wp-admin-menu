# gecka/wp-admin-menu

A top-level WordPress admin menu that several plugins share. Each plugin brings its tabs to the pages of the menu, and every page is drawn in the same layout: the header band and tab bar of the Site Health and Privacy screens of WordPress.

A plugin declares a menu, a page under it and its tab. Another plugin declares the same menu and adds its tab to the same page, or a page of its own. Each plugin works alone, and the menu looks the same whether one or five of them are active. A page with a single tab shows no tab bar. Pages under Settings get the same layout.

## Requirements

- PHP 8.2 or later
- WordPress 6.3 or later, in the admin of a site or, for a menu marked `network()`, in the network admin of a multisite
- The library installed through Composer inside a plugin or a must-use plugin, not a theme: its stylesheet and script are served with `plugins_url()`

## Install

```sh
composer require gecka/wp-admin-menu
```

Do not prefix the namespace with Strauss or PHP-Scoper. A prefixed copy keeps a registry of its own, and the menu it declares would be added a second time next to the shared one.

## Quick start

```php
use Gecka\WP\AdminMenu\Menus;
use Gecka\WP\AdminMenu\Tab;

add_action('init', function (): void {
    $menu = Menus::menu('security')
        ->title(__('Security', 'my-plugin'))
        ->icon('dashicons-shield')
        ->position(71);

    $menu->page('security-stats')
        ->title(__('Statistics', 'my-plugin'))
        ->order(10)
        ->wide()
        ->tab('my-plugin')                      // from here on, the tab
        ->title(__('My plugin', 'my-plugin'))
        ->order(20)
        ->capability('manage_options')
        ->badge(fn(): int => my_plugin_pending_count())
        ->load(function (Tab $tab): void { /* list table, screen options, Help */ })
        ->render(function (Tab $tab): void { echo '…'; });

    Menus::options('my-plugin')
        ->title(__('My plugin', 'my-plugin'))
        ->tab('settings')
        ->render(function (Tab $tab): void { echo '…'; });
}, 5);
```

Declare from `init` on. The classes are ready on `plugins_loaded`, but WordPress wants translations asked for from `init` on and raises a notice before. The action `gecka_admin_menu`, fired on `admin_menu` just before the pages are added, takes late declarations; it receives the registry.

## Sharing a menu

`Menus::menu()`, `Menu::page()` and `Page::tab()` create what they name on its first mention and return the same object afterwards, so two plugins naming the same slugs work on the same menu, page or tab.

On a menu and on a page, the first value given wins: title, icon, position, capability, order and width. The plugins that come next only add pages and tabs. WordPress loads the plugins in the alphabetical order of their paths, so the plugin that owns a menu declares it ahead of the default priority of `init`, at 5, and the guests keep the default. The owner takes the low orders for its pages and tabs, the guests the high ones. Pages and tabs of equal order are sorted by slug.

A tab belongs to the plugin that brings it: its title, order, badge, `load` and `render` take the last value given.

## API

### `Menus`

| Method | Returns |
|---|---|
| `Menus::menu(string $slug)` | The top-level menu of that slug. |
| `Menus::options(string $slug)` | A page under Settings, with tabs like any other page. |
| `Menus::current()` | The page shown on the current screen, `null` elsewhere. |

### `Menu`

| Method | Effect |
|---|---|
| `title(string)` | Name in the sidebar, the slug until given. |
| `icon(string)` | A Dashicons class, a `data:` URL or `'none'`. `dashicons-admin-generic` until given. |
| `position(int\|float)` | Place in the sidebar. |
| `capability(string)` | Capability the pages ask for unless they name their own. |
| `network(bool = true)` | Puts the menu in the network admin of a multisite instead of the admin of each site. Its pages follow. |
| `page(string $slug)` | A page of the menu. Its slug is the `page` query argument, unique across the site. |

### `Page`

| Method | Effect |
|---|---|
| `title(string)` | Title in the band and in the sidebar. |
| `order(int)` | Place among the pages of the menu, lowest first, 10 by default. The menu opens on the first page the user may see. |
| `wide(bool = true)` | Body 1400px wide instead of 800px, for lists and charts. |
| `capability(string)` | Capability the tabs ask for unless they name their own. |
| `network(bool = true)` | For a page under Settings: puts it under the Settings menu of the network admin instead of Settings of each site. |
| `tab(string $slug)` | A tab of the page. |
| `url(?string $tab = null, array $args = [])` | Address of the page, on a tab, with more query arguments. |
| `hook()` | Name of the screen of the page once added, empty before `admin_menu`. |

### `Tab`

| Method | Effect |
|---|---|
| `title(string)` | Label in the tab bar. |
| `order(int)` | Place among the tabs of the page, lowest first, 10 by default. |
| `capability(string)` | Capability the tab asks for. The first value given stays. |
| `badge(int\|callable)` | Count in a bubble next to the label, summed up to the page and the menu in the sidebar. A callable runs once a request. |
| `load(callable)` | Runs on `load-{screen}` when the tab is shown, before anything is printed: list table, screen options, Help tabs, scripts. |
| `render(callable)` | Prints the body of the page when the tab is shown. |
| `url(array $args = [])` | Address of the tab. |

`load` and `render` receive the `Tab`. The tab shown is the one the `tab` query argument names (`Page::TAB_ARG`), or else the first the user may see.

Titles are plain text, escaped when printed.

## Capabilities

A tab asks for the capability it names, or else the one of its page, or else the one of its menu, or else `manage_options`. A user sees the tabs they have the capability for. A page is added for a user who may see one of its tabs, under the capability of the first of them, and a menu for a user who may see one of its pages. The badges count only the tabs the user may see. The library asks `current_user_can()` and decides nothing itself.

## Screens

The screen of a page under a menu is named `{menu}_page_{page}` after the slugs, whatever the title, its translation or the badge. The page the menu opens on is `toplevel_page_{page}`, and which page that is depends on what the user may see. Pages under Settings are `settings_page_{page}`. To act on a screen, ask `Menus::current()` or `$page->hook()` rather than writing its name.

## Network admin

A menu marked `network()` and a page under Settings marked `network()` are added on `network_admin_menu` instead of `admin_menu`, and their addresses come from `network_admin_url()`. Nothing else changes: the same tabs, the same layout, the same `load` and `render`. On a single site, where the network admin is the admin, they land in the admin of the site. A plugin that keeps everything at the network level declares its menu with `->network(is_multisite())`. The form of a settings page in the network admin cannot post to `options.php`, which does not exist there: post it to `edit.php?action=…` of the network admin and save on `network_admin_edit_{action}`.

## Layout

The stylesheet and the script come from the copy of the library that runs, under the handles `Assets::STYLE` and `Assets::SCRIPT`. The body of each screen carries the class `gecka-admin-screen`.

Classes to reuse inside a tab:

- `gecka-admin-toggle` on a checkbox drawn as a switch, `gecka-admin-toggle-label` on its label
- `gecka-admin-choice` on the label of a radio button
- `gecka-admin-buttons` on a row of buttons, or of forms holding one
- `gecka-admin-help-link` on a link that opens a Help tab, named by `data-help-tab="<tab id>"`. It shows as a question mark: write its text all the same ("Learn more"), which screen readers announce, and repeat it in a `title` for the tooltip

No colour is written: the accent is the one of the admin colour scheme, and the neutral tones are mixed from the colour of the text.

## Several plugins, several copies

Every plugin ships its own copy of the library in its `vendor/`. On a site running several of them, the copies are found through the Composer autoloaders of the plugins, and the most recent copy, by the version in its `version.php`, serves them all.

That choice ignores the major version, so the public API never breaks: a plugin built against 1.0 is served by whatever newer copy another plugin brings. The functions of `bootstrap.php` and the `$GLOBALS['gecka_wp_admin_menu_*']` keys are frozen as well, since they come from the first copy loaded, whatever its age. A change that cannot stay backward compatible goes in a new namespace.

## Development

```sh
composer install
composer lint:php
composer analyse   # PHPStan, PHP 8.2 to 8.5
composer test      # Pest, WordPress on SQLite in .tests/
```

The tests need PHP 8.3 or later (Pest 4). PHP 8.2 is covered by PHPStan and by a syntax check in CI.

The stylesheet is written in SCSS under `assets/scss/` and compiled into `assets/admin.css`, which is committed so that installing the library needs no build. After a change to the SCSS:

```sh
npm install
npm run build      # or npm run watch
```

CI rebuilds it and fails when the committed `assets/admin.css` differs.

A release bumps `version.php` along with the tag and adds its entry to `CHANGELOG.md`: the copies on a site are told apart by `version.php`, not by the tag.

## License

Copyright 2026 Gecka. GPL-3.0-or-later.

---
Built with 🥥 and ☕ by [Gecka](https://gecka.nc) — Kanaky-New Caledonia 🇳🇨
