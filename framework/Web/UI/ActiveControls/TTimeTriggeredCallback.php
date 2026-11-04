<?php

/**
 * TTimeTriggeredCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active callback control.
 */
use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;

/**
 * TTimeTriggeredCallback class.
 *
 * TTimeTriggeredCallback sends callback request every {@see setInterval Interval} seconds.
 * Upon each callback request, the {@see onCallback OnCallback} event is raised.
 *
 * The timer can be started by calling {@see startTimer()} and stopped using
 * {@see stopTimer()}. The timer can be automatically started when
 * {@see setStartTimerOnLoad StartTimerOnLoad} is true.
 *
 * A started timer pauses while the browser is offline, and while the page is
 * hidden when {@see setPauseWhenHidden PauseWhenHidden} is true. When the page
 * is visible and online again, the client sends one callback at once if an
 * interval passed since the last one, then resumes the interval. The server
 * handler cannot assume one callback per interval.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TTimeTriggeredCallback extends TCallback
{
	/**
	 * @return float seconds between callback requests. Default is 1 second.
	 */
	public function getInterval()
	{
		return $this->getViewState('Interval', 1);
	}

	/**
	 * @param float $value seconds between callback requests, must be a positive number, default is 1 second.
	 */
	public function setInterval($value)
	{
		$interval = TPropertyValue::ensureFloat($value);
		if ($interval <= 0) {
			throw new TConfigurationException('callback_interval_be_positive', $this->getID());
		}

		if ($this->getInterval() === $value) {
			return;
		}

		$this->setViewState('Interval', $interval, 1);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$client = $this->getPage()->getCallbackClient();
			$client->callClientFunction('Prado.WebUI.TTimeTriggeredCallback.setTimerInterval', [$this, $interval]);
		}
	}

	/**
	 * Registers the javascript code to start the timer.
	 */
	public function startTimer()
	{
		$client = $this->getPage()->getCallbackClient();
		$client->callClientFunction('Prado.WebUI.TTimeTriggeredCallback.start', [$this]);
	}

	/**
	 * Registers the javascript code to stop the timer.
	 */
	public function stopTimer()
	{
		$client = $this->getPage()->getCallbackClient();
		$client->callClientFunction('Prado.WebUI.TTimeTriggeredCallback.stop', [$this]);
	}

	/**
	 * @param bool $value true to start the timer when page loads.
	 */
	public function setStartTimerOnLoad($value)
	{
		$this->setViewState(
			'StartTimerOnLoad',
			TPropertyValue::ensureBoolean($value),
			false
		);
	}

	/**
	 * @return bool true to start the timer when page loads.
	 */
	public function getStartTimerOnLoad()
	{
		return $this->getViewState('StartTimerOnLoad', false);
	}

	/**
	 * @return bool whether the timer pauses while the page is hidden. Defaults to true.
	 * @since 4.4.0
	 */
	public function getPauseWhenHidden(): bool
	{
		return $this->getViewState('PauseWhenHidden', true);
	}

	/**
	 * Sets whether the timer pauses while the page is hidden (`document.visibilityState`),
	 * such as in a background tab. A visible page resumes the timer and sends one callback
	 * at once if an interval passed. False keeps the interval running when hidden, at the
	 * rate the browser throttles a hidden page to.
	 * @param mixed $value whether the timer pauses while the page is hidden.
	 * @since 4.4.0
	 */
	public function setPauseWhenHidden($value): void
	{
		$this->setViewState('PauseWhenHidden', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return array list of timer options for client-side.
	 */
	protected function getTriggerOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		$options['Interval'] = $this->getInterval();
		if (!$this->getPauseWhenHidden()) {
			$options['PauseWhenHidden'] = false;
		}
		return $options;
	}

	/**
	 * Registers the javascript code for initializing the active control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer.
	 */
	public function render($writer)
	{
		parent::render($writer);
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getTriggerOptions()
		);
		if ($this->getStartTimerOnLoad()) {
			$id = $this->getClientID();
			$code = "Prado.WebUI.TTimeTriggeredCallback.start('{$id}');";
			$cs = $this->getPage()->getClientScript();
			$cs->registerEndScript("{$id}:start", $code);
		}
	}

	/**
	 * @return string corresponding javascript class name for TTimeTriggeredCallback.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TTimeTriggeredCallback';
	}
}
