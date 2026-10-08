<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

use Gecka\WP\AdminMenu\Menus;

/*
 * Composer runs bootstrap.php for the first copy of the library only, so the
 * copies of the other plugins are found through their autoloaders: a second
 * copy is faked here with a Composer loader of its own.
 */
it('finds the copies through the Composer autoloaders and tells them apart by version.php', function () {
    $root = dirname(__DIR__, 2);
    $copy = $root . '/.tests/copy-' . uniqid();
    mkdir($copy . '/src', 0777, true);
    file_put_contents($copy . '/version.php', "<?php return '99.0.0';\n");

    // Composer lists a loader among the registered ones only when it knows
    // its vendor directory, as the generated ones do.
    $loader = new \Composer\Autoload\ClassLoader($copy . '/vendor');
    $loader->setPsr4('Gecka\\WP\\AdminMenu\\', $copy . '/src');
    $loader->register();

    try {
        $copies = gecka_wp_admin_menu_copies();
    } finally {
        $loader->unregister();
        unlink($copy . '/version.php');
        rmdir($copy . '/src');
        rmdir($copy);
    }

    $version = require $root . '/version.php';

    expect($copies)->toHaveKey($version)
        ->toHaveKey('99.0.0')
        ->and($copies[$version])->toBe($root)
        ->and($copies['99.0.0'])->toBe($copy)
        ->and(gecka_wp_admin_menu_newest($copies))->toBe(['99.0.0', $copy])
        ->and(gecka_wp_admin_menu_newest([]))->toBeNull();
});

it('serves the version that version.php declares', function () {
    $version = require dirname(__DIR__, 2) . '/version.php';

    expect($version)->toMatch('/^\d+\.\d+\.\d+$/')
        ->and(Menus::instance()->version())->toBe($version)
        ->and(Menus::instance()->directory())->toBe(dirname(__DIR__, 2));
});
