<?php

/**
 * IFilterRenderable class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * IFilterRenderable interface.
 *
 * Marks a control as supporting render-output filtering via the `onRenderFilter` event.
 * Implement using {@see TFilterRenderableTrait}.  {@see TControl::renderControl} and
 * {@see TControl::renderChildren} detect this interface and handle the capture-and-restore
 * lifecycle automatically.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @see TFilterRenderableTrait
 * @since 4.3.3
 */
interface IFilterRenderable extends IRenderable
{
	/**
	 * Returns whether at least one handler is registered for the named event.
	 * Required so that {@see TControl::preRenderFilter} can test the event without
	 * assuming the implementor is a `TComponent`.
	 *
	 * @param string $name event name (case-insensitive)
	 * @return bool
	 */
	public function hasEventHandler($name);

	/**
	 * Raises `onRenderFilter`, passing `$output` (string) through registered handlers via
	 * a {@see TRenderFilterParameter}, and returns the (possibly modified) HTML string.
	 *
	 * @param string $renderedText captured rendered HTML
	 * @return string filtered HTML
	 */
	public function onRenderFilter($renderedText);
}
