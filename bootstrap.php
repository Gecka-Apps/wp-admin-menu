<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

/*
 * Entry point of the library, run by the Composer autoloader of the plugin
 * that ships it. Several plugins on a site may each ship a copy; Composer
 * runs this file for the first of them only, since every copy registers it
 * under the same name. That copy records itself here, and once the plugins
 * are loaded, the copies of the other plugins are found through their
 * Composer autoloaders. The most recent copy alone serves them all: its
 * classes are loaded ahead of the Composer autoloaders, which would
 * otherwise hand out whichever copy was registered first.
 *
 * The functions below are therefore those of the first copy loaded, whatever
 * its age: they stay small and stable, and everything else lives in the
 * classes of the copy chosen. The library is used from plugins_loaded on,
 * never earlier. Run outside WordPress, by a tool reading the autoloader,
 * this file records the copy and does nothing more.
 */

$GLOBALS['gecka_wp_admin_menu_versions'][(string) require __DIR__ . '/version.php'] = __DIR__;

if (! function_exists('gecka_wp_admin_menu_copies')) {
    /**
     * Every copy of the library on the site: those that ran bootstrap.php,
     * and those the Composer autoloaders of the plugins know about.
     *
     * @return array<string, string> Directory by version.
     */
    function gecka_wp_admin_menu_copies(): array
    {
        $copies = (array) ($GLOBALS['gecka_wp_admin_menu_versions'] ?? []);

        if (! class_exists(\Composer\Autoload\ClassLoader::class)) {
            return $copies;
        }

        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
            foreach ($loader->getPrefixesPsr4()['Gecka\\WP\\AdminMenu\\'] ?? [] as $src) {
                // Composer writes the directories of its loaders as vendor/composer/..,
                // which the address of the stylesheet would otherwise carry.
                $directory = dirname(rtrim((string) $src, '/\\'));
                $directory = realpath($directory) ?: $directory;

                if (is_file($directory . '/version.php')) {
                    $copies[(string) include $directory . '/version.php'] ??= $directory;
                }
            }
        }

        return $copies;
    }
}

if (! function_exists('gecka_wp_admin_menu_newest')) {
    /**
     * The most recent of the copies
     *
     * @param array<string, string> $copies Directory by version.
     *
     * @return array{string, string}|null Version and directory, null without a copy.
     */
    function gecka_wp_admin_menu_newest(array $copies): ?array
    {
        if (! $copies) {
            return null;
        }

        uksort($copies, static fn(string|int $a, string|int $b): int => version_compare((string) $a, (string) $b));

        return [(string) array_key_last($copies), (string) end($copies)];
    }
}

if (! function_exists('gecka_wp_admin_menu_boot')) {
    /**
     * Loads the most recent copy of the library and hooks it up, once.
     *
     * @return void
     */
    function gecka_wp_admin_menu_boot(): void
    {
        if (! empty($GLOBALS['gecka_wp_admin_menu_booted'])) {
            return;
        }

        $newest = gecka_wp_admin_menu_newest(gecka_wp_admin_menu_copies());

        if ($newest === null) {
            return;
        }

        $GLOBALS['gecka_wp_admin_menu_booted'] = true;
        [$version, $directory] = $newest;

        spl_autoload_register(
            static function (string $class) use ($directory): void {
                if (str_starts_with($class, 'Gecka\\WP\\AdminMenu\\')) {
                    $file = $directory . '/src/' . str_replace('\\', '/', substr($class, strlen('Gecka\\WP\\AdminMenu\\'))) . '.php';

                    if (is_file($file)) {
                        require $file;
                    }
                }
            },
            true,
            true,
        );

        \Gecka\WP\AdminMenu\Menus::instance()->boot($version, $directory);
    }
}

if (function_exists('add_action')) {
    if (did_action('plugins_loaded')) {
        gecka_wp_admin_menu_boot();
    } elseif (! has_action('plugins_loaded', 'gecka_wp_admin_menu_boot')) {
        add_action('plugins_loaded', 'gecka_wp_admin_menu_boot', -100);
    }
}
