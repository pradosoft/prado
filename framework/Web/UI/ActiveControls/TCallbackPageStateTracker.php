<?php
/**
 * TActiveControlAdapter and TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TMap;
use stdClass;

/**
 * TCallbackPageStateTracker class.
 *
 * Tracking changes to the page state during callback.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackPageStateTracker
{
	/**
	 * @var TMap new view state data
	 */
	private $_states;
	/**
	 * @var TMap old view state data
	 */
	private $_existingState;
	/**
	 * @var TControl the control tracked
	 */
	protected $_control;
	/**
	 * @var object null object.
	 */
	private $_nullObject;

	/**
	 * Constructor. Add a set of default states to track.
	 * @param TControl $control control to track.
	 */
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_existingState = new TMap;
		$this->_nullObject = new stdClass;
		$this->_states = new TMap;
		$this->addStatesToTrack();
	}

	/**
	 * Add a list of view states to track. Each state is added
	 * to the StatesToTrack property with the view state name as key.
	 * The value should be an array with two enteries. The first entery
	 * is the name of the class that will calculate the state differences.
	 * The second entry is a php function/method callback that handles
	 * the changes in the viewstate.
	 */
	protected function addStatesToTrack()
	{
		$states = $this->getStatesToTrack();
		$states['Visible'] = ['TScalarDiff', [$this, 'updateVisible']];
		$states['Enabled'] = ['TScalarDiff', [$this, 'updateEnabled']];
		$states['Attributes'] = ['TMapCollectionDiff', [$this, 'updateAttributes']];
		$states['Style'] = ['TStyleDiff', [$this, 'updateStyle']];
		$states['TabIndex'] = ['TScalarDiff', [$this, 'updateTabIndex']];
		$states['ToolTip'] = ['TScalarDiff', [$this, 'updateToolTip']];
		$states['AccessKey'] = ['TScalarDiff', [$this, 'updateAccessKey']];
	}

	/**
	 * @return TMap list of viewstates to track.
	 */
	protected function getStatesToTrack()
	{
		return $this->_states;
	}

	/**
	 * Start tracking view state changes. The clone function on objects are called
	 * for those viewstate having an object as value.
	 */
	public function trackChanges()
	{
		foreach ($this->_states as $name => $value) {
			$obj = $this->_control->getViewState($name);
			$this->_existingState[$name] = is_object($obj) ? clone($obj) : $obj;
		}
	}

	/**
	 * @return array list of viewstate and the changed data.
	 */
	protected function getChanges()
	{
		$changes = [];
		foreach ($this->_states as $name => $details) {
			$new = $this->_control->getViewState($name);
			$old = $this->_existingState[$name];
			if ($new !== $old) {
				$diff = new $details[0]($new, $old, $this->_nullObject);
				if (($change = $diff->getDifference()) !== $this->_nullObject) {
					$changes[] = [$details[1], [$change]];
				}
			}
		}
		return $changes;
	}

	/**
	 * For each of the changes call the corresponding change handlers.
	 */
	public function respondToChanges()
	{
		foreach ($this->getChanges() as $change) {
			call_user_func_array($change[0], $change[1]);
		}
	}

	/**
	 * @return TCallbackClientScript callback client scripting
	 */
	protected function client()
	{
		return $this->_control->getPage()->getCallbackClient();
	}

	/**
	 * Updates the tooltip.
	 * @param string $value new tooltip
	 */
	protected function updateToolTip($value)
	{
		$this->client()->setAttribute($this->_control, 'title', $value);
	}

	/**
	 * Updates the tab index.
	 * @param int $value tab index
	 */
	protected function updateTabIndex($value)
	{
		$this->client()->setAttribute($this->_control, 'tabindex', $value);
	}

	/**
	 * Updates the modifier access key
	 * @param string $value access key
	 */
	protected function updateAccessKey($value)
	{
		$this->client()->setAttribute($this->_control, 'accesskey', $value);
	}

	/**
	 * Hides or shows the control on the client-side. The control must be
	 * already rendered on the client-side.
	 * @param bool $visible true to show the control, false to hide.
	 */
	protected function updateVisible($visible)
	{
		if ($visible === false) {
			$this->client()->replaceContent($this->_control, "<span id=\"" . $this->_control->getClientID() . "\" style=\"display:none\" ></span>");
		} else {
			$this->client()->replaceContent($this->_control, $this->_control);
		}
	}

	/**
	 * Enables or Disables the control on the client-side.
	 * @param bool $enable true to enable the control, false to disable.
	 */
	protected function updateEnabled($enable)
	{
		$this->client()->setAttribute($this->_control, 'disabled', $enable === false);
	}

	/**
	 * Updates the CSS style on the control on the client-side.
	 * @param array $style list of new CSS style declarations.
	 */
	protected function updateStyle($style)
	{
		if ($style['CssClass'] !== null) {
			$this->client()->setAttribute($this->_control, 'class', $style['CssClass']);
		}
		if (is_array($style['Style']) && count($style['Style']) > 0) {
			$this->client()->setStyle($this->_control, $style['Style']);
		}
	}

	/**
	 * Updates/adds a list of attributes on the control.
	 * @param array $attributes list of attribute name-value pairs.
	 */
	protected function updateAttributes($attributes)
	{
		foreach ($attributes as $name => $value) {
			$this->client()->setAttribute($this->_control, $name, $value);
		}
	}
}
