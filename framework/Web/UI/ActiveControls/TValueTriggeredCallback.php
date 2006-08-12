<?php

class TValueTriggeredCallback extends TTriggeredCallback
{
	/**
	 * @return string The control property name to observe value changes.
	 */
	public function getPropertyName()
	{
		return $this->getViewState('PropertyName', '');
	}

	/**
	 * Sets the control property name to observe value changes that fires the callback request.
	 * @param string The control property name to observe value changes.
	 */
	public function setPropertyName($value)
	{
		$this->setViewState('PropertyName', $value, '');
	}

	/**
	 * Sets the polling interval in seconds to observe property changes.
	 * Default is 1 second.
	 * @param float polling interval in seconds.
	 */
	public function setPollingInterval($value)
	{
		$this->setViewState('Interval', TPropertyValue::ensureFloat($value), 1);
	}

	/**
	 * Gets the decay rate between callbacks. Default is 0;
	 * @return float decay rate between callbacks.
	 */
	public function getDecayRate()
	{
		return $this->getViewState('Decay', 0);
	}

	/**
	 * Sets the decay rate between callback. Default is 0;
	 * @param float decay rate between callbacks.
	 */
	public function setDecayRate($value)
	{
		$decay = TPropertyValue::ensureFloat($value);
		if($decay < 0)
			throw new TConfigurationException('callback_decay_be_not_negative', $this->getID());
		$this->setViewState('Decay', $decay);
	}

	/**
	 * @return float polling interval, 1 second default.
	 */
	public function getPollingInterval()
	{
		return $this->getViewState('Interval', 1);
	}

	/**
	 * @return array list of timer options for client-side.
	 */
	protected function getTriggerOptions()
	{
		$options = parent::getTriggerOptions();
		$options['PropertyName'] = $this->getPropertyName();
		$options['Interval'] = $this->getPollingInterval();
		$options['Decay'] = $this->getDecayRate();
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
		return 'Prado.WebUI.TValueTriggeredCallback';
	}
}
?>