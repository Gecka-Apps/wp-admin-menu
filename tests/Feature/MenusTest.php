<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

use Gecka\WP\AdminMenu\Menus;
use Gecka\WP\AdminMenu\Page;

beforeEach(function () {
    global $menu, $submenu, $_registered_pages, $_parent_pages, $admin_page_hooks;

    $menu = [];
    $submenu = [];
    $_registered_pages = [];
    $_parent_pages = [];
    $admin_page_hooks = [];
    wp_set_current_user(static::factory()->user->create(['role' => 'administrator']));
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
});

it('hooks the registry up once booted', function () {
    expect(has_action('admin_menu', [Menus::instance(), 'register']))->toBe(Menus::PRIORITY);
});

it('adds a menu opening on its first page, the pages in their order and by their own slug', function () {
    global $menu, $submenu;

    $security = Menus::menu('security-' . __LINE__)->title('Security')->icon('dashicons-shield')->position(71);
    $security->page('sec-journal')->title('Journal')->order(20)->tab('security')->title('Security')->render(fn() => print 'journal');
    $security->page('sec-stats')->title('Statistics')->order(10)->tab('security')->title('Security')->render(fn() => print 'stats');

    Menus::instance()->register();

    $top = array_values(array_filter($menu, static fn(array $item): bool => $item[2] === 'sec-stats'));
    expect($top)->toHaveCount(1)
        ->and($top[0][0])->toBe('Security')
        ->and($top[0][6])->toBe('dashicons-shield')
        ->and(array_column($submenu['sec-stats'], 2))->toBe(['sec-stats', 'sec-journal'])
        ->and($security->page('sec-stats')->url())->toContain('admin.php?page=sec-stats')
        ->and($security->page('sec-journal')->url())->toContain('admin.php?page=sec-journal');
});

it('keeps the title, icon and position of the first plugin that set them', function () {
    $menu = Menus::menu('shared-' . __LINE__)->title('First')->icon('dashicons-shield')->position(5);
    Menus::menu($menu->slug())->title('Second')->icon('dashicons-star')->position(99);

    expect($menu->getTitle())->toBe('First')
        ->and($menu->getIcon())->toBe('dashicons-shield')
        ->and($menu->getPosition())->toBe(5);
});

it('keeps the title, order and width of the first plugin that set them on a page', function () {
    $page = Menus::menu('shared-' . __LINE__)->page('first-wins')->title('First')->order(5)->wide();
    $page->title('Second')->order(99)->wide(false);

    expect($page->getTitle())->toBe('First')
        ->and($page->getOrder())->toBe(5);

    $page->tab('t')->render(fn() => null);
    ob_start();
    $page->render();
    $html = ob_get_clean();

    expect($html)->toContain('<div class="gecka-admin-page is-wide">');
});

it('names the screens after the slug of the menu, whatever its title and badge', function () {
    $count = 3;
    $shared = Menus::menu('stable')->title('Sécurité');
    $shared->page('st-one')->order(10)->tab('t')->badge(function () use (&$count): int {
        return $count;
    })->render(fn() => null);
    $shared->page('st-two')->order(20)->tab('t')->render(fn() => null);

    Menus::instance()->register();

    expect($shared->page('st-one')->hook())->toBe('toplevel_page_st-one')
        ->and($shared->page('st-two')->hook())->toBe('stable_page_st-two');
});

it('escapes the titles in the sidebar, the bubble aside', function () {
    $shared = Menus::menu('escaped-' . __LINE__)->title('A & <b>B</b>');
    $page = $shared->page('esc-page')->title('C & D');
    $page->tab('t')->badge(1)->render(fn() => null);

    expect($shared->getMenuTitle())->toBe('A &amp; &lt;b&gt;B&lt;/b&gt; <span class="awaiting-mod">1</span>')
        ->and($page->getMenuTitle())->toBe('C &amp; D <span class="awaiting-mod">1</span>');
});

it('runs a callable badge once a request', function () {
    $calls = 0;
    $shared = Menus::menu('counted-' . __LINE__)->title('Counted');
    $page = $shared->page('counted-page')->title('Page');
    $page->tab('a')->badge(function () use (&$calls): int {
        $calls++;

        return 4;
    })->render(fn() => null);
    $page->tab('b')->render(fn() => null);

    Menus::instance()->register();
    ob_start();
    $page->render();
    ob_end_clean();

    expect($calls)->toBe(1)
        ->and($shared->getMenuTitle())->toContain('awaiting-mod">4</span>');
});

it('shows the tab bar only past one tab, and only the tabs the user may see', function () {
    $page = Menus::options('opts-' . __LINE__)->title('Settings');
    $page->tab('one')->title('One')->render(fn() => print 'one');

    ob_start();
    $page->render();
    $html = ob_get_clean();

    expect($html)->toContain('<h1>Settings</h1>')
        ->not->toContain('gecka-admin-page-tabs')
        ->toContain('<div class="gecka-admin-page-body">one</div>');

    $page->tab('two')->title('Two')->order(5)->capability('manage_options')->render(fn() => print 'two');
    $page->tab('secret')->title('Secret')->capability('do_not_have')->render(fn() => print 'secret');

    ob_start();
    $page->render();
    $html = ob_get_clean();

    expect($html)->toContain('gecka-admin-page-tabs')
        ->toContain('>Two</a>')
        ->toContain('>One</a>')
        ->not->toContain('Secret')
        ->toContain('aria-current="page">Two</a>')
        ->toContain('">two</div>');
});

it('shows the tab asked for, and sets it up on load', function () {
    $page = Menus::options('opts-' . __LINE__)->title('Settings');
    $loaded = [];
    $page->tab('a')->title('A')->load(function () use (&$loaded) {
        $loaded[] = 'a';
    })->render(fn() => print 'a');
    $page->tab('b')->title('B')->load(function () use (&$loaded) {
        $loaded[] = 'b';
    })->render(fn() => print 'b');

    $_GET[Page::TAB_ARG] = 'b';
    $page->load();

    ob_start();
    $page->render();
    $html = ob_get_clean();
    unset($_GET[Page::TAB_ARG]);

    expect($loaded)->toBe(['b'])
        ->and($html)->toContain('">b</div>')
        ->and($page->tab('b')->url(['x' => 1]))->toContain('tab=b')
        ->and($page->tab('b')->url(['x' => 1]))->toContain('x=1');
});

it('carries the badges of the tabs up to the page and the menu', function () {
    $menu = Menus::menu('badged-' . __LINE__)->title('Badged');
    $page = $menu->page('badged-page')->title('Page');
    $page->tab('x')->title('X')->badge(fn(): int => 3)->render(fn() => null);
    $page->tab('y')->title('Y')->badge(2)->render(fn() => null);
    $page->tab('hidden')->title('Hidden')->capability('do_not_have')->badge(7)->render(fn() => null);

    expect($page->getBadge())->toBe(5)
        ->and($menu->getMenuTitle())->toContain('awaiting-mod">5</span>')
        ->and($page->tab('y')->getMenuTitle())->toBe('Y <span class="awaiting-mod">2</span>');
});

it('refuses the page to a user with no tab to see', function () {
    wp_set_current_user(static::factory()->user->create(['role' => 'subscriber']));
    $page = Menus::options('opts-' . __LINE__)->title('Settings');
    $page->tab('one')->title('One')->render(fn() => print 'one');

    expect(fn() => $page->render())->toThrow('not allowed');
});

it('puts the tabs in their order, the slug deciding between equals', function () {
    $page = Menus::options('order-' . __LINE__)->title('Statistics');
    $page->tab('gecka-security')->title('Security')->order(0);
    $page->tab('gecka-antispam')->title('Antispam');
    $page->tab('gecka-backup')->title('Backup');

    expect(array_map(static fn($tab) => $tab->slug(), $page->tabs()))->toBe(['gecka-security', 'gecka-antispam', 'gecka-backup']);
});

it('adds a page for a user who may see one of its tabs, under the capability of that tab', function () {
    global $menu, $submenu;

    $shared = Menus::menu('shared-' . __LINE__)->title('Security');
    $shared->page('sh-stats')->title('Statistics')->order(10)->tab('security')->title('Security')->render(fn() => null);
    $shared->page('sh-stats')->tab('antispam')->title('Antispam')->order(20)->capability('moderate_comments')->render(fn() => null);
    $shared->page('sh-lockouts')->title('Lockouts')->order(20)->tab('security')->title('Security')->render(fn() => null);
    $shared->page('sh-refused')->title('Refused')->order(30)->capability('moderate_comments')->tab('antispam')->title('Antispam')->render(fn() => null);

    wp_set_current_user(static::factory()->user->create(['role' => 'editor']));
    Menus::instance()->register();

    $top = array_values(array_filter($menu, static fn(array $item): bool => $item[2] === 'sh-stats'));
    expect($top)->toHaveCount(1)
        ->and($top[0][1])->toBe('moderate_comments')
        ->and(array_column($submenu['sh-stats'], 2))->toBe(['sh-stats', 'sh-refused'])
        ->and(array_column($submenu['sh-stats'], 1))->toBe(['moderate_comments', 'moderate_comments'])
        ->and($shared->page('sh-stats')->url())->toContain('page=sh-stats')
        ->and($shared->page('sh-lockouts')->url())->toContain('page=sh-lockouts')
        ->and(array_map(static fn($tab) => $tab->slug(), $shared->page('sh-stats')->tabs()))->toBe(['antispam'])
        ->and($shared->page('sh-refused')->tab('antispam')->getCapability())->toBe('moderate_comments');
});

it('adds no menu for a user who may see none of its pages', function () {
    global $menu;

    $shared = Menus::menu('closed-' . __LINE__)->title('Closed');
    $shared->page('cl-page')->title('Page')->tab('t')->title('T')->render(fn() => null);

    wp_set_current_user(static::factory()->user->create(['role' => 'subscriber']));
    Menus::instance()->register();

    expect(array_filter($menu, static fn(array $item): bool => $item[2] === $shared->slug()))->toBe([])
        ->and($shared->page('cl-page')->visibleCapability())->toBe('');
});

it('falls back on the first tab when the tab asked for is not a string', function () {
    $page = Menus::options('opts-' . __LINE__)->title('Settings');
    $page->tab('a')->title('A')->render(fn() => print 'a');
    $page->tab('b')->title('B')->render(fn() => print 'b');

    $_GET[Page::TAB_ARG] = ['b'];
    $current = $page->current();
    unset($_GET[Page::TAB_ARG]);

    expect($current?->slug())->toBe('a');
});
