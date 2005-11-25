<?php
/**
 * TStyle class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TFont definition
 */
Prado::using('System.Web.UI.WebControls.TFont');

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
	 * @var array storage of CSS fields
	 */
	private $_data=array();
	/**
	 * @var TFont font object
	 */
	private $_font=null;
	/**
	 * @var string CSS class name
	 */
	private $_class='';
	/**
	 * @var string CSS style string (those not represented by specific fields of TStyle)
	 */
	private $_style='';

	/**
	 * @return string the background color of the control
	 */
	public function getBackColor()
	{
		return isset($this->_data['background-color'])?$this->_data['background-color']:'';
	}

	/**
	 * @param string the background color of the control
	 */
	public function setBackColor($value)
	{
		if($value==='')
			unset($this->_data['background-color']);
		else
			$this->_data['background-color']=$value;
	}

	/**
	 * @return string the border color of the control
	 */
	public function getBorderColor()
	{
		return isset($this->_data['border-color'])?$this->_data['border-color']:'';
	}

	/**
	 * @param string the border color of the control
	 */
	public function setBorderColor($value)
	{
		if($value==='')
			unset($this->_data['border-color']);
		else
			$this->_data['border-color']=$value;
	}

	/**
	 * @return string the border style of the control
	 */
	public function getBorderStyle()
	{
		return isset($this->_data['border-style'])?$this->_data['border-style']:'';
	}

	/**
	 * Sets the border style of the control.
	 * @param string the border style of the control
	 */
	public function setBorderStyle($value)
	{
		if($value==='')
			unset($this->_data['border-style']);
		else
			$this->_data['border-style']=$value;
	}

	/**
	 * @return string the border width of the control
	 */
	public function getBorderWidth()
	{
		return isset($this->_data['border-width'])?$this->_data['border-width']:'';
	}

	/**
	 * @param string the border width of the control
	 */
	public function setBorderWidth($value)
	{
		if($value==='')
			unset($this->_data['border-width']);
		else
			$this->_data['border-width']=$value;
	}

	/**
	 * @return string the CSS class of the control
	 */
	public function getCssClass()
	{
		return $this->_class;
	}

	/**
	 * @param string the name of the CSS class of the control
	 */
	public function setCssClass($value)
	{
		$this->_class=$value;
	}

	/**
	 * @return TFont the font of the control
	 */
	public function getFont()
	{
		if($this->_font===null)
			$this->_font=new TFont;
		return $this->_font;
	}

	/**
	 * @return string the foreground color of the control
	 */
	public function getForeColor()
	{
		return isset($this->_data['color'])?$this->_data['color']:'';
	}

	/**
	 * @param string the foreground color of the control
	 */
	public function setForeColor($value)
	{
		if($value==='')
			unset($this->_data['color']);
		else
			$this->_data['color']=$value;
	}

	/**
	 * @return string the height of the control
	 */
	public function getHeight()
	{
		return isset($this->_data['height'])?$this->_data['height']:'';
	}

	/**
	 * @param string the height of the control
	 */
	public function setHeight($value)
	{
		if($value==='')
			unset($this->_data['height']);
		else
			$this->_data['height']=$value;
	}

	/**
	 * @return string the custom style of the control
	 */
	public function getStyle()
	{
		return $this->_style;
	}

	/**
	 * @param string the custom style of the control
	 */
	public function setStyle($value)
	{
		$this->_style=$value;
	}

	/**
	 * @return string the width of the control
	 */
	public function getWidth()
	{
		return isset($this->_data['width'])?$this->_data['width']:'';
	}

	/**
	 * @param string the width of the control
	 */
	public function setWidth($value)
	{
		if($value==='')
			unset($this->_data['width']);
		else
			$this->_data['width']=$value;
	}

	/**
	 * @param boolean if the style contains nothing
	 */
	public function getIsEmpty()
	{
		return empty($this->_data) && $this->_class==='' && $this->_style==='' && (!$this->_font || $this->_font->getIsEmpty());
	}

	/**
	 * Resets the style to the original empty state.
	 */
	public function reset()
	{
		$this->_data=array();
		$this->_font=null;
		$this->_class='';
		$this->_style='';
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
		foreach($style->_data as $name=>$value)
			$this->_data[$name]=$value;
		if($style->_class!=='')
			$this->_class=$style->_class;
		if($style->_style!=='')
			$this->_style=$style->_style;
		if($style->_font!==null)
			$this->getFont()->mergeWith($style->_font);
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
		$str='';
		foreach($this->_data as $name=>$value)
			$str.=' '.$name.':'.$value.';';
		if($this->_font)
			$str.=$this->_font->toString();
		return $str;
	}

	/**
	 * Adds attributes related to CSS styles to renderer.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function addAttributesToRender($writer)
	{
		if($this->_style!=='')
		{
			foreach(explode(';',$this->_style) as $style)
			{
				$arr=explode(':',$style);
				if(isset($arr[1]) && trim($arr[0])!=='')
					$writer->addStyleAttribute(trim($arr[0]),trim($arry[1]));
			}
		}
		foreach($this->_data as $name=>$value)
			$writer->addStyleAttribute($name,$value);
		if($this->_font!==null)
			$this->_font->addAttributesToRender($writer);
		if($this->_class!=='')
			$writer->addAttribute('class',$this->_class);
	}
}

?>