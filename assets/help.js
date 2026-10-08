/*
 * SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * A link carrying data-help-tab opens the Help panel of WordPress on that
 * tab, and moves the focus to it.
 *
 * WordPress focuses the panel once it has slid open, then marks its button
 * aria-expanded: the tab takes the focus on that change, not before.
 */
(() => {
    document.querySelectorAll('[data-help-tab]').forEach((link) => {
        link.addEventListener('click', (event) => {
            const tab = document.querySelector(`#tab-link-${CSS.escape(link.dataset.helpTab)} a`);
            const toggle = document.getElementById('contextual-help-link');

            if (!tab || !toggle) {
                return;
            }

            event.preventDefault();
            tab.click();
            window.scrollTo({ top: 0, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });

            if (toggle.getAttribute('aria-expanded') === 'true') {
                tab.focus({ preventScroll: true });

                return;
            }

            const opened = new MutationObserver(() => {
                if (toggle.getAttribute('aria-expanded') === 'true') {
                    opened.disconnect();
                    tab.focus({ preventScroll: true });
                }
            });

            opened.observe(toggle, { attributes: true, attributeFilter: ['aria-expanded'] });
            toggle.click();
        });
    });
})();
