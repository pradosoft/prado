<?php
/**
 * TPeriodicCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  : $
 * @package System.Web.UI.ActiveControls
 */

/**
 * Load active callback control.
 */
Prado::using('System.Web.UI.ActiveControls.TCallback');

/**
 * TPeriodicCallback class.
 *
 * TPeriodicCallback sends callback request every {@link setInterval Interval} seconds.
 * Upon each callback request, the {@link onCallback OnCallback} event is raised.
 *
 * The intervals between each request can be increased when the browser is inactive
 * by changing the {@link setDecayRate DecayRate} to a positive number. The
 * default decay rate, {@link setDecayType DecayType}, is linear and can be changed to
 * 'Exponential', 'Linear', 'Quadratic' or 'Cubic'.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version : $  Mon Jun 19 21:29:42 EST 2006 $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */
class TPeriodicCallback extends TCallback
{
	/**
	 * @return float seconds between callback requests. Default is 1 second.
	 */
	public function getInterval()
	{
		return $this->getViewState('Interval', 1);
	}

	/**
	 * @param float seconds between callback requests, must be a positive number, default is 1 second.
	 */
	public function setInterval($value)
	{
		$interval = TPropertyValue::ensureFloat($value);
		if($interval <= 0)
			throw new TConfigurationException('callback_interval_be_positive', $this->getID());
		$this->setViewState('Interval', $interval, 1);
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
	 * @param string Decay type, allows 'Exponential', 'Linear', 'Quadratic' and 'Cubic'. Default is 'Linear'.
	 */
	public function setDecayType($value)
	{
		$this->setViewState('DecayType', TPropertyValue::ensureEnum($value,
			'Exponential', 'Linear', 'Quadratic', 'Cubic'), 'Linear');
	}

	/**
	 * @return string decay type, default is 'Linear', valid types are 'Exponential', 'Linear', 'Quadratic' and 'Cubic'.
	 */
	public function getDecayType()
	{
		return $this->getViewState('DecayType', 'Linear');
	}

	/**
	 * Registers the javascript code to start the timer.
	 */
	public function startTimer()
	{
		$id = $this->getClientID();
		$code = "Prado.WebUI.TPeriodicCallback.start('{$id}');";
		$cs = $this->getPage()->getClientScript();
		$cs->registerEndScript("{$id}:start", $code);
	}

	/**
	 * Registers the javascript code to stop the timer.
	 */
	public function stopTimer()
	{
		$id = $this->getClientID();
		$code = "Prado.WebUI.TPeriodicCallback.stop('{$id}');";
		$cs = $this->getPage()->getClientScript();
		$cs->registerEndScript("{$id}:stop", $code);
	}

	/**
	 * @return array list of timer options for client-side.
	 */
	protected function getTimerOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['Interval'] = $this->getInterval();
		$options['DecayRate'] = $this->getDecayRate();
		$options['DecayType'] = $this->getDecayType();
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
			$this->getClientClassName(), $this->getTimerOptions());
	}

	/**
	 * @return string corresponding javascript class name for TPeriodicCallback.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TPeriodicCallback';
	}
}

?>