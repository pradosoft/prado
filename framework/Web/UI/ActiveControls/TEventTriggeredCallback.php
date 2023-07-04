<?php
/**
 * TEventTriggeredCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEventTriggeredCallback Class
 *
 * Triggers a new callback request when a particular {@see setEventName EventName}
 * on a control with ID given by {@see setControlID ControlID} is raised.
 *
 * The default action of the event on the client-side can be prevented when
 * {@see setPreventDefaultAction PreventDefaultAction} is set to true.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
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
	 * @param string $value The client-side event name the trigger listens to.
	 */
	public function setEventName($value)
	{
		$this->setViewState('EventName', $value, '');
	}

	/**
	 * @param bool $value true to prevent/stop default event action.
	 */
	public function setPreventDefaultAction($value)
	{
		$this->setViewState('StopEvent', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool true to prevent/stop default event action.
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
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer.
	 */
	public function render($writer)
	{
		parent::render($writer);
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getTriggerOptions()
		);
	}

	/**
	 * @return string corresponding javascript class name for TEventTriggeredCallback.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TEventTriggeredCallback';
	}
}
