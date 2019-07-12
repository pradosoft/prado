<?php
/**
 * TJuiControlAdapter class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\TPropertyValue;
use Prado\Web\UI\ActiveControls\TCallbackPageStateTracker;

/**
 * TJuiCallbackPageStateTracker class.
 *
 * Tracking changes to the page state during callback, including {@link TJuiControlOptions}.
 *
 * @author LANDWEHR Computer und Software GmbH
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiCallbackPageStateTracker extends TCallbackPageStateTracker
{

  /**
   * Add the {@link TJuiControlOptions} to the states to track.
   */
	protected function addStatesToTrack()
	{
		parent::addStatesToTrack();
		$states = $this->getStatesToTrack();
		$states['JuiOptions'] = ['TMapCollectionDiff', [$this, 'updateJuiOptions']];
	}

	/**
	 * Updates the options of the jQueryUI widget.
	 * @param array $options list of widget options to change.
	 */
	protected function updateJuiOptions($options)
	{
		foreach ($options as $key => $value) {
			$options[$key] = $key . ': ' . (is_string($value) ? "'{$value}'" : TPropertyValue::ensureString($value));
		}
		$code = "jQuery('#{$this->_control->getWidgetID()}').{$this->_control->getWidget()}('option', { " . implode(', ', $options) . " });";
		$this->_control->getPage()->getClientScript()->registerEndScript(sprintf('%08X', crc32($code)), $code);
	}
}
