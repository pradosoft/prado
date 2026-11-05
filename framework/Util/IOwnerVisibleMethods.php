<?php

/**
 * IOwnerVisibleMethods interface file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * IOwnerVisibleMethods interface.
 *
 * A behavior implements this interface to declare which of its methods remain
 * visible to its owner. The name aligns with the gate predicate
 * {@see \Prado\Prado::method_visible()}: after a behavior method passes the PHP
 * visibility check, this interface decides whether it stays visible to the owner
 * that called it.
 *
 * The owner consults {@see getOwnerVisibleMethods()} when resolving a method it
 * does not implement itself. The return value is cast to an array by the caller
 * (`null|string|array`), so implementations may return any of the following:
 *
 * | Return | Meaning |
 * |--------|---------|
 * | `null` | no restriction — every public method is visible to the owner |
 * | `[]` | no method is visible to the owner unless explicitly declared |
 * | `['doThing', 'doOther']` | only these methods are visible to the owner |
 * | `'doThing'` | single-name shorthand, cast to `['doThing']` |
 *
 * Method names are matched case-insensitively. `dy` dynamic events and `fx` global
 * events form the behavior-to-owner protocol and are never subject to this
 * restriction.
 *
 * The declaration is overridable by subclasses through normal method overriding,
 * so a child behavior may compose its visible methods with the parent's:
 * ```php
 * public function getOwnerVisibleMethods(): null|string|array
 * {
 *     return array_merge(parent::getOwnerVisibleMethods() ?? [], ['doMore']);
 * }
 * ```
 *
 * {@see \Prado\Util\TBaseBehavior} implements this interface and returns `null` by
 * default, so standard behaviors expose every public method until they declare a
 * restriction.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IOwnerVisibleMethods
{
	/**
	 * Returns the names of the behavior methods that remain visible to the owner.
	 *
	 * The return value is cast to an array by the caller, so a single string method
	 * name may be returned as a convenience. Supported forms:
	 *   - `null` — no restriction; every public method is visible to the owner
	 *   - `[]` — no method is visible to the owner
	 *   - `'doThing'` — single visible method
	 *   - `['doThing', 'doOther']` — multiple visible methods
	 *
	 * Method names are matched case-insensitively.
	 *
	 * @return null|array|string the behavior method names visible to the owner.
	 */
	public function getOwnerVisibleMethods(): null|string|array;
}
