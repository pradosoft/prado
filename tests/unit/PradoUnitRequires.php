<?php

/**
 * PradoUnitRequires — master test-infrastructure loader.
 *
 * A single include that pulls in every shared helper used across the PRADO unit-test
 * suite. Any test file that needs access to {@see PradoUnit}, {@see TTestApplication},
 * {@see TTestShellApplication}, or the database-connection helpers should include this
 * file instead of requiring individual pieces:
 *
 * ```php
 * require_once __DIR__ . '/path/to/PradoUnitRequires.php';
 * ```
 *
 * The relative path from common locations:
 * - tests/unit/                   — `__DIR__ . '/PradoUnitRequires.php'`
 * - tests/unit/Shell/             — `__DIR__ . '/../PradoUnitRequires.php'`
 * - tests/unit/Data/{module}/     — `__DIR__ . '/../../PradoUnitRequires.php'`
 * - tests/unit/Data/DbSpecific/{driver}/ — `__DIR__ . '/../../../PradoUnitRequires.php'`
 *
 * All files use `require_once`, so including this file multiple times (from different
 * test helpers that each pull it in) is safe and incurs no double-loading.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 4.3.3
 */

// No namespace — test infrastructure lives outside the Prado\ hierarchy.

require_once __DIR__ . '/PradoUnitDataConnectionTrait.php';
require_once __DIR__ . '/IO/TarTestHelper.php';
require_once __DIR__ . '/TTestApplicationRestorationTrait.php';
require_once __DIR__ . '/TTestApplication.php';
require_once __DIR__ . '/Shell/TTestShellApplication.php';
require_once __DIR__ . '/PradoUnit.php';
