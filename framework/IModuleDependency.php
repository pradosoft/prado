<?php

/**
 * IModuleDependency interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IModuleDependency interface.
 *
 * Application modules may implement this interface to declare which other
 * modules must be initialized before they are. The application loader
 * collects these declarations and uses Kahn's topological sort to determine
 * a safe initialization order, regardless of the order modules appear in the
 * configuration file.
 *
 * **The `$isPreInit` flag** passed to {@see getModuleDependencies()} identifies
 * which sort pass is being computed:
 *
 * | `$isPreInit` | Used for                   |
 * |--------------|----------------------------|
 * | `true`       | dyPreInit pass             |
 * | `false`      | init() and dyPostInit      |
 *
 * `dyPostInit` always runs in the **same order** as `init()` — the framework
 * reuses the init-pass sort result for post-init without re-sorting.
 * Implementations may return different lists for the pre-init and init passes:
 * ```php
 * public function getModuleDependencies(bool $isPreInit = false): ?array
 * {
 *     // Only need 'db' during init; skip in the pre-init pass.
 *     if ($isPreInit) { return []; }
 *     return ['db'];
 * }
 * ```
 *
 * The return value of {@see getModuleDependencies()} is cast to an array
 * internally (`null|string|array`), so implementations may return any of the following:
 *   - `null` or `[]` — no dependencies;
 *   - a single string module ID — required dependency, shorthand for one dep;
 *   - an indexed array whose elements are either:
 *       - a plain string module ID — required dependency; or
 *       - an associative array with keys `'id'` (string|null) and optionally
 *         `'required'` (bool, default true). When `'id'` is `null` or `''` the
 *         entry is silently skipped (useful for conditional deps where the ID
 *         may not yet be known). When `'required'` is false the dependency is
 *         advisory: it influences order when the referenced module is present
 *         but raises no error when it is absent;
 *   - an associative array whose keys are module IDs and values are the
 *     required flag in any form accepted by {@see TPropertyValue::ensureBoolean}
 *     (bool, int, or string such as `'true'`, `'false'`, `'yes'`, `'no'`).
 *     Example: `['db' => true, 'cache' => false]`.
 *
 * **Contract:** {@see getModuleDependencies()} is called before
 * {@see IModule::init()}, after all configuration properties have been
 * applied via setXxx() calls. Implementations must therefore return their
 * values from configuration state alone and must not rely on init() having
 * run.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IModuleDependency
{
	/**
	 * Returns the IDs of modules that must be initialized before this module.
	 *
	 * The `$isPreInit` flag identifies which sort pass is being computed:
	 * `true` for the dyPreInit pass, `false` for the init() pass (dyPostInit
	 * reuses the init-pass order and does not re-invoke this method).
	 * Implementations may ignore `$isPreInit` and return the same list for both
	 * values, or return different lists for the two passes.
	 *
	 * The return value is cast to an array by the caller, so a single string
	 * module ID may be returned as a convenience. Supported forms:
	 *   - `null` or `[]` — no dependencies
	 *   - `'moduleId'` — single required dependency
	 *   - `['a', 'b']` — multiple required dependencies
	 *   - `[['id' => 'a', 'required' => false]]` — verbose form with required flag;
	 *     `'id'` may be `null` to represent "no dependency" in a fixed-shape array
	 *   - `['a' => true, 'b' => false]` — key-value shorthand; value is passed through
	 *     {@see TPropertyValue::ensureBoolean} so strings like `'true'`/`'false'` are accepted
	 *
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default)
	 * @return null|array<array{id:null|string,required?:bool}|string>|array<string,bool|int|string>|string dependency descriptors
	 */
	public function getModuleDependencies(bool $isPreInit): null|string|array;
}
