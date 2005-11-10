<?php
/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.xisc.com/
 * @copyright Copyright &copy; 2004-2005, Qiang Xue
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TStyle class
 *
 * TStyle encapsulates the CSS style applied to a control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TStyle extends TComponent
{
	/**
	 * @var array The enumerable type for border styles
	 */
	public static $ENUM_BORDER_STYLE=array('NotSet','None','Dashed','Dotted','Solid','Double','Groove','Ridge','Inset','Outset');

	/**
	 * Various CSS fields
	 */
	const FLD_BACKCOLOR=0;
	const FLD_BORDERCOLOR=1;
	const FLD_BORDERWIDTH=2;
	const FLD_BORDERSTYLE=3;
	const FLD_FONT=4;
	const FLD_FORECOLOR=5;
	const FLD_HEIGHT=6;
	const FLD_WIDTH=7;
	const FLD_CSSCLASS=8;
	const FLD_STYLE=9;

	/**
	 * @var array storage of CSS fields
	 */
	private $_data=array();

	/**
	 * @return string the background color of the control
	 */
	public function getBackColor()
	{
		return isset($this->_data[self::FLD_BACKCOLOR])?$this->_data[self::FLD_BACKCOLOR]:'';
	}

	/**
	 * @param string the background color of the control
	 */
	public function setBackColor($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_BACKCOLOR]);
		else
			$this->_data[self::FLD_BACKCOLOR]=$value;
	}

	/**
	 * @return string the border color of the control
	 */
	public function getBorderColor()
	{
		return isset($this->_data[self::FLD_BORDERCOLOR])?$this->_data[self::FLD_BORDERCOLOR]:'';
	}

	/**
	 * @param string the border color of the control
	 */
	public function setBorderColor($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_BORDERCOLOR]);
		else
			$this->_data[self::FLD_BORDERCOLOR]=$value;
	}

	/**
	 * @return string the border style of the control
	 */
	public function getBorderStyle()
	{
		return isset($this->_data[self::FLD_BORDERSTYLE])?$this->_data[self::FLD_BORDERSTYLE]:'';
	}

	/**
	 * Sets the border style of the control.
	 * Valid values include: 
	 * 'NotSet','None','Dashed','Dotted','Solid','Double','Groove','Ridge','Inset','Outset'
	 * @param string the border style of the control
	 */
	public function setBorderStyle($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_BORDERSTYLE]);
		else
			$this->_data[self::FLD_BORDERSTYLE]=TPropertyValue::ensureEnum($value,self::$ENUM_BORDER_STYLE);
	}

	/**
	 * @return string the border width of the control
	 */
	public function getBorderWidth()
	{
		return isset($this->_data[self::FLD_BORDERWIDTH])?$this->_data[self::FLD_BORDERWIDTH]:'';
	}

	/**
	 * @param string the border width of the control
	 */
	public function setBorderWidth($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_BORDERWIDTH]);
		else
			$this->_data[self::FLD_BORDERWIDTH]=$value;
	}

	/**
	 * @return string the CSS class of the control
	 */
	public function getCssClass()
	{
		return isset($this->_data[self::FLD_CSSCLASS])?$this->_data[self::FLD_CSSCLASS]:'';
	}

	/**
	 * @param string the name of the CSS class of the control
	 */
	public function setCssClass($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_CSSCLASS]);
		else
			$this->_data[self::FLD_CSSCLASS]=$value;
	}

	/**
	 * @return TFont the font of the control
	 */
	public function getFont()
	{
		if(!isset($this->_data[self::FLD_FONT]))
			$this->_data[self::FLD_FONT]=new TFont;
		return $this->_data[self::FLD_FONT];
	}

	/**
	 * @return string the foreground color of the control
	 */
	public function getForeColor()
	{
		return isset($this->_data[self::FLD_FORECOLOR])?$this->_data[self::FLD_FORECOLOR]:'';
	}

	/**
	 * @param string the foreground color of the control
	 */
	public function setForeColor($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_FORECOLOR]);
		else
			$this->_data[self::FLD_FORECOLOR]=$value;
	}

	/**
	 * @return string the height of the control
	 */
	public function getHeight()
	{
		return isset($this->_data[self::FLD_HEIGHT])?$this->_data[self::FLD_HEIGHT]:'';
	}

	/**
	 * @param string the height of the control
	 */
	public function setHeight($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_HEIGHT]);
		else
			$this->_data[self::FLD_HEIGHT]=$value;
	}

	/**
	 * @return string the custom style of the control
	 */
	public function getStyle()
	{
		return isset($this->_data[self::FLD_STYLE])?$this->_data[self::FLD_STYLE]:'';
	}

	/**
	 * @param string the custom style of the control
	 */
	public function setStyle($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_STYLE]);
		else
			$this->_data[self::FLD_STYLE]=$value;
	}

	/**
	 * @return string the width of the control
	 */
	public function getWidth()
	{
		return isset($this->_data[self::FLD_WIDTH])?$this->_data[self::FLD_WIDTH]:'';
	}

	/**
	 * @param string the width of the control
	 */
	public function setWidth($value)
	{
		if($value==='')
			unset($this->_data[self::FLD_WIDTH]);
		else
			$this->_data[self::FLD_WIDTH]=$value;
	}

	/**
	 * @param boolean if the style contains nothing
	 */
	public function getIsEmpty()
	{
		return empty($this->_data) || (isset($this->_data[self::FLD_FONT]) && $this->_data[self::FLD_FONT]->getIsEmpty());
	}

	/**
	 * Resets the style to the original empty state.
	 */
	public function reset()
	{
		$this->_data=array();
		$this->flags=0;
	}

	/**
	 * Merges the current style with another one.
	 * If the two styles have the same style field, the new one
	 * will overwrite the current one.
	 * @param TStyle the new style
	 */
	public function mergeWith($style)
	{
		if($style===null)
			return;
		if(isset($style->_data[self::FLD_BACKCOLOR]))
			$this->_data[self::FLD_BACKCOLOR]=$style->_data[self::FLD_BACKCOLOR];
		if(isset($style->_data[self::FLD_BORDERCOLOR]))
			$this->_data[self::FLD_BORDERCOLOR]=$style->_data[self::FLD_BORDERCOLOR];
		if(isset($style->_data[self::FLD_BORDERWIDTH]))
			$this->_data[self::FLD_BORDERWIDTH]=$style->_data[self::FLD_BORDERWIDTH];
		if(isset($style->_data[self::FLD_BORDERSTYLE]))
			$this->_data[self::FLD_BORDERSTYLE]=$style->_data[self::FLD_BORDERSTYLE];
		if(isset($style->_data[self::FLD_FORECOLOR]))
			$this->_data[self::FLD_FORECOLOR]=$style->_data[self::FLD_FORECOLOR];
		if(isset($style->_data[self::FLD_HEIGHT]))
			$this->_data[self::FLD_HEIGHT]=$style->_data[self::FLD_HEIGHT];
		if(isset($style->_data[self::FLD_WIDTH]))
			$this->_data[self::FLD_WIDTH]=$style->_data[self::FLD_WIDTH];
		if(isset($style->_data[self::FLD_FONT]))
			$this->getFont()->mergeWith($style->_data[self::FLD_FONT]);
		if(isset($style->_data[self::FLD_CSSCLASS]))
			$this->_data[self::FLD_CSSCLASS]=$style->_data[self::FLD_CSSCLASS];
	}

	/**
	 * Copies from a style.
	 * Existing style will be reset first.
	 * @param TStyle the new style
	 */
	public function copyFrom($style)
	{
		$this->reset();
		$this->mergeWith($style);
	}

	/**
	 * Converts the style into a string representation suitable for rendering.
	 * @return string the string representation of the style
	 */
	public function toString()
	{
		if($this->getIsEmpty())
			return '';
		if(($str=$this->getStyle())!=='')
			$str=rtrim($str).';';
		if(isset($this->_data[self::FLD_BACKCOLOR]))
			$str.='background-color:'.$this->_data[self::FLD_BACKCOLOR].';';
		if(isset($this->_data[self::FLD_BORDERCOLOR]))
			$str.='border-color:'.$this->_data[self::FLD_BORDERCOLOR].';';
		if(isset($this->_data[self::FLD_BORDERWIDTH]))
			$str.='border-width:'.$this->_data[self::FLD_BORDERWIDTH].';';
		if(isset($this->_data[self::FLD_BORDERSTYLE]))
			$str.='border-style:'.$this->_data[self::FLD_BORDERSTYLE].';';
		if(isset($this->_data[self::FLD_FORECOLOR]))
			$str.='color:'.$this->_data[self::FLD_FORECOLOR].';';
		if(isset($this->_data[self::FLD_HEIGHT]))
			$str.='height:'.$this->_data[self::FLD_HEIGHT].';';
		if(isset($this->_data[self::FLD_WIDTH]))
			$str.='width:'.$this->_data[self::FLD_WIDTH].';';
		if(isset($this->_data[self::FLD_FONT]))
			$str.=$this->_data[self::FLD_FONT]->toString();
		return $str;
	}

	/**
	 * Adds attributes related to CSS styles to renderer.
	 * @param THtmlTextWriter the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		$str=$this->toString();
		if($str!=='')
			$writer->addAttribute('style',$str);
		if(isset($this->_data[self::FLD_CSSCLASS]))
			$writer->addAttribute('class',$this->_data[self::FLD_CSSCLASS]);
	}
}

?>