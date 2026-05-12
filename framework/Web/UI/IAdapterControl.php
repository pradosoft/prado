<?php

/**
 * IAdapterControl interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * IAdapterControl interface.
 *
 * Common contract for the object returned by {@see TControl::getAdapterControl}.
 * That method returns either the control itself or its {@see TControlAdapter} when
 * one is set, so both classes implement this interface.  The lifecycle methods
 * (`onInit`, `onLoad`, `onPreRender`, `onUnload`), the render entry-point, and the
 * state hooks (`loadState`, `saveState`) are all called through this interface.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @see TControl::getAdapterControl
 * @see TControlAdapter
 */
interface IAdapterControl
{
	/**
	 * Creates child controls.
	 */
	public function createChildControls();

	/**
	 * Invoked when the control enters the `OnInit` lifecycle stage.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onInit($param);

	/**
	 * Invoked when the control enters the `OnLoad` lifecycle stage.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onLoad($param);

	/**
	 * Invoked when the control enters the `OnPreRender` lifecycle stage.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onPreRender($param);

	/**
	 * Invoked when the control enters the `OnUnload` lifecycle stage.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onUnload($param);

	/**
	 * Renders the control to `$writer`.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer);

	/**
	 * Loads additional persistent control state.
	 */
	public function loadState();

	/**
	 * Saves additional persistent control state.
	 */
	public function saveState();
}
