<?php
/**
 * TTriggeredCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Web\UI\TControl;

/**
 * TTriggeredCallback abstract Class
 *
 * Base class for triggered callback controls. The {@link setControlID ControlID}
 * property sets the control ID to observe the trigger.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
abstract class TTriggeredCallback extends TCallback
{
	/**
	 * @return string The ID of the server control the trigger is bounded to.
	 */
	public function getControlID()
	{
		return $this->getViewState('ControlID', '');
	}

	/**
	 * @param string $value The ID of the server control the trigger is bounded to.
	 */
	public function setControlID($value)
	{
		$this->setViewState('ControlID', $value, '');
	}

	/**
	 * @return string target control client ID or html element ID if
	 * control is not found in hierarchy.
	 */
	protected function getTargetControl()
	{
		$id = $this->getControlID();
		if (($control = $this->findControl($id)) instanceof TControl) {
			return $control->getClientID();
		}
		if ($id === '') {
			throw new TConfigurationException(
				'ttriggeredcallback_invalid_controlid',
				get_class($this)
			);
		}
		return $id;
	}

	/**
	 * @return array list of trigger callback options.
	 */
	protected function getTriggerOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'EventTarget' => $this->getUniqueID(),
			'ControlID' => $this->getTargetControl(),
		];
	}
}
