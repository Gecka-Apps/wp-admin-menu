<?php

// SPDX-FileCopyrightText: 2026 Gecka <contact@gecka.nc>
// SPDX-License-Identifier: GPL-3.0-or-later

/*
 * WordPress installed once for the whole run, SQLite standing for MySQL, the
 * library booted as a plugin boots it. The installation lands in .tests/
 * unless WP_CORE_DIR names another directory.
 */

use Mantle\Testkit\TestCase;

if (! getenv('WP_CORE_DIR')) {
    putenv('WP_CORE_DIR=' . dirname(__DIR__) . '/.tests/wordpress');
}

uses(TestCase::class)->in(__DIR__);

\Mantle\Testing\manager()
    ->with_sqlite()
    ->loaded(static function (): void {
        require dirname(__DIR__) . '/bootstrap.php';
    })
    ->install();
