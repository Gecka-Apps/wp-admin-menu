<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Gecka\WP\AdminMenu;

/**
 * A tab of a page: what a plugin brings to it. It renders the body of the
 * page when shown, and sets the screen up before, for its list table and
 * its screen options.
 *
 * @author Laurent Dinclaux - Gecka <laurent@gecka.nc>
 */
class Tab
{
    /**
     * @var string
     */
    private string $slug;

    /**
     * @var Page
     */
    private Page $page;

    /**
     * @var string
     */
    private string $title = '';

    /**
     * @var int
     */
    private int $order = 10;

    /**
     * The capability the tab asks for, null for the one of the page.
     *
     * @var string|null
     */
    private ?string $capability = null;

    /**
     * @var callable|null
     */
    private $render = null;

    /**
     * @var callable|null
     */
    private $load = null;

    /**
     * A count shown in a bubble next to the title, or a callable giving it.
     *
     * @var int|callable
     */
    private $badge = 0;

    /**
     * The count a callable badge gave, kept for the rest of the request.
     *
     * @var int|null
     */
    private ?int $count = null;

    /**
     * @param string $slug
     * @param Page   $page
     */
    public function __construct(string $slug, Page $page)
    {
        $this->slug = $slug;
        $this->page = $page;
    }

    /**
     * @return string
     */
    public function slug(): string
    {
        return $this->slug;
    }

    /**
     * @return Page
     */
    public function page(): Page
    {
        return $this->page;
    }

    /**
     * Names the tab.
     *
     * @param string $title
     *
     * @return $this
     */
    public function title(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Places the tab among those of its page, the lowest first.
     *
     * @param int $order
     *
     * @return $this
     */
    public function order(int $order): static
    {
        $this->order = $order;

        return $this;
    }

    /**
     * The capability the tab asks for, the one of the page unless given.
     * The first value given stays, like the title.
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
     * What prints the body of the page when the tab is shown.
     *
     * @param callable $render
     *
     * @return $this
     */
    public function render(callable $render): static
    {
        $this->render = $render;

        return $this;
    }

    /**
     * What sets the screen up before anything is printed, when the tab is
     * shown: a list table, its screen options, the Help, scripts.
     *
     * @param callable $load
     *
     * @return $this
     */
    public function load(callable $load): static
    {
        $this->load = $load;

        return $this;
    }

    /**
     * A count shown in a bubble next to the title, in the tab bar and in the
     * sidebar.
     *
     * @param int|callable $badge The count, or a callable giving it when the menu is drawn. The
     *                            callable runs once a request, its count serving the sidebar
     *                            and the tab bar alike.
     *
     * @return $this
     */
    public function badge(int|callable $badge): static
    {
        $this->badge = $badge;
        $this->count = null;

        return $this;
    }

    /**
     * Url of the tab
     *
     * @param array<string, mixed> $args Other query arguments.
     *
     * @return string
     */
    public function url(array $args = []): string
    {
        return $this->page->url($this->slug, $args);
    }

    /**
     * Whether the current user may see the tab
     *
     * @return bool
     */
    public function allowed(): bool
    {
        return current_user_can($this->getCapability());
    }

    /**
     * @return void
     */
    public function runLoad(): void
    {
        if ($this->load) {
            ($this->load)($this);
        }
    }

    /**
     * @return void
     */
    public function runRender(): void
    {
        if ($this->render) {
            ($this->render)($this);
        }
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title !== '' ? $this->title : $this->slug;
    }

    /**
     * Title with the badge
     *
     * @return string HTML.
     */
    public function getMenuTitle(): string
    {
        return esc_html($this->getTitle()) . Page::bubble($this->getBadge());
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }

    /**
     * @return string
     */
    public function getCapability(): string
    {
        return $this->capability ?? $this->page->getCapability();
    }

    /**
     * The count of the badge
     *
     * @return int
     */
    public function getBadge(): int
    {
        if (! is_callable($this->badge)) {
            return (int) $this->badge;
        }

        return $this->count ??= (int) ($this->badge)();
    }
}
