<?php
/**
 * TPanelStyle class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TStyle class file
 */
Prado::using('System.Web.UI.WebControls.TStyle');

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
	private $_backImageUrl=null;
	/**
	 * @var string alignment of the content in the panel.
	 */
	private $_direction=null;
	/**
	 * @var string horizontal alignment of the contents within the panel
	 */
	private $_horizontalAlign=null;
	/**
	 * @var string visibility and position of scroll bars
	 */
	private $_scrollBars=null;
	/**
	 * @var boolean whether the content wraps within the panel
	 */
	private $_wrap=null;

	/**
	 * Adds attributes related to CSS styles to renderer.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		if(($url=trim($this->getBackImageUrl()))!=='')
			$this->setStyleField('background-image','url('.$url.')');

		switch($this->getScrollBars())
		{
			case 'Horizontal': $this->setStyleField('overflow-x','scroll'); break;
			case 'Vertical': $this->setStyleField('overflow-y','scroll'); break;
			case 'Both': $this->setStyleField('overflow','scroll'); break;
			case 'Auto': $this->setStyleField('overflow','auto'); break;
		}

		if(($align=$this->getHorizontalAlign())!=='NotSet')
			$this->setStyleField('text-align',strtolower($align));

		if(!$this->getWrap())
			$this->setStyleField('white-space','nowrap');

		if(($direction=$this->getDirection())!=='NotSet')
		{
			if($direction==='LeftToRight')
				$this->setStyleField('direction','ltr');
			else
				$this->setStyleField('direction','rtl');
		}

		parent::addAttributesToRender($writer);
	}

	/**
	 * @return string the URL of the background image for the panel component.
	 */
	public function getBackImageUrl()
	{
		return $this->_backImageUrl===null?'':$this->_backImageUrl;
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
		return $this->_direction===null?'NotSet':$this->_direction;
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
		return $this->_wrap===null?true:$this->_wrap;
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
		return $this->_horizontalAlign===null?'NotSet':$this->_horizontalAlign;
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
		return $this->_scrollBars===null?'None':$this->_scrollBars;
	}

	/**
	 * @param string the visibility and position of scroll bars in a panel control.
	 * Valid values include None, Auto, Both, Horizontal and Vertical.
	 */
	public function setScrollBars($value)
	{
		$this->_scrollBars=TPropertyValue::ensureEnum($value,array('None','Auto','Both','Horizontal','Vertical'));
	}

	/**
	 * Sets the style attributes to default values.
	 * This method overrides the parent implementation by
	 * resetting additional TPanelStyle specific attributes.
	 */
	public function reset()
	{
		parent::reset();
		$this->_backImageUrl=null;
		$this->_direction=null;
		$this->_horizontalAlign=null;
		$this->_scrollBars=null;
		$this->_wrap=null;
	}

	/**
	 * Copies the fields in a new style to this style.
	 * If a style field is set in the new style, the corresponding field
	 * in this style will be overwritten.
	 * @param TStyle the new style
	 */
	public function copyFrom($style)
	{
		parent::copyFrom($style);
		if($style instanceof TPanelStyle)
		{
			if($style->_backImageUrl!==null)
				$this->_backImageUrl=$style->_backImageUrl;
			if($style->_direction!==null)
				$this->_direction=$style->_direction;
			if($style->_horizontalAlign!==null)
				$this->_horizontalAlign=$style->_horizontalAlign;
			if($style->_scrollBars!==null)
				$this->_scrollBars=$style->_scrollBars;
			if($style->_wrap!==null)
				$this->_wrap=$style->_wrap;
		}
	}

	/**
	 * Merges the style with a new one.
	 * If a style field is not set in this style, it will be overwritten by
	 * the new one.
	 * @param TStyle the new style
	 */
	public function mergeWith($style)
	{
		parent::mergeWith($style);
		if($style instanceof TPanelStyle)
		{
			if($this->_backImageUrl===null && $style->_backImageUrl!==null)
				$this->_backImageUrl=$style->_backImageUrl;
			if($this->_direction===null && $style->_direction!==null)
				$this->_direction=$style->_direction;
			if($this->_horizontalAlign===null && $style->_horizontalAlign!==null)
				$this->_horizontalAlign=$style->_horizontalAlign;
			if($this->_scrollBars===null && $style->_scrollBars!==null)
				$this->_scrollBars=$style->_scrollBars;
			if($this->_wrap===null && $style->_wrap!==null)
				$this->_wrap=$style->_wrap;
		}
	}
}

?>