<?php

class TEventTriggeredCallback extends TTriggeredCallback
{
	/**
	 * @return string The client-side event name the trigger listens to.
	 */
	public function getEventName()
	{
		return $this->getViewState('EventName', '');
	}

	/**
	 * Sets the client-side event name that fires the callback request.
	 * @param string The client-side event name the trigger listens to.
	 */
	public function setEventName($value)
	{
		$this->setViewState('EventName', $value, '');
	}

	/**
	 * @param boolean true to prevent/stop default event action.
	 */
	public function setPreventDefaultAction($value)
	{
		$this->setViewState('StopEvent', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return boolean true to prevent/stop default event action.
	 */
	public function getPreventDefaultAction()
	{
		return $this->getViewState('StopEvent', false);
	}

	/**
	 * @return array list of timer options for client-side.
	 */
	protected function getTriggerOptions()
	{
		$options = parent::getTriggerOptions();
		$name = preg_replace('/^on/', '', $this->getEventName());
		$options['EventName'] = strtolower($name);
		$options['StopEvent'] = $this->getPreventDefaultAction();
		return $options;
	}

	/**
	 * Registers the javascript code for initializing the active control.
	 * @param THtmlWriter the renderer.
	 */
	public function render($writer)
	{
		parent::render($writer);
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(), $this->getTriggerOptions());
	}

	/**
	 * @return string corresponding javascript class name for TEventTriggeredCallback.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TEventTriggeredCallback';
	}
}

?>