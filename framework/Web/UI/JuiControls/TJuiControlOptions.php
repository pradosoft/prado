<?php
/**
 * TJuiControlOptions class file.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\JuiControls
 */

namespace Prado\Web\UI\JuiControls;

use Prado\Collections\TMap;
use Prado\Exceptions\THttpException;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\UI\TControl;

/**
 * TJuiControlOptions interface
 *
 * TJuiControlOptions is an helper class that can collect a list of options
 * for a control. The control must implement {@link IJuiOptions}.
 * The options are validated againg an array of valid options provided by the control itself.
 * Since component properties are case insensitive, the array of valid options is used
 * to ensure the option name has the correct case.
 * The options array can then get retrieved using {@link toArray} and applied to the jQuery-ui widget.
 * In addition to the options, this class will render the needed javascript to raise a callback
 * for any event for which an handler is defined in the control.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package Prado\Web\UI\JuiControls
 * @since 3.3
 */
class TJuiControlOptions
{
	/**
	 * @var TMap map of javascript options.
	 */
	protected $_options;
	/**
	 * @var TControl parent control.
	 */
	private $_control;

	/**
	 * Constructor. Set the parent control owning these options.
	 * @param TControl $control parent control
	 */
	public function __construct($control)
	{
		$this->setControl($control);
	}

	/**
	 * Sets the parent control.
	 * @param TControl $control $control
	 * @throws THttpException
	 */
	public function setControl($control)
	{
		if (!$control instanceof IJuiOptions) {
			throw new THttpException(500, 'juioptions_control_invalid', $control->ID);
		}
		$this->_control = $control;
	}

	/**
	 * Sets a named options with a value. Options are used to store and retrive
	 * named values for the javascript control.
	 * @param string $name option name.
	 * @param mixed $value option value.
	 * @throws THttpException
	 */
	public function __set($name, $value)
	{
		if ($this->_options === null) {
			$this->_options = [];
		}

		foreach ($this->_control->getValidOptions() as $option) {
			if (0 == strcasecmp($name, $option)) {
				$low = strtolower($value);
				if ($low === 'null') {
					$this->_options[$option] = null;
				} elseif ($low === 'true') {
					$this->_options[$option] = true;
				} elseif ($low === 'false') {
					$this->_options[$option] = false;
				} elseif (is_numeric($value)) {
					// trick to get float or integer automatically when needed
					$this->_options[$option] = $value + 0;
				} elseif (substr($low, 0, 8) == 'function') {
					$this->_options[$option] = new TJavaScriptLiteral($value);
				} else {
					$this->_options[$option] = $value;
				}
				return;
			}
		}

		throw new TConfigurationException('juioptions_option_invalid', $this->_control->ID, $name);
	}

	/**
	 * Gets an option named value. Options are used to store and retrive
	 * named values for the base active controls.
	 * @param string $name option name.
	 * @return mixed options value or null if not set.
	 */
	public function __get($name)
	{
		if ($this->_options === null) {
			$this->_options = [];
		}

		foreach ($this->_control->getValidOptions() as $option) {
			if (0 == strcasecmp($name, $option) && isset($this->_options[$option])) {
				return $this->_options[$option];
			}
		}

		return null;
	}

	/**
	 * Only serialize the options itself, not the corresponding parent control.
	 * @return mixed array with the names of all variables of that object that should be serialized.
	 */
	public function __sleep()
	{
		return ['_options'];
	}

	/**
	 * @return Array of active control options
	 */
	public function toArray()
	{
		$ret = ($this->_options === null) ? [] : $this->_options;

		foreach ($this->_control->getValidEvents() as $event) {
			if ($this->_control->hasEventHandler('on' . $event)) {
				$ret[$event] = new TJavaScriptLiteral("function( event, ui ) { Prado.JuiCallback(" . TJavaScript::encode($this->_control->getUniqueID()) . ", " . TJavaScript::encode($event) . ", event, ui, this); }");
			}
		}

		return $ret;
	}

	/**
	 * Raise the specific callback event handler of the target control.
	 * @param mixed $param callback parameters
	 */
	public function raiseCallbackEvent($param)
	{
		$callbackParam = $param->CallbackParameter;
		if (isset($callbackParam->event)) {
			$eventName = 'On' . ucfirst($callbackParam->event);
			if ($this->_control->hasEventHandler($eventName)) {
				$this->_control->$eventName(
					new TJuiEventParameter(
						$this->_control->getResponse(),
						isset($callbackParam->ui) ? $callbackParam->ui : null
				)
				);
			}
		}
	}
}
