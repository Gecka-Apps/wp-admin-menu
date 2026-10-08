<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * A page of the admin: under a menu, or under Settings. It shows the tabs
 * the plugins brought, one at a time, under a header band; a lone tab gets
 * no tab bar. A user sees the tabs they have the capability for.
 *
 * @author Laurent Dinclaux - Gecka <laurent@gecka.nc>
 */
class Page
{
    /**
     * Query argument naming the tab shown.
     */
    public const TAB_ARG = 'tab';

    /**
     * @var string
     */
    private string $slug;

    /**
     * The menu the page is under, null for a page under Settings.
     *
     * @var Menu|null
     */
    private ?Menu $menu;

    /**
     * @var string
     */
    private string $title = '';

    /**
     * Rank among the pages of the menu, null for 10.
     *
     * @var int|null
     */
    private ?int $order = null;

    /**
     * The capability the tabs ask for unless they name their own, null for
     * the one of the menu.
     *
     * @var string|null
     */
    private ?string $capability = null;

    /**
     * Whether the body takes the wide width, for lists and charts, null
     * for the narrow one.
     *
     * @var bool|null
     */
    private ?bool $wide = null;

    /**
     * The tabs, by slug.
     *
     * @var array<string, Tab>
     */
    private array $tabs = [];

    /**
     * Hook of the screen WordPress gave the page, once added.
     *
     * @var string
     */
    private string $hook = '';

    /**
     * @param string    $slug
     * @param Menu|null $menu
     */
    public function __construct(string $slug, ?Menu $menu)
    {
        $this->slug = $slug;
        $this->menu = $menu;
    }

    /**
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * Names the page.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title(string $title): static
    {
        if ($this->title === '') {
            $this->title = $title;
        }

        return $this;
    }

    /**
     * Places the page among those of its menu, the lowest first. The first
     * value given stays, like the title.
     *
     * @param int $order
     *
     * @return $this
     */
    public function order(int $order): static
    {
        $this->order ??= $order;

        return $this;
    }

    /**
     * The capability the tabs ask for unless they name their own, the one
     * of the menu unless given. The first value given stays, like the
     * title. The page itself is added to the sidebar for the users who may
     * see one of its tabs.
     *
     * @param string $capability
     *
     * @return $this
     */
    public function capability(string $capability): static
    {
        $this->capability ??= $capability;

        return $this;
    }

    /**
     * Gives the body the wide width. The first value given stays, like the
     * title.
     *
     * @param bool $wide
     *
     * @return $this
     */
    public function wide(bool $wide = true): static
    {
        $this->wide ??= $wide;

        return $this;
    }

    /**
     * A tab of the page, created on its first mention
     *
     * @param string $slug
     *
     * @return Tab
     */
    public function tab(string $slug): Tab
    {
        return $this->tabs[$slug] ??= new Tab($slug, $this);
    }

    /**
     * The tabs the current user may see, in their order
     *
     * @return Tab[]
     */
    public function tabs(): array
    {
        $tabs = array_values(array_filter($this->tabs, static fn(Tab $tab): bool => $tab->allowed()));

        usort($tabs, static fn(Tab $a, Tab $b): int => $a->getOrder() <=> $b->getOrder() ?: strcmp($a->slug(), $b->slug()));

        return $tabs;
    }

    /**
     * The tab shown: the one asked for, or the first
     *
     * @return Tab|null
     */
    public function current(): ?Tab
    {
        $tabs = $this->tabs();

        if (! $tabs) {
            return null;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Only picks the tab.
        $asked = isset($_GET[self::TAB_ARG]) && is_string($_GET[self::TAB_ARG]) ? sanitize_key($_GET[self::TAB_ARG]) : '';

        foreach ($tabs as $tab) {
            if ($tab->slug() === $asked) {
                return $tab;
            }
        }

        return $tabs[0];
    }

    /**
     * Url of the page, on a tab
     *
     * @param string|null          $tab  Slug of the tab, the first when null.
     * @param array<string, mixed> $args Other query arguments.
     *
     * @return string
     */
    public function url(?string $tab = null, array $args = []): string
    {
        $query = ['page' => $this->slug];

        if ($tab !== null && count($this->tabs) > 1) {
            $query[self::TAB_ARG] = $tab;
        }

        return add_query_arg(array_merge($query, $args), admin_url($this->menu ? 'admin.php' : 'options-general.php'));
    }

    /**
     * Records the screen WordPress gave the page.
     *
     * @param string $hook
     *
     * @return void
     */
    public function hooked(string $hook): void
    {
        $this->hook = $hook;
    }

    /**
     * Hook of the screen of the page, empty until the page is added
     *
     * @return string
     */
    public function hook(): string
    {
        return $this->hook;
    }

    /**
     * Sets the screen up before anything is printed: the script opening the
     * Help from a link, then whatever the tab shown needs, its list table
     * and its screen options.
     *
     * @return void
     */
    public function load(): void
    {
        add_action('admin_enqueue_scripts', [Assets::class, 'enqueueScript']);

        $this->current()?->runLoad();
    }

    /**
     * Prints the page: the band with the title and the tabs, then the tab
     * shown.
     *
     * @return void
     */
    public function render(): void
    {
        $tabs = $this->tabs();
        $current = $this->current();

        if ($current === null) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'default'), 403);
        }

        // The notices WordPress moves under the band take the width of the
        // body through the class of this block.
        printf('<div class="gecka-admin-page%s">', $this->wide ? ' is-wide' : '');
        echo '<div class="gecka-admin-page-header privacy-settings-header">';
        printf('<h1>%s</h1>', esc_html($this->getTitle()));

        if (count($tabs) > 1) {
            printf('<nav class="gecka-admin-page-tabs" aria-label="%s">', esc_attr($this->getTitle()));

            foreach ($tabs as $tab) {
                printf(
                    '<a href="%s" class="gecka-admin-page-tab privacy-settings-tab%s"%s>%s</a>',
                    esc_url($tab->url()),
                    $tab === $current ? ' active' : '',
                    $tab === $current ? ' aria-current="page"' : '',
                    wp_kses($tab->getMenuTitle(), ['span' => ['class' => []]]),
                );
            }

            echo '</nav>';
        }

        echo '</div>';

        // WordPress moves the notices of the page right after this line.
        echo '<hr class="wp-header-end">';
        echo '<div class="gecka-admin-page-body">';
        $current->runRender();
        echo '</div></div>';
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title !== '' ? $this->title : $this->slug;
    }

    /**
     * Title in the sidebar, with the badges of the tabs
     *
     * @return string HTML.
     */
    public function getMenuTitle(): string
    {
        return esc_html($this->getTitle()) . self::bubble($this->getBadge());
    }

    /**
     * Sum of the badges of the tabs the current user may see
     *
     * @return int
     */
    public function getBadge(): int
    {
        $count = 0;

        foreach ($this->tabs() as $tab) {
            $count += $tab->getBadge();
        }

        return $count;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order ?? 10;
    }

    /**
     * The capability the tabs ask for unless they name their own
     *
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability ?? $this->menu?->getCapability() ?? 'manage_options';
    }

    /**
     * The capability the page is added to the sidebar with: that of the
     * first tab the current user may see, so that a user who may see one
     * tab gets the page
     *
     * @return string Empty when the user may see no tab.
     */
    public function visibleCapability(): string
    {
        $tabs = $this->tabs();

        return $tabs ? $tabs[0]->getCapability() : '';
    }

    /**
     * The bubble WordPress draws next to a menu title, for a count
     *
     * @param int $count
     *
     * @return string HTML, empty for zero.
     */
    public static function bubble(int $count): string
    {
        return $count > 0 ? sprintf(' <span class="awaiting-mod">%s</span>', esc_html(number_format_i18n($count))) : '';
    }
}
