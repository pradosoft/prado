<?php

/**
 * TActiveTestPageAdapter class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\TControlAdapter;

/**
 * TActiveTestPageAdapter is a page adapter that records the controls registered for
 * a deferred render in a callback, in the place of {@see \Prado\Web\UI\ActiveControls\TActivePageAdapter}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TActiveTestPageAdapter extends TControlAdapter
{
	/**
	 * @var array the controls registered for a deferred render, by unique ID
	 */
	public array $rendered = [];

	/**
	 * Records the control for a deferred render.
	 * @param \Prado\Web\UI\TControl $control the control
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function registerControlToRender($control, $writer)
	{
		$this->rendered[$control->getUniqueID()] = $control;
	}

	/**
	 * @return null no callback event target
	 */
	public function getCallbackEventTarget()
	{
		return null;
	}
}
