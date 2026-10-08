<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * The registry: the menus the plugins declare, and the pages under Settings,
 * added to the admin of WordPress once they are all known.
 *
 * A plugin declares what it brings from plugins_loaded on, or on the
 * gecka_admin_menu action fired just before the pages are added. The first
 * plugin to name a menu sets its title, icon and position; the next ones
 * add their pages and tabs to it, or their tabs to its pages. A menu or a
 * page under Settings marked network goes to the network admin of a
 * multisite, the others to the admin of each site.
 *
 * @author Laurent Dinclaux - Gecka <laurent@gecka.nc>
 */
class Menus
{
    /**
     * Priority on admin_menu and network_admin_menu at which the pages are
     * added. Declarations made on those must come before it.
     */
    public const PRIORITY = 9;

    /**
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Version of the copy of the library that serves the site.
     *
     * @var string
     */
    private string $version = '';

    /**
     * Directory of that copy, where its assets are.
     *
     * @var string
     */
    private string $directory = '';

    /**
     * The menus, by slug.
     *
     * @var array<string, Menu>
     */
    private array $menus = [];

    /**
     * The pages under Settings, by slug.
     *
     * @var array<string, Page>
     */
    private array $options = [];

    /**
     * The pages added, by the hook of their screen.
     *
     * @var array<string, Page>
     */
    private array $screens = [];

    /**
     * The registry
     *
     * @return self
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * A menu, created on its first mention
     *
     * @param string $slug
     *
     * @return Menu
     */
    public static function menu(string $slug): Menu
    {
        $menus = self::instance();

        return $menus->menus[$slug] ??= new Menu($slug);
    }

    /**
     * A page under Settings, created on its first mention
     *
     * @param string $slug
     *
     * @return Page
     */
    public static function options(string $slug): Page
    {
        $menus = self::instance();

        return $menus->options[$slug] ??= new Page($slug, null);
    }

    /**
     * The page shown on the current screen, if it is one of ours. The
     * network admin suffixes the ids of its screens, the hooks of the pages
     * carry none.
     *
     * @return Page|null
     */
    public static function current(): ?Page
    {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (! $screen) {
            return null;
        }

        return self::instance()->screens[(string) preg_replace('/-(network|user)$/', '', $screen->id)] ?? null;
    }

    /**
     * Hooks the registry up.
     *
     * @param string $version   Version of the copy that serves the site.
     * @param string $directory Its directory.
     *
     * @return void
     */
    public function boot(string $version, string $directory): void
    {
        $this->version = $version;
        $this->directory = $directory;

        add_action('admin_menu', [$this, 'register'], self::PRIORITY);
        add_action('network_admin_menu', [$this, 'register'], self::PRIORITY);
        add_filter('admin_body_class', [$this, 'bodyClass']);
    }

    /**
     * Version of the copy that serves the site
     *
     * @return string
     */
    public function version(): string
    {
        return $this->version;
    }

    /**
     * Directory of the copy that serves the site
     *
     * @return string
     */
    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Adds the menus and the pages to the admin being drawn: those marked
     * network to the network admin, the others to the admin of the site.
     *
     * @return void
     */
    public function register(): void
    {
        /**
         * Fires before the menus and pages are added: the last chance for a
         * plugin to declare what it brings.
         *
         * @param Menus $menus The registry.
         */
        do_action('gecka_admin_menu', $this);

        $network = is_network_admin();

        foreach ($this->menus as $menu) {
            if ($menu->isNetwork() === $network) {
                $this->addMenu($menu);
            }
        }

        foreach ($this->options as $page) {
            if ($page->isNetwork() !== $network || $page->visibleCapability() === '') {
                continue;
            }

            $hook = $network
                ? add_submenu_page('settings.php', $page->getTitle(), $page->getMenuTitle(), $page->visibleCapability(), $page->slug(), [$page, 'render'])
                : add_options_page($page->getTitle(), $page->getMenuTitle(), $page->visibleCapability(), $page->slug(), [$page, 'render']);
            $this->addScreen($hook, $page);
        }
    }

    /**
     * Marks the body of our screens, for the stylesheet.
     *
     * @param mixed $classes
     *
     * @return mixed
     */
    public function bodyClass(mixed $classes): mixed
    {
        if (self::current() === null || ! is_string($classes)) {
            return $classes;
        }

        return trim($classes . ' gecka-admin-screen');
    }

    /**
     * Adds a menu and the pages the current user may see, each under the
     * capability of the first tab they may see and by its own slug, so that
     * an address built for one user holds for another. The menu opens on
     * the first of them, whose slug it takes in WordPress; that entry then
     * stands for the page, and WordPress adds none of its own. A user who
     * may see no page gets no menu.
     *
     * WordPress names the screens of the pages under a menu after the title
     * of that menu, badge and translation included. The slug of the menu
     * takes its place, so that a screen keeps its name whatever the count
     * and the language: {menu}_page_{page}, and toplevel_page_{page} for
     * the page the menu opens on.
     *
     * @param Menu $menu
     *
     * @return void
     */
    private function addMenu(Menu $menu): void
    {
        global $admin_page_hooks;

        $pages = $menu->visiblePages();

        if (! $pages) {
            return;
        }

        $first = reset($pages);

        add_menu_page($menu->getTitle(), $menu->getMenuTitle(), $first->visibleCapability(), $first->slug(), [$first, 'render'], $menu->getIcon(), $menu->getPosition());
        $admin_page_hooks[$first->slug()] = sanitize_title($menu->slug());

        foreach ($pages as $page) {
            $hook = add_submenu_page($first->slug(), $page->getTitle(), $page->getMenuTitle(), $page->visibleCapability(), $page->slug(), [$page, 'render']);
            $this->addScreen($hook, $page);
        }
    }

    /**
     * Ties a page to the screen WordPress gave it.
     *
     * @param string|false $hook
     * @param Page         $page
     *
     * @return void
     */
    private function addScreen(string|false $hook, Page $page): void
    {
        if (! $hook) {
            return;
        }

        $this->screens[$hook] = $page;
        $page->hooked($hook);

        add_action('load-' . $hook, [$page, 'load']);
        add_action('admin_print_styles-' . $hook, [Assets::class, 'enqueue']);
    }
}
