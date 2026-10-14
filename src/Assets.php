<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * The stylesheet of the pages and the script opening the Help from a link,
 * read from the copy of the library that runs and printed in the page. That
 * copy may live in the vendor directory of a site rather than in a plugin,
 * where the web server does not always serve it, or outside the document
 * root: no address of the files is needed.
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
        wp_register_style(self::STYLE, false, [], Menus::instance()->version());
        wp_add_inline_style(self::STYLE, self::read('assets/admin.css'));
        wp_enqueue_style(self::STYLE);
    }

    /**
     * Enqueues the script, printed at the end of the page.
     *
     * @return void
     */
    public static function enqueueScript(): void
    {
        wp_register_script(self::SCRIPT, false, [], Menus::instance()->version(), ['in_footer' => true]);
        wp_add_inline_script(self::SCRIPT, self::read('assets/help.js'));
        wp_enqueue_script(self::SCRIPT);
    }

    /**
     * Contents of a file of the library, empty when it cannot be read
     *
     * @param string $relative Path from the root of the library.
     *
     * @return string
     */
    public static function read(string $relative): string
    {
        $contents = file_get_contents(Menus::instance()->directory() . '/' . $relative);

        return $contents === false ? '' : $contents;
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
