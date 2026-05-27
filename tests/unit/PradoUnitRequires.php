<?php

/**
 * PradoUnitRequires — master test-harness loader.
 *
 * A single include that recursively loads every shared helper under
 * {@see tests/unit/Harness/} plus {@see PradoUnit} (the central jump-off
 * point at `tests/unit/PradoUnit.php`). Any test file that needs access to
 * `PradoUnit`, the `TTestApplication` family, the test traits, or any helper
 * that has been (or will be) dropped into the harness can include this file:
 *
 * ```php
 * require_once __DIR__ . '/path/to/PradoUnitRequires.php';
 * ```
 *
 * Relative path from common locations:
 * - tests/unit/                         — `__DIR__ . '/PradoUnitRequires.php'`
 * - tests/unit/{category}/              — `__DIR__ . '/../PradoUnitRequires.php'`
 * - tests/unit/Data/{module}/           — `__DIR__ . '/../../PradoUnitRequires.php'`
 * - tests/unit/Data/DbSpecific/{driver}/ — `__DIR__ . '/../../../PradoUnitRequires.php'`
 *
 * **Auto-discovery contract.** Every `.php` file at any depth below
 * `tests/unit/Harness/` is `require_once`'d, sorted by path so the load order
 * is deterministic across filesystems. Helper files therefore do not need to
 * be registered here when added — drop them into `Harness/` (or any
 * subdirectory of it) and they are picked up automatically. Each harness file
 * is responsible for its own intra-harness `require_once` statements if it
 * depends on a specific other helper at parse time.
 *
 * The harness layout:
 * - `tests/unit/PradoUnit.php` — central jump-off point; loads this file as
 *   its first action.
 * - `tests/unit/PradoUnitRequires.php` — this file.
 * - `tests/unit/Harness/` — concrete helper classes (`TTestApplication`,
 *   `TTestShellApplication`, `TarTestHelper`, …) and any future additions.
 * - `tests/unit/Harness/Traits/` — reusable test traits
 *   (`PradoUnitDataConnectionTrait`, `PradoUnitModuleDependencyTrait`,
 *   `TTestApplicationRestorationTrait`).
 *
 * All files use `require_once`, so including this file multiple times (from
 * different helpers that each pull it in) is safe and incurs no double-loading.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 4.3.3
 */

// No namespace — test infrastructure lives outside the Prado\ hierarchy.

(static function (): void {
	$harness = __DIR__ . '/Harness';
	if (!is_dir($harness)) {
		return;
	}
	$iterator = new \RecursiveIteratorIterator(
		new \RecursiveDirectoryIterator($harness, \FilesystemIterator::SKIP_DOTS)
	);
	$files = [];
	foreach ($iterator as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			$files[] = $file->getPathname();
		}
	}
	// Deterministic order across filesystems (each file uses require_once
	// internally for its own intra-harness deps, so order is incidental).
	sort($files);
	foreach ($files as $path) {
		require_once $path;
	}
})();

require_once __DIR__ . '/PradoUnit.php';
