<?php

/**
 * TApplicationMultipleMode class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TApplicationMultipleMode class.
 *
 * TApplicationMultipleMode names how a {@see TApplication} registers itself with
 * the {@see Prado} application pool at construction. It is the `MultipleMode`
 * attribute of the `<application>` element and the
 * {@see TApplication::setMultipleMode MultipleMode} property:
 *
 * ```xml
 * <application MultipleMode="Auto">
 * ```
 *
 * | Mode | Registration |
 * |------|--------------|
 * | `Auto` (default) | The singleton when no application exists; joins the pool and enables multiple-application mode when another application exists. |
 * | `Multiple` | Enables multiple-application mode and joins the pool. |
 * | `Singleton` | Throws {@see \Prado\Exceptions\TInvalidOperationException} when a different application is registered. |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TApplicationMultipleMode extends TEnumerable
{
	/** Singleton when alone; joins the pool when another application exists. */
	public const Auto = 'Auto';

	/** Enables multiple-application mode and joins the pool. */
	public const Multiple = 'Multiple';

	/** Throws when a different application is already registered. */
	public const Singleton = 'Singleton';
}
