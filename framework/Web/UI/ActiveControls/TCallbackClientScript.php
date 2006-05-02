<?php
/**
 * TCallbackClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 */
 
/**
 * TCallbackClientScript class.
 * 
 * The TCallbackClientScript class provides corresponding methods that can be
 * executed on the client-side (i.e. the browser client that is viewing
 * the page) during a callback response.
 * 
 * The avaiable methods includes setting/clicking input elements, changing Css
 * styles, hiding/showing elements, and adding visual effects to elements on the
 * page. The client-side methods can be access through the CallbackClient
 * property available in TPage.
 * 
 * For example, to hide "$myTextBox" element during callback response, do
 * <code> 		
 * $this->getPage()->getCallbackClient()->hide($myTextBox);
 * </code>
 * 
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.ActiveControls
 * @since 3.0
 */ 
class TCallbackClientScript 
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
		return $this->_actions;
	}
	
	/**
	 * Executes a client-side statement.
	 * @param string javascript function name
	 * @param array list of arguments for the function
	 */
	public function callClientFunction($function, $params=null)
	{
		if(!is_array($params)) 
			$params = array($params);
			
		if(count($params) > 0)
		{
			if($params[0] instanceof TControl)
				$params[0] = $params[0]->getID();
		}
		$this->_actions->add(array($function => $params));
	}

	/**
	 * Client script to set the value of a particular input element.
	 * @param TControl|string control element to set the new value
	 * @param string new value
	 */
	public function setValue($input, $text)
	{
		$this->callClientFunction('Prado.Element.setValue', array($input, $text));
	}

	/**
	 * Client script to select/clear/check a drop down list, check box list, 
	 * or radio button list.
	 * The second parameter determines the selection method. Valid methods are
	 *  - <b>Value</b>, select or check by value
	 *  - <b>Index</b>, select or check by list index (zero based index)
	 *  - <b>All</b>, selects or checks all in the list
	 *  - <b>Clear</b>, clears or selections or checks in the list
	 *  - <b>Invert</b>, inverts the current selection or checks.
	 * @param TControl|string list control
	 * @param string selection method
	 * @param string|int the value or index to select/check.
	 */
	public function select($listControl, $method="Value", $valueOrIndex=null)
	{
		$this->callClientFunction('Prado.Element.select', array($listControl, $method, $valueOrIndex));
	}		

	/**
	 * Client script to click on an element. <b>This client-side function
	 * is unpredictable.</b>
	 * @param TControl|string control element or element id
	 */
	public function click($control)
	{
		$this->callClientFunction('Prado.Element.click', $control);
	}

	/**
	 * Client script to check or uncheck a checkbox or radio button.
	 * @param TControl|string control element or element id
	 * @param boolean check or uncheck the checkbox or radio button.
	 */
	public function check($checkbox, $checked=true)
	{
		$this->select($checkbox, "Value", $checked);
	}

	/**
	 * Sets the attribute of a particular control. 
	 * @param TControl|string control element or element id
	 * @param string attribute name
	 * @param string attribute value
	 */
	public function setAttribute($control, $name, $value)
	{
		$this->callClientFunction('Prado.Element.setAttribute',array($control, $name, $value));
	}

	/**
	 * Sets the options of a select input element.
	 * @param TControl|string control element or element id
	 * @param TCollection a list of new options
	 */
	public function setOptions($control, $items)
	{
		$options = array();
		foreach($items as $item)
			$options[] = array($item->getText(),$item->getValue());
		$this->callClientFunction('Prado.Element.setOptions', array($control, $options));
	}
	
	/**
	 * Shows an element by changing its CSS display style as empty.
	 * @param TControl|string control element or element id 
	 */
	public function show($element)
	{
		$this->callClientFunction('Element.show', $element);
	}

	/**
	 * Hides an element by changing its CSS display style to "none".
	 * @param TControl|string control element or element id 
	 */
	public function hide($element)
	{
		$this->callClientFunction('Element.hide', $element);
	}

	/**
	 * Toggles the visibility of the element.
	 * @param TControl|string control element or element id 
	 */
	public function toggle($element)
	{
		$this->callClientFunction('Element.toggle', $element);
	}

	/**
	 * Removes an element from the HTML page.
	 * @param TControl|string control element or element id
	 */
	public function remove($element)
	{
		$this->callClientFunction('Element.remove', $element);
	}

	/**
	 * Update the element's innerHTML with new content.
	 * @param TControl|string control element or element id
	 * @param TControl|string new HTML content, if content is of a TControl, the
	 * controls render method is called.
	 */
	public function update($element, $innerHTML)
	{
		if($innerHTML instanceof TControl)
			$innerHTML = $innerHTML->render();
		$this->callClientFunction('Element.update', array($element, $innerHTML));
	}

	/**
	 * Replace the innerHTML of a content with fragements of the response body.
	 * @param TControl|string control element or element id
	 */
	public function replaceContent($element)
	{
		$this->callClientFunction('Prado.Element.replaceContent', $element);
	}

	/**
	 * Add a Css class name to the element.
	 * @param TControl|string control element or element id
	 * @param string CssClass name to add.
	 */
	public function addCssClass($element, $cssClass)
	{
		$this->callClientFunction('Element.addClassName', array($element, $cssClass));
	}

	/**
	 * Remove a Css class name from the element.
	 * @param TControl|string control element or element id
	 * @param string CssClass name to remove.
	 */
	public function removeCssClass($element, $cssClass)
	{
		$this->callClientFunction('Element.removeClassName', array($element, $cssClass));
	}

	/**
	 * Sets the CssClass of an element.
	 * @param TControl|string control element or element id
	 * @param string new CssClass name for the element.
	 */
	public function setCssClass($element, $cssClass)
	{
		$this->callClientFunction('Prado.Element.CssClass.set', array($element, $cssClass));
	}

	/**
	 * Scroll the top of the browser viewing area to the location of the
	 * element.
	 * @param TControl|string control element or element id
	 */
	public function scrollTo($element)
	{
		$this->callClientFunction('Element.scrollTo', $element);
	}

	/**
	 * Sets the style of element. The style must be a key-value array where the
	 * key is the style property and the value is the style value.
	 * @param TControl|string control element or element id
	 * @param array list of key-value pairs as style property and style value.
	 */
	public function setStyle($element, $styles)
	{
		$this->callClientFunction('Element.setStyle', array($element, $styles));
	}

	/**
	 * Insert a HTML fragement after the element.
	 * @param TControl|string control element or element id
	 * @param TControl|string HTML fragement, otherwise if TControl, its render
	 * method will be called.
	 */
	public function insertAfter($element, $innerHTML)
	{
		if($innerHTML instanceof TControl)
			$innerHTML = $innerHTML->render();
		$this->callClientFunction('Prado.Element.Insert.After', array($element, $innerHTML));
	}

	/**
	 * Insert a HTML fragement before the element.
	 * @param TControl|string control element or element id
	 * @param TControl|string HTML fragement, otherwise if TControl, its render
	 * method will be called.
	 */	
	public function insertBefore($element, $innerHTML)
	{
		if($innerHTML instanceof TControl)
			$innerHTML = $innerHTML->render();
		$this->callClientFunction('Prado.Element.Insert.Before', array($element, $innerHTML));
	}

	/**
	 * Insert a HTML fragement below the element.
	 * @param TControl|string control element or element id
	 * @param TControl|string HTML fragement, otherwise if TControl, its render
	 * method will be called.
	 */
	public function insertBelow($element, $innerHTML)
	{
		if($innerHTML instanceof TControl)
			$innerHTML = $innerHTML->render();
		$this->callClientFunction('Prado.Element.Insert.Below', array($element, $innerHTML));
	}

	/**
	 * Insert a HTML fragement above the element.
	 * @param TControl|string control element or element id
	 * @param TControl|string HTML fragement, otherwise if TControl, its render
	 * method will be called.
	 */
	public function insertAbove($element, $innerHTML)
	{
		if($innerHTML instanceof TControl)
			$innerHTML = $innerHTML->render();
		$this->callClientFunction('Prado.Element.Insert.Above', array($element, $innerHTML));
	}	

	/**
	 * Add a visual effect the element.
	 * @param string visual effect function name.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options.
	 */
	public function visualEffect($type, $element, $options=null)
	{
		$this->callClientFunction($type, is_array($options) ? array($element, $options) : $element);
	}

	/**
	 * Visual Effect: Gradually make the element appear.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function appear($element, $options=null)
	{
		$this->visualEffect('Effect.Appear', $element, $options);
	}

	/**
	 * Visual Effect: Blind down.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function blindDown($element, $options=null)
	{
		$this->visualEffect('Effect.BlindDown', $element, $options);
	}

	/**
	 * Visual Effect: Blind up.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function blindUp($element, $options=null)
	{
		$this->visualEffect('Effect.BlindUp', $element, $options);
			
	}

	/**
	 * Visual Effect: Drop out.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function dropOut($element, $options=null)
	{
		$this->visualEffect('Effect.DropOut', $element, $options);
	}
	
	/**
	 * Visual Effect: Gradually fade the element.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function fade($element, $options=null)
	{
		$this->visualEffect('Effect.Fade', $element, $options);
	}
	
	/**
	 * Visual Effect: Fold.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function fold($element, $options = null)
	{
		$this->visualEffect('Effect.Fold', $element, $options);
	}

	/**
	 * Visual Effect: Gradually make an element grow to a predetermined size.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function grow($element, $options=null)
	{
		$this->visualEffect('Effect.Grow', $element, $options);
	}

	/**
	 * Visual Effect: Gradually grow and fade the element.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function puff($element, $options=null)
	{
		$this->visualEffect('Effect.Puff', $element, $options);
	}

	/**
	 * Visual Effect: Pulsate.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */	
	public function pulsate($element, $options=null)
	{
		$this->visualEffect('Effect.Pulsate', $element, $options);
	}

	/**
	 * Visual Effect: Shake the element.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function shake($element, $options=null)
	{
		$this->visualEffect('Effect.Shake', $element, $options);
	}

	/**
	 * Visual Effect: Shrink the element.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function shrink($element, $options=null)
	{
		$this->visualEffect('Effect.Shrink', $element, $options);
	}

	/**
	 * Visual Effect: Slide down.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function slideDown($element, $options=null)
	{
		$this->visualEffect('Effect.SlideDown', $element, $options);
	}

	/**
	 * Visual Effect: Side up.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function slideUp($element, $options=null)
	{
		$this->visualEffect('Effect.SlideUp', $element, $options);
	}
	
	/**
	 * Visual Effect: Squish the element.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function squish($element, $options=null)
	{
		$this->visualEffect('Effect.Squish', $element, $options);
	}
	
	/**
	 * Visual Effect: Switch Off effect.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function switchOff($element, $options=null)
	{
		$this->visualEffect('Effect.SwitchOff', $element, $options);
	}

	/**
	 * Visual Effect: High light the element for about 2 seconds.
	 * @param TControl|string control element or element id
	 * @param array visual effect key-value pair options. 
	 */
	public function highlight($element, $options=null)
	{
		$this->visualEffect('Effect.Highlight', $element, $options);
	}
}

?>
