<?php
/**
 * TPanel class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TPanel class
 *
 * TPanel represents a component that acts as a container for other component.
 * It is especially useful when you want to generate components programmatically
 * or hide/show a group of components.
 *
 * By default, TPanel displays a &lt;div&gt; element on a page.
 * Children of TPanel are displayed as the body content of the element.
 * The property {@link setWrap Wrap} can be used to set whether the body content
 * should wrap or not. {@link setHorizontalAlign HorizontalAlign} governs how
 * the content is aligned horizontally, and {@link getDirection Direction} indicates
 * the content direction (left to right or right to left). You can set
 * {@link setBackImageUrl BackImageUrl} to give a background image to the panel,
 * and you can ste {@link setGroupingText GroupingText} so that the panel is
 * displayed as a field set with a legend text. Finally, you can specify
 * a default button to be fired when users press 'return' key within the panel
 * by setting the {@link setDefaultButton DefaultButton} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TPanel extends TWebControl
{
	/**
	 * @var string ID path to the default button
	 */
	private $_defaultButton='';

	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 * @throws TInvalidDataValueException if default button is not right.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($url=trim($this->getBackImageUrl()))!=='')
			$writer->addStyleAttribute('background-image','url('.$url.')');
		switch($this->getScrollBars())
		{
			case 'Horizontal': $writer->addStyleAttribute('overflow-x','scroll'); break;
			case 'Vertical': $writer->addStyleAttribute('overflow-y','scroll'); break;
			case 'Both': $writer->addStyleAttribute('overflow','scroll'); break;
			case 'Auto': $writer->addStyleAttribute('overflow','auto'); break;
		}
		if(($align=$this->getHorizontalAlign())!=='')
			$writer->addStyleAttribute('text-align',$align);
		if(!$this->getWrap())
			$writer->addStyleAttribute('white-space','nowrap');
		if(($dir=$this->getDirection())!=='')  // ltr or rtl
			$writer->addStyleAttribute('direction',$dir);
		if(($butt=$this->getDefaultButton())!=='')
		{
			if(($button=$this->findControl($butt))===null)
				throw new TInvalidDataValueException('panel_defaultbutton_invalid',$butt);
			else
			{
				$onkeypress=$this->getPage()->getClientScript()->registerDefaultButtonScript($button);
				$writer->addAttribute('onkeypress',$onkeypress);
			}
		}
	}

	/**
	 * @return boolean whether the content wraps within the panel.
	 */
	public function getWrap()
	{
		return $this->getViewState('Wrap',true);
	}

	/**
	 * Sets the value indicating whether the content wraps within the panel.
	 * @param boolean whether the content wraps within the panel.
	 */
	public function setWrap($value)
	{
		$this->setViewState('Wrap',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the horizontal alignment of the contents within the panel, defaults to empty string (meaning not set).
	 */
	public function getHorizontalAlign()
	{
		return $this->getViewState('HorizontalAlign','');
	}

	/**
	 * Sets the horizontal alignment of the contents within the panel.
     * Valid values include 'justify', 'left', 'center', 'right' or empty string.
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->setViewState('HorizontalAlign',$value,'');
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->getViewState('BackImageUrl','');
	}

	/**
	 * Sets the URL of the background image for the panel component.
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->setViewState('BackImageUrl',$value,'');
	}

	/**
	 * @return string alignment of the content in the panel.
	 * Valid values include 'ltr' (left to right) and 'rtl' (right to left).
	 * Defaults to empty.
	 */
	public function getDirection()
	{
		return $this->getViewState('Direction','');
	}

	/**
	 * @param string alignment of the content in the panel.
	 * Valid values include 'ltr' (left to right) and 'rtl' (right to left).
	 */
	public function setDirection($value)
	{
		$this->setViewState('Direction',$value,'');
	}

	/**
	 * @return string the ID path to the default button. Defaults to empty.
	 */
	public function getDefaultButton()
	{
		return $this->_defaultButton;
	}

	/**
	 * Specifies the default button for the panel.
	 * The default button will be fired (clicked) whenever a user enters 'return'
	 * key within the panel.
	 * The button must be locatable via the function call {@link TControl::findControl findControl}.
	 * @param string the ID path to the default button.
	 */
	public function setDefaultButton($value)
	{
		$this->_defaultButton=$value;
	}

	/**
	 * @return string the legend text when the panel is used as a fieldset. Defaults to empty.
	 */
	public function getGroupingText()
	{
		return $this->getViewState('GroupingText','');
	}

	/**
	 * @param string the legend text. If this value is not empty, the panel will be rendered as a fieldset.
	 */
	public function setGroupingText($value)
	{
		$this->setViewState('GroupingText',$value,'');
	}

	/**
	 * @return string the visibility and position of scroll bars in a panel control, defaults to None.
	 */
	public function getScrollBars()
	{
		return $this->getViewState('ScrollBars','None');
	}

	/**
	 * @param string the visibility and position of scroll bars in a panel control.
	 * Valid values include None, Auto, Both, Horizontal and Vertical.
	 */
	public function setScrollBars($value)
	{
		$this->setViewState('ScrollBars',TPropertyValue::ensureEnum($value,array('None','Auto','Both','Horizontal','Vertical')),'None');
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderBeginTag($writer)
	{
		parent::renderBeginTag($writer);
		if(($text=$this->getGroupingText())!=='')
		{
			$writer->renderBeginTag('fieldset');
			$writer->renderBeginTag('legend');
			$writer->write($text);
			$writer->renderEndTag();
		}
	}

	/**
	 * Renders the closing tag for the control
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderEndTag($writer)
	{
		if($this->getGroupingText()!=='')
			$writer->renderEndTag();
		parent::renderEndTag($writer);
	}
}

?>