<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * The stylesheet of the pages and the script opening the Help from a link,
 * served from the copy of the library that runs.
 *
 * @author Laurent Dinclaux - Gecka <laurent@gecka.nc>
 */
class Assets
{
    /**
     * Handle of the stylesheet.
     */
    public const STYLE = 'gecka-admin-menu';

    /**
     * Handle of the script.
     */
    public const SCRIPT = 'gecka-admin-menu-help';

    /**
     * Enqueues the stylesheet.
     *
     * @return void
     */
    public static function enqueue(): void
    {
        wp_enqueue_style(self::STYLE, self::url('assets/admin.css'), [], Menus::instance()->version());
    }

    /**
     * Enqueues the script.
     *
     * @return void
     */
    public static function enqueueScript(): void
    {
        wp_enqueue_script(self::SCRIPT, self::url('assets/help.js'), [], Menus::instance()->version(), ['in_footer' => true, 'strategy' => 'defer']);
    }

    /**
     * Url of a file of the library, which lives inside a plugin
     *
     * @param string $relative
     *
     * @return string
     */
    public static function url(string $relative): string
    {
        return plugins_url($relative, Menus::instance()->directory() . '/bootstrap.php');
    }
}
