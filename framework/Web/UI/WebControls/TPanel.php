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
	 * Creates a style object to be used by the control.
	 * This method overrides the parent impementation by creating a TPanelStyle object.
	 */
	protected function createStyle()
	{
		return new TPanelStyle;
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 * @throws TInvalidDataValueException if default button is not right.
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
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
	 * @return boolean whether the content wraps within the panel. Defaults to true.
	 */
	public function getWrap()
	{
		return $this->getStyle()->getWrap();
	}

	/**
	 * Sets the value indicating whether the content wraps within the panel.
	 * @param boolean whether the content wraps within the panel.
	 */
	public function setWrap($value)
	{
		$this->getStyle()->setWrap($value);
	}

	/**
	 * @return string the horizontal alignment of the contents within the panel, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		return $this->getStyle()->getHorizontalAlign();
	}

	/**
	 * Sets the horizontal alignment of the contents within the panel.
     * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->getStyle()->setHorizontalAlign($value);
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->getStyle()->getBackImageUrl();
	}

	/**
	 * Sets the URL of the background image for the panel component.
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->getStyle()->setBackImageUrl($value);
	}

	/**
	 * @return string alignment of the content in the panel. Defaults to 'NotSet'.
	 */
	public function getDirection()
	{
		return $this->getStyle()->getDirection();
	}

	/**
	 * @param string alignment of the content in the panel.
	 * Valid values include 'NotSet', 'LeftToRight', 'RightToLeft'.
	 */
	public function setDirection($value)
	{
		$this->getStyle()->setDirection($value);
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
		return $this->getStyle()->getScrollBars();
	}

	/**
	 * @param string the visibility and position of scroll bars in a panel control.
	 * Valid values include None, Auto, Both, Horizontal and Vertical.
	 */
	public function setScrollBars($value)
	{
		$this->getStyle()->setScrollBars($value);
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
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
	public function renderEndTag($writer)
	{
		if($this->getGroupingText()!=='')
			$writer->renderEndTag();
		parent::renderEndTag($writer);
	}
}

/**
 * TPanelStyle class.
 * TPanelStyle represents the CSS style specific for panel HTML tag.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TPanelStyle extends TStyle
{
	/**
	 * @var string the URL of the background image for the panel component
	 */
	private $_backImageUrl='';
	/**
	 * @var string alignment of the content in the panel.
	 */
	private $_direction='NotSet';
	/**
	 * @var string horizontal alignment of the contents within the panel
	 */
	private $_horizontalAlign='NotSet';
	/**
	 * @var string visibility and position of scroll bars
	 */
	private $_scrollBars='None';
	/**
	 * @var boolean whether the content wraps within the panel
	 */
	private $_wrap=true;

	/**
	 * Adds attributes related to CSS styles to renderer.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		if(($url=trim($this->_backImageUrl))!=='')
			$this->setStyleField('background-image','url('.$url.')');

		switch($this->_scrollBars)
		{
			case 'Horizontal': $this->setStyleField('overflow-x','scroll'); break;
			case 'Vertical': $this->setStyleField('overflow-y','scroll'); break;
			case 'Both': $this->setStyleField('overflow','scroll'); break;
			case 'Auto': $this->setStyleField('overflow','auto'); break;
		}

		if($this->_horizontalAlign!=='NotSet')
			$this->setStyleField('text-align',strtolower($this->_horizontalAlign));

		if(!$this->_wrap)
			$this->setStyleField('white-space','nowrap');

		if($this->_direction==='LeftToRight')
			$this->setStyleField('direction','ltr');
		else if($this->_direction==='RightToLeft')
			$this->setStyleField('direction','rtl');

		parent::addAttributesToRender($writer);
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->_backImageUrl;
	}

	/**
	 * Sets the URL of the background image for the panel component.
	 * @param string the URL
	 */
	public function setBackImageUrl($value)
	{
		$this->_backImageUrl=$value;
	}

	/**
	 * @return string alignment of the content in the panel. Defaults to 'NotSet'.
	 */
	public function getDirection()
	{
		return $this->_direction;
	}

	/**
	 * @param string alignment of the content in the panel.
	 * Valid values include 'NotSet', 'LeftToRight', 'RightToLeft'.
	 */
	public function setDirection($value)
	{
		$this->_direction=TPropertyValue::ensureEnum($value,array('NotSet','LeftToRight','RightToLeft'));
	}

	/**
	 * @return boolean whether the content wraps within the panel. Defaults to true.
	 */
	public function getWrap()
	{
		return $this->_wrap;
	}

	/**
	 * Sets the value indicating whether the content wraps within the panel.
	 * @param boolean whether the content wraps within the panel.
	 */
	public function setWrap($value)
	{
		$this->_wrap=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the horizontal alignment of the contents within the panel, defaults to 'NotSet'.
	 */
	public function getHorizontalAlign()
	{
		return $this->_horizontalAlign;
	}

	/**
	 * Sets the horizontal alignment of the contents within the panel.
     * Valid values include 'NotSet', 'Justify', 'Left', 'Right', 'Center'
	 * @param string the horizontal alignment
	 */
	public function setHorizontalAlign($value)
	{
		$this->_horizontalAlign=TPropertyValue::ensureEnum($value,array('NotSet','Left','Right','Center','Justify'));
	}

	/**
	 * @return string the visibility and position of scroll bars in a panel control, defaults to None.
	 */
	public function getScrollBars()
	{
		return $this->_scrollBars;
	}

	/**
	 * @param string the visibility and position of scroll bars in a panel control.
	 * Valid values include None, Auto, Both, Horizontal and Vertical.
	 */
	public function setScrollBars($value)
	{
		$this->_scrollBars=TPropertyValue::ensureEnum($value,array('None','Auto','Both','Horizontal','Vertical'));
	}
}
?>