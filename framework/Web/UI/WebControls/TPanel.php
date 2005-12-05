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
 * It is especially useful when you want to generate components programmatically or hide/show a group of components.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TPanel extends TWebControl
{
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
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if(($url=trim($this->getBackImageUrl()))!=='')
			$writer->addStyleAttribute('background-image','url('.$url.')');
		//this.AddScrollingAttribute(this.ScrollBars, writer);
		if(($align=$this->getHorizontalAlign())!=='')
			$writer->addStyleAttribute('text-align',$align);
		if(!$this->getWrap())
			$writer->addStyleAttribute('white-space','nowrap');
		if(($dir=$this->getDirection())!=='')  // ltr or rtl
			$writer->addStyleAttribute('direction',$dir);
		if(($butt=$this->getDefaultButton())!=='')
		{
			if(($button=$this->findControl($butt))===null)
				throw new TInvalidOperationException('panel_defaultbutton_invalid');
			else
				$this->getPage()->getClientScript()->registerDefaultButtonScript($button,$writer);
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
	 * @return string the horizontal alignment of the contents within the panel.
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

	public function getDefaultButton()
	{
		return $this->_defaultButton;
	}

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
		if(($text=$this->getGroupingText())!=='')
			$writer->renderEndTag();
		parent::renderEndTag($writer);
	}
}

?>