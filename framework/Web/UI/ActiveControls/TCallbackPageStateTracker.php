<?php

/**
 * TCallbackPageStateTracker class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TMap;
use stdClass;

/**
 * TCallbackPageStateTracker tracks view state changes on a control during a callback
 * and propagates those changes to the client.
 *
 * {@see trackChanges} snapshots the control's current view state before page logic runs.
 * {@see respondToChanges} then diffs the snapshot against the post-execution state and
 * dispatches the registered client-side update handler for each changed property.
 *
 * View states tracked by default:
 * - Scalar: Visible, Enabled, TabIndex, ToolTip, AccessKey, Translate, Lang, Dir,
 *   Hidden, SpellCheck, Draggable, ContentEditable, InputMode, EnterKeyHint, Inert, Popover
 * - Map collections: Attributes, Style, Aria, Dataset
 *
 * Override {@see addStatesToTrack} in subclasses to register additional view states.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
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
	 * @var \Prado\Web\UI\TControl the control tracked
	 */
	protected $_control;
	/**
	 * @var object sentinel value used to detect the absence of a diff result
	 */
	private $_nullObject;

	/**
	 * Initializes the tracker for the given control and registers the default view states.
	 *
	 * @param \Prado\Web\UI\TControl $control the control whose view state is tracked
	 */
	public function __construct($control)
	{
		$this->_control = $control;
		$this->_existingState = new TMap();
		$this->_nullObject = new stdClass();
		$this->_states = new TMap();
		$this->addStatesToTrack();
	}

	/**
	 * Registers the default set of view states to track.
	 *
	 * Each entry in the StatesToTrack map uses the view state name as key and a
	 * two-element array as value: the diff class name and the change-handler callable.
	 */
	protected function addStatesToTrack()
	{
		$states = $this->getStatesToTrack();
		$states['Visible'] = ['TScalarDiff', [$this, 'updateVisible']];
		$states['Enabled'] = ['TScalarDiff', fn ($diff) => $this->updatePresenceAttribute('disable', $diff === false)];
		$states['Attributes'] = ['TMapCollectionDiff', [$this, 'updateAttributes']];
		$states['Style'] = ['TStyleDiff', [$this, 'updateStyle']];
		$states['TabIndex'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('tabindex', $diff)];
		$states['ToolTip'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('title', $diff)];
		$states['AccessKey'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('accesskey', $diff)];

		// HTML 5 attributes
		$states['Translate'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('translate', $diff)];
		$states['Lang'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('lang', $diff)];
		$states['Dir'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('dir', $diff)];
		$states['Hidden'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('hidden', $diff)];
		$states['SpellCheck'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('spellcheck', $diff)];
		$states['Draggable'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('draggable', $diff)];
		$states['ContentEditable'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('contenteditable', $diff)];
		$states['InputMode'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('inputmode', $diff)];
		$states['EnterKeyHint'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('enterkeyhint', $diff)];
		$states['Inert'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('inert', $diff)];
		$states['Popover'] = ['TScalarDiff', fn ($diff) => $this->updateAttribute('popover', $diff)];

		$states['Aria'] = ['TMapCollectionDiff', [$this, 'updateAttributes']];
		$states['Dataset'] = ['TMapCollectionDiff', [$this, 'updateAttributes']];
	}

	/**
	 * Returns the map of view state names registered for change tracking.
	 *
	 * @return TMap map of view state names to [diffClass, handler] pairs
	 */
	protected function getStatesToTrack()
	{
		return $this->_states;
	}

	/**
	 * Snapshots the current view state values for all tracked properties.
	 *
	 * Object values are cloned to preserve the snapshot.
	 */
	public function trackChanges()
	{
		foreach ($this->_states as $name => $value) {
			$obj = $this->_control->getViewState($name);
			$this->_existingState[$name] = is_object($obj) ? clone($obj) : $obj;
		}
	}

	/**
	 * @return array list of [handler, [diff]] pairs for each changed view state
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
	 * Invokes the registered handler for each view state change detected since {@see trackChanges}.
	 */
	public function respondToChanges()
	{
		foreach ($this->getChanges() as $change) {
			call_user_func_array($change[0], $change[1]);
		}
	}

	/**
	 * @return TCallbackClientScript the callback client script helper for this control's page
	 * @since 4.4.0
	 */
	protected function client()
	{
		return $this->_control->getPage()->getCallbackClient();
	}

	/**
	 * Sets a single HTML attribute on the tracked control via the callback client.
	 *
	 * @param string $attrName the HTML attribute name (e.g., `'tabindex'`, `'lang'`)
	 * @param mixed $value the new attribute value
	 * @since 4.4.0
	 */
	protected function updateAttribute($attrName, $value)
	{
		$this->client()->setAttribute($this->_control, $attrName, $value);
	}

	/**
	 * Adds or removes a boolean presence attribute (e.g., `disabled`) on the tracked control.
	 *
	 * @param string $attrName the HTML attribute name
	 * @param bool $isPresent true to set the attribute, false to remove it
	 * @since 4.4.0
	 */
	protected function updatePresenceAttribute($attrName, $isPresent)
	{
		if ($isPresent) {
			$this->client()->setAttribute($this->_control, $attrName, $attrName);
		} else {
			$this->client()->removeAttribute($this->_control, $attrName);
		}
	}

	/**
	 * Shows or hides the control by replacing its client-side content.
	 *
	 * The control must already be rendered on the client before this is called.
	 *
	 * @param bool $visible true to show the control, false to replace with a hidden placeholder
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
	 * Applies changed CSS class and style declarations to the tracked control.
	 *
	 * @param array $style diff array with keys 'CssClass' and 'Style'
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
	 * Sets each attribute in the diff map on the tracked control.
	 *
	 * @param array $attributes attribute name-value pairs to apply
	 */
	protected function updateAttributes($attributes)
	{
		foreach ($attributes as $name => $value) {
			$this->client()->setAttribute($this->_control, $name, $value);
		}
	}
}
