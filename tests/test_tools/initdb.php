#!/usr/bin/env php
<?php
/**
 * tests/test_tools/initdb.php — standalone CLI wrapper around DbInit.
 *
 * Usage:   php tests/test_tools/initdb.php
 * Composer: composer dbinit
 *
 * The same initialization logic runs automatically when PHPUnit starts via
 * PradoTestListener::bootstrap() — this script exists for manual invocation
 * and for the `composer dbtest` / `composer unittest` chain.
 *
 * See DbInit.php for supported databases and environment variable overrides.
 */

require_once __DIR__ . '/DbInit.php';

//use Prado\Tests\DbInit;

// Allow DbInit to run again even if PHPUnit already called it in this process
// (unlikely from CLI, but safe to reset the guard).
$ok = DbInit::initAll(quiet: false);

exit($ok ? 0 : 1);
