<?php

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
	 * @param string The ID of the server control the trigger is bounded to.
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
		if(($control=$this->findControl($id)) instanceof TControl)
			return $control->getClientID();
		if($id==='')
		{
			throw new TConfigurationException(
				'ttriggeredcallback_invalid_controlid', get_class($this));
		}
		return $id;
	}

	/**
	 * @return array list of trigger callback options.
	 */
	protected function getTriggerOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ControlID'] = $this->getTargetControl();
		return $options;
	}
}

?>