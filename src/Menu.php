<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * A top-level menu of the admin, and the pages under it. The first plugin
 * to name it, give it an icon or place it wins: the next ones only add to
 * it.
 *
 * @author Laurent Dinclaux - Gecka <laurent@gecka.nc>
 */
class Menu
{
    /**
     * Icon until a plugin gives one.
     */
    private const DEFAULT_ICON = 'dashicons-admin-generic';

    /**
     * @var string
     */
    private string $slug;

    /**
     * @var string
     */
    private string $title = '';

    /**
     * @var string
     */
    private string $icon = self::DEFAULT_ICON;

    /**
     * @var int|float|null
     */
    private int|float|null $position = null;

    /**
     * The capability the pages ask for unless they name their own, null
     * for manage_options.
     *
     * @var string|null
     */
    private ?string $capability = null;

    /**
     * The pages, by slug.
     *
     * @var array<string, Page>
     */
    private array $pages = [];

    /**
     * @param string $slug
     */
    public function __construct(string $slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * Names the menu.
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
     * Gives the menu its icon.
     *
     * @param string $icon A Dashicons class, a data: URL, or 'none'.
     *
     * @return $this
     */
    public function icon(string $icon): static
    {
        if ($this->icon === self::DEFAULT_ICON) {
            $this->icon = $icon;
        }

        return $this;
    }

    /**
     * Places the menu in the sidebar.
     *
     * @param int|float $position
     *
     * @return $this
     */
    public function position(int|float $position): static
    {
        $this->position ??= $position;

        return $this;
    }

    /**
     * The capability the pages ask for unless they name their own. The
     * first value given stays, like the title. The menu itself is added to
     * the sidebar for the users who may see one of its pages.
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
     * A page of the menu, created on its first mention
     *
     * @param string $slug Slug of the page in the admin, unique across the site.
     *
     * @return Page
     */
    public function page(string $slug): Page
    {
        return $this->pages[$slug] ??= new Page($slug, $this);
    }

    /**
     * The pages, in their order
     *
     * @return Page[]
     */
    public function pages(): array
    {
        $pages = array_values($this->pages);

        usort($pages, static fn(Page $a, Page $b): int => $a->getOrder() <=> $b->getOrder() ?: strcmp($a->slug(), $b->slug()));

        return $pages;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title !== '' ? $this->title : $this->slug;
    }

    /**
     * The pages the current user may see a tab of, in their order
     *
     * @return Page[]
     */
    public function visiblePages(): array
    {
        return array_values(array_filter($this->pages(), static fn(Page $page): bool => $page->visibleCapability() !== ''));
    }

    /**
     * Title in the sidebar, with the badges of the pages the user may see
     *
     * @return string HTML.
     */
    public function getMenuTitle(): string
    {
        $count = 0;

        foreach ($this->visiblePages() as $page) {
            $count += $page->getBadge();
        }

        return esc_html($this->getTitle()) . Page::bubble($count);
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }

    /**
     * @return int|float|null
     */
    public function getPosition(): int|float|null
    {
        return $this->position;
    }

    /**
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability ?? 'manage_options';
    }
}
