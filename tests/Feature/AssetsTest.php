<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

use Gecka\WP\AdminMenu\Assets;
use Gecka\WP\AdminMenu\Menus;

afterEach(function () {
    wp_deregister_style(Assets::STYLE);
    wp_deregister_script(Assets::SCRIPT);
});

it('prints the stylesheet in the page, with no address to serve it from', function () {
    Assets::enqueue();

    ob_start();
    wp_styles()->do_items([Assets::STYLE]);
    $html = (string) ob_get_clean();

    expect(wp_styles()->registered[Assets::STYLE]->src)->toBeFalse()
        ->and($html)->toContain('<style id="gecka-admin-menu-inline-css"')
        ->and($html)->toContain('.gecka-admin-page-tabs')
        ->and($html)->not->toContain('admin.css');
});

it('prints the help script at the end of the page, with no address either', function () {
    Assets::enqueueScript();

    ob_start();
    wp_scripts()->do_items([Assets::SCRIPT], 1);
    $html = (string) ob_get_clean();

    expect(wp_scripts()->registered[Assets::SCRIPT]->src)->toBeFalse()
        ->and(wp_scripts()->get_data(Assets::SCRIPT, 'group'))->toBe(1)
        ->and($html)->toContain('data-help-tab')
        ->and($html)->not->toContain('help.js');
});

it('reads the files of the copy that runs', function () {
    expect(Assets::read('assets/admin.css'))->toBe(file_get_contents(Menus::instance()->directory() . '/assets/admin.css'));
});
