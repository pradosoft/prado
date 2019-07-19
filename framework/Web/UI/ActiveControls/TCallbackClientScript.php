<?php
/**
 * TCallbackClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Collections\TList;
use Prado\TPropertyValue;
use Prado\Web\UI\ISurroundable;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TCheckBoxList;
use Prado\Web\UI\WebControls\TListControl;

/**
 * TCallbackClientScript class.
 *
 * The TCallbackClientScript class provides corresponding methods that can be
 * executed on the client-side (i.e. the browser client that is viewing
 * the page) during a callback response.
 *
 * The available methods includes setting/clicking input elements, changing Css
 * styles, hiding/showing elements, and adding visual effects to elements on the
 * page. The client-side methods can be access through the CallbackClient
 * property available in TPage.
 *
 * For example, to hide "$myTextBox" element during callback response, do
 * <code>
 * $this->getPage()->getCallbackClient()->hide($myTextBox);
 * </code>
 *
 * To call a specific jQuery method on an element, use the {@link jQuery} method:
 * <code>
 * // simple example: focus a textbox
 * $this->getCallbackClient()->jQuery($myTextBox, 'focus');
 *
 * // complex example: resize a textbox using an animation
 * 	$this->getCallbackClient()->jQuery($myTextBox, 'animate', array(
 *		array(	'width' => '+=100',
 *				'height' => '+=50'
 *			),
 *		array('duration' => 1000)
 *		));
 * </code>
 *
 * To call a jQueryUI effect on an element, use the {@link juiEffect} method:
 * <code>
 * // simple example: focus a textbox
 * $this->getCallbackClient()->juiEffect($myTextBox, 'highlight');
 * </code>
 *
 * In order to use the jQueryUI effects, the jqueryui script must be registered:
 * <code>
 * $this->getPage()->getClientScript()->registerPradoScript('jqueryui');
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
class TCallbackClientScript extends \Prado\TApplicationComponent
{
	/**
	 * @var TList list of client functions to execute.
	 */
	private $_actions;

	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$this->_actions = new TList;
	}

	/**
	 * @return array list of client function to be executed during callback
	 * response.
	 */
	public function getClientFunctionsToExecute()
	{
		return $this->_actions->toArray();
	}

	/**
	 * Executes a client-side statement.
	 * @param string $function javascript function name
	 * @param array $params list of arguments for the function
	 */
	public function callClientFunction($function, $params = [])
	{
		if (!is_array($params)) {
			$params = [$params];
		}

		if (count($params) > 0) {
			if ($params[0] instanceof ISurroundable) {
				$params[0] = $params[0]->getSurroundingTagID();
			} elseif ($params[0] instanceof TControl) {
				$params[0] = $params[0]->getClientID();
			}
		}
		$this->_actions->add([$function => $params]);
	}

	/**
	 * Executes a jQuery client-side method over an element.
	 * @param string $element control or element id
	 * @param string $method jQuery method name
	 * @param array $params list of arguments for the function
	 */
	public function jQuery($element, $method, $params = [])
	{
		if ($element instanceof ISurroundable) {
			$element = $element->getSurroundingTagID();
		} elseif ($element instanceof TControl) {
			$element = $element->getClientID();
		}

		if (!is_array($params)) {
			$params = [$params];
		}

		$this->_actions->add(['Prado.Element.j' => [$element, $method, $params]]);
	}

	/**
	 * Client script to set the value of a particular input element.
	 * @param TControl $input control element to set the new value
	 * @param string $text new value
	 */
	public function setValue($input, $text)
	{
		$this->jQuery($input, 'val', $text);
	}

	/**
	 * Client script to select/clear/check a drop down list, check box list,
	 * or radio button list.
	 * The second parameter determines the selection method. Valid methods are
	 *  - <b>Value</b>, select or check by value
	 *  - <b>Values</b>, select or check by a list of values
	 *  - <b>Index</b>, select or check by index (zero based index)
	 *  - <b>Indices</b>, select or check by a list of index (zero based index)
	 *  - <b>Clear</b>, clears or selections or checks in the list
	 *  - <b>All</b>, select all
	 *  - <b>Invert</b>, invert the selection.
	 * @param TControl $control list control
	 * @param string $method selection method
	 * @param null|int|string $value the value or index to select/check.
	 * @param null|string $type selection control type, either 'check' or 'select'
	 */
	public function select($control, $method = 'Value', $value = null, $type = null)
	{
		$method = TPropertyValue::ensureEnum(
			$method,
			'Value',
			'Index',
			'Clear',
			'Indices',
			'Values',
			'All',
			'Invert'
		);
		$type = ($type === null) ? $this->getSelectionControlType($control) : $type;
		$total = $this->getSelectionControlIsListType($control) ? $control->getItemCount() : 1;

		// pass the ID to avoid getting the surrounding elements (ISurroundable)
		if ($control instanceof TControl) {
			$control = $control->getClientID();
		}

		$this->callClientFunction(
			'Prado.Element.select',
			[$control, $type . $method, $value, $total]
		);
	}

	private function getSelectionControlType($control)
	{
		if (is_string($control)) {
			return 'check';
		}
		if ($control instanceof TCheckBoxList) {
			return 'check';
		}
		if ($control instanceof TCheckBox) {
			return 'check';
		}
		return 'select';
	}

	private function getSelectionControlIsListType($control)
	{
		return $control instanceof TListControl;
	}

	/**
	 * Client script to click on an element. <b>This client-side function is unpredictable.</b>
	 *
	 * @param TControl $control control element or element id
	 */
	public function click($control)
	{
		$this->jQuery($control, 'trigger', 'click');
	}

	/**
	 * Client script to check or uncheck a checkbox or radio button.
	 * @param TControl $checkbox control element or element id
	 * @param bool $checked check or uncheck the checkbox or radio button.
	 */
	public function check($checkbox, $checked = true)
	{
		$this->select($checkbox, "Value", $checked);
	}

	/**
	 * Raise the client side event (given by $eventName) on a particular element.
	 * @param TControl $control control element or element id
	 * @param string $eventName Event name, e.g. "click"
	 */
	public function raiseClientEvent($control, $eventName)
	{
		$this->jQuery($control, 'trigger', $eventName);
	}

	/**
	 * Sets the attribute of a particular control.
	 * @param TControl $control control element or element id
	 * @param string $name attribute name
	 * @param string $value attribute value
	 */
	public function setAttribute($control, $name, $value)
	{
		// Attributes should be applied on Surrounding tag, except for 'disabled' attribute
		if ($control instanceof ISurroundable && strtolower($name) !== 'disabled') {
			$control = $control->getSurroundingTagID();
		}
		$this->callClientFunction('Prado.Element.setAttribute', [$control, $name, $value]);
	}

	/**
	 * Sets the options of a select input element.
	 * @param TControl $control control element or element id
	 * @param TCollection $items a list of new options
	 */
	public function setListItems($control, $items)
	{
		$options = [];
		if ($control instanceof TListControl) {
			$promptText = $control->getPromptText();
			$promptValue = $control->getPromptValue();

			if ($promptValue === '') {
				$promptValue = $promptText;
			}

			if ($promptValue !== '') {
				$options[] = [$promptText, $promptValue];
			}
		}

		foreach ($items as $item) {
			if ($item->getHasAttributes()) {
				$options[] = [$item->getText(), $item->getValue(), $item->getAttributes()->itemAt('Group')];
			} else {
				$options[] = [$item->getText(), $item->getValue()];
			}
		}
		$this->callClientFunction('Prado.Element.setOptions', [$control, $options]);
	}

	/**
	 * Shows an element by changing its CSS display style as empty.
	 * @param TControl $element control element or element id
	 */
	public function show($element)
	{
		$this->jQuery($element, 'show');
	}

	/**
	 * Hides an element by changing its CSS display style to "none".
	 * @param TControl $element control element or element id
	 */
	public function hide($element)
	{
		$this->jQuery($element, 'hide');
	}

	/**
	 * Toggles the visibility of the element.
	 * @param TControl $element control element or element id
	 * @param null|string $effect visual effect, such as, 'fade' or 'slide'.
	 * @param array $options additional options.
	 */
	public function toggle($element, $effect = null, $options = [])
	{
		switch (strtolower($effect)) {
			case 'fade':
				$method = 'fadeToggle';
				break;
			case 'slide':
				$method = 'slideToggle';
				break;
			default:
				$method = 'toggle';
				// avoid fancy effect by default
				if (!array_key_exists('duration', $options)) {
					$options['duration'] = 0;
				}
				break;
		}
		$this->jQuery($element, $method, $options);
	}

	/**
	 * Removes an element from the HTML page.
	 * @param TControl $element control element or element id
	 */
	public function remove($element)
	{
		$this->jQuery($element, 'remove');
	}

	/**
	 * Update the element's innerHTML with new content.
	 * @param TControl $element control element or element id
	 * @param TControl $content new HTML content, if content is of a TControl, the
	 * controls render method is called.
	 */
	public function update($element, $content)
	{
		$this->jQuery($element, 'html', $content);
	}

	/**
	 * Add a Css class name to the element.
	 * @param TControl $element control element or element id
	 * @param string $cssClass CssClass name to add.
	 */
	public function addCssClass($element, $cssClass)
	{
		$this->jQuery($element, 'addClass', $cssClass);
	}

	/**
	 * Remove a Css class name from the element.
	 * @param TControl $element control element or element id
	 * @param string $cssClass CssClass name to remove.
	 */
	public function removeCssClass($element, $cssClass)
	{
		$this->jQuery($element, 'removeClass', $cssClass);
	}

	/**
	 * Scroll the top of the browser viewing area to the location of the
	 * element.
	 *
	 * @param TControl $element control element or element id
	 * @param array $options additional options: 'duration' in ms, 'offset' from the top in pixels
	 */
	public function scrollTo($element, $options = [])
	{
		$this->callClientFunction('Prado.Element.scrollTo', [$element, $options]);
	}

	/**
	 * Focus on a particular element.
	 * @param TControl $element control element or element id.
	 */
	public function focus($element)
	{
		$this->jQuery($element, 'trigger', 'focus');
	}

	/**
	 * Sets the style of element. The style must be a key-value array where the
	 * key is the style property and the value is the style value.
	 * @param TControl $element control element or element id
	 * @param array $styles list of key-value pairs as style property and style value.
	 */
	public function setStyle($element, $styles)
	{
		$this->jQuery($element, 'css', [$styles]);
	}

	/**
	 * Append a HTML fragement to the element.
	 * @param TControl $element control element or element id
	 * @param string $content HTML fragement or the control to be rendered
	 */
	public function appendContent($element, $content)
	{
		$this->jQuery($element, 'append', $content);
	}

	/**
	 * Prepend a HTML fragement to the element.
	 * @param TControl $element control element or element id
	 * @param string $content HTML fragement or the control to be rendered
	 */
	public function prependContent($element, $content)
	{
		$this->jQuery($element, 'prepend', $content);
	}

	/**
	 * Insert a HTML fragement after the element.
	 * @param TControl $element control element or element id
	 * @param string $content HTML fragement or the control to be rendered
	 */
	public function insertContentAfter($element, $content)
	{
		$this->jQuery($element, 'after', $content);
	}

	/**
	 * Insert a HTML fragement in before the element.
	 * @param TControl $element control element or element id
	 * @param string $content HTML fragement or the control to be rendered
	 */
	public function insertContentBefore($element, $content)
	{
		$this->jQuery($element, 'before', $content);
	}

	/**
	 * Replace the content of an element with new content. The new content can
	 * be a string or a TControl component. If the <tt>content</tt> parameter is
	 * a TControl component, its rendered method will be called and its contents
	 * will be used for replacement.
	 * @param TControl $element control element or HTML element id.
	 * @param string $content HTML fragement or the control to be rendered.
	 * @param bool $self whether to fully replace the element or just its inner content.
	 * @see insertAbout
	 * @see insertBelow
	 * @see insertBefore
	 * @see insertAfter
	 */
	protected function replace($element, $content, $self)
	{
		if ($content instanceof TControl) {
			$boundary = $this->getRenderedContentBoundary($content);
			$content = null;
		} elseif ($content instanceof THtmlWriter) {
			$boundary = $this->getResponseContentBoundary($content);
			$content = null;
		} else {
			$boundary = null;
		}

		$this->callClientFunction('Prado.Element.replace', [$element, $content, $boundary, $self]);
	}

	/**
	 * Replace the content of an element with new content contained in writer.
	 * @param TControl $element control element or HTML element id.
	 * @param string $content HTML fragement or the control to be rendered.
	 * @param bool $self whether to fully replace the element or just its inner content, defaults to true.
	 */
	public function replaceContent($element, $content, $self = true)
	{
		$this->replace($element, $content, $self);
	}


	/**
	 * Evaluate a block of javascript enclosed in a boundary.
	 * @param THtmlWriter $writer writer for the content.
	 */
	public function evaluateScript($writer)
	{
		if ($writer instanceof THtmlWriter) {
			$boundary = $this->getResponseContentBoundary($writer);
			$content = null;
		} else {
			$boundary = null;
			$content = $writer;
		}

		$this->callClientFunction('Prado.Element.evaluateScript', [$content, $boundary]);
	}

	/**
	 * Appends a block of inline javascript enclosed in a boundary.
	 * Similar to to evaluateScript(), but functions declared in the
	 * inline block will be available to page elements.
	 * @param THtmlWriter $content writer for the content.
	 */
	public function appendScriptBlock($content)
	{
		if ($content instanceof TControl) {
			$boundary = $this->getRenderedContentBoundary($content);
		} elseif ($content instanceof THtmlWriter) {
			$boundary = $this->getResponseContentBoundary($content);
		}

		$this->callClientFunction('Prado.Element.appendScriptBlock', [$boundary]);
	}

	/**
	 * Renders the control and return the content boundary from
	 * TCallbackResponseWriter. This method should only be used by framework
	 * component developers. The render() method is defered to be called in the
	 * TActivePageAdapter class.
	 * @param TControl $control control to be rendered on callback response.
	 * @return string the boundary for which the rendered content is wrapped.
	 */
	private function getRenderedContentBoundary($control)
	{
		$writer = $this->getResponse()->createHtmlWriter();
		$adapter = $control->getPage()->getAdapter();
		$adapter->registerControlToRender($control, $writer);
		return $writer->getWriter()->getBoundary();
	}

	/**
	 * @param THtmlWriter $html the writer responsible for rendering html content.
	 * @return string content boundary.
	 */
	private function getResponseContentBoundary($html)
	{
		if ($html instanceof THtmlWriter) {
			if ($html->getWriter() instanceof TCallbackResponseWriter) {
				return $html->getWriter()->getBoundary();
			}
		}
		return null;
	}

	/* VISUAL EFFECTS */

	/**
	 * Add a visual effect the element.
	 * @param string $type visual effect function name.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function visualEffect($type, $element, $options = [])
	{
		$this->jQuery($element, $type, $options);
	}

	/* BASIC EFFECTS (JQUERY CORE) */

	/**
	 * Visual Effect: Gradually make the element appear.
	 * This effect doesn't need jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function fadeIn($element, $options = [])
	{
		$this->visualEffect('fadeIn', $element, $options);
	}

	/**
	 * Visual Effect: Gradually fade the element.
	 * This effect doesn't need jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function fadeOut($element, $options = [])
	{
		$this->visualEffect('fadeOut', $element, $options);
	}

	/**
	 * Set the opacity on a html element or control.
	 * This effect doesn't need jQueryUI.
	 * @param TControl $element control element or element id
	 * @param float $value opacity value between 1 and 0
	 * @param int $duration animation duration in ms
	 */
	public function fadeTo($element, $value, $duration = 500)
	{
		$value = TPropertyValue::ensureFloat($value);
		$this->visualEffect('fadeTo', $element, [$duration, $value]);
	}

	/**
	 * Visual Effect: Slide down.
	 * This effect doesn't need jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function slideDown($element, $options = [])
	{
		$this->visualEffect('slideDown', $element, $options);
	}

	/**
	 * Visual Effect: Slide up.
	 * This effect doesn't need jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function slideUp($element, $options = [])
	{
		$this->visualEffect('slideUp', $element, $options);
	}

	/* OLD METHODS, DEPRECATED, BACKWARDS-COMPATIBILITY */

	/**
	 * Alias of fadeIn()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function appear($element, $options = [])
	{
		$this->fadeIn($element, $options);
	}

	/**
	 * Alias of fadeOut()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function fade($element, $options = [])
	{
		$this->fadeOut($element, $options);
	}

	/**
	 * Alias of fadeTo()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param float $value opacity value between 1 and 0
	 */
	public function setOpacity($element, $value)
	{
		$this->fadeTo($element, $value);
	}

	/* JQUERY UI EFFECTS */

	/**
	 * Add a jQuery-ui effect the element.
	 * This method needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param string $effect visual effect function name.
	 * @param array $options effect options.
	 */
	public function juiEffect($element, $effect, $options = [])
	{
		$options['effect'] = $effect;
		$this->jQuery($element, 'effect', [$options]);
	}

	/**
	 * Visual Effect: Blind.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function blind($element, $options = [])
	{
		$this->juiEffect($element, 'blind', $options);
	}

	/**
	 * Visual Effect: Drop out.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function drop($element, $options = [])
	{
		$this->juiEffect($element, 'drop', $options);
	}

	/**
	 * Visual Effect: Fold.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function fold($element, $options = [])
	{
		$this->juiEffect($element, 'fold', $options);
	}

	/**
	 * Visual Effect: Gradually make an element grow to a predetermined size.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function size($element, $options = [])
	{
		$this->juiEffect($element, 'size', $options);
	}

	/**
	 * Visual Effect: Gradually grow and fade the element.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function puff($element, $options = [])
	{
		$this->juiEffect($element, 'puff', $options);
	}

	/**
	 * Visual Effect: Pulsate.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function pulsate($element, $options = [])
	{
		$this->juiEffect($element, 'pulsate', $options);
	}

	/**
	 * Visual Effect: Shake the element.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function shake($element, $options = [])
	{
		$this->juiEffect($element, 'shake', $options);
	}

	/**
	 * Visual Effect: Scale the element.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function scale($element, $options = [])
	{
		$this->juiEffect($element, 'scale', $options);
	}

	/**
	 * Visual Effect: High light the element for about 2 seconds.
	 * This effect needs jQueryUI.
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function highlight($element, $options = [])
	{
		$this->juiEffect($element, 'highlight', $options);
	}

	/* jui - OLD METHODS, DEPRECATED, BACKWARDS-COMPATIBILITY */

	/**
	 * Alias of blind(), presets the direction to 'down'
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function blindDown($element, $options = [])
	{
		$options['direction'] = 'down';
		$this->blind($element, $options);
	}

	/**
	 * Alias of blind(), presets the direction to 'up'
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function blindUp($element, $options = [])
	{
		$options['direction'] = 'up';
		$this->blind($element, $options);
	}

	/**
	 * Alias of drop()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function dropOut($element, $options = [])
	{
		$this->drop($element, $options);
	}

	/**
	 * Alias of size()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function grow($element, $options = [])
	{
		$this->size($element, $options);
	}

	/**
	 * Alias of scale()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function shrink($element, $options = [])
	{
		$options['percent'] = 0;
		$this->scale($element, $options);
	}

	/**
	 * Alias of scale()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function squish($element, $options = [])
	{
		$options['origin'] = ['top', 'left'];
		$options['percent'] = 0;
		$this->scale($element, $options);
	}

	/**
	 * Alias of scale()
	 * @deprecated since 3.4
	 * @param TControl $element control element or element id
	 * @param array $options visual effect key-value pair options.
	 */
	public function switchOff($element, $options = [])
	{
		$options['direction'] = 'vertical';
		$options['percent'] = 0;
		$this->scale($element, $options);
	}
}
