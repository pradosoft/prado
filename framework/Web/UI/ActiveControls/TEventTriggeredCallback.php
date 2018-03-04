<?php
/**
 * TEventTriggeredCallback class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEventTriggeredCallback Class
 *
 * Triggers a new callback request when a particular {@link setEventName EventName}
 * on a control with ID given by {@link setControlID ControlID} is raised.
 *
 * The default action of the event on the client-side can be prevented when
 * {@link setPreventDefaultAction PreventDefaultAction} is set to true.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
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
	 * @param boolean $value true to prevent/stop default event action.
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
	 * @param THtmlWriter $writer the renderer.
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
