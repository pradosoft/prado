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
	private $_fields=array();
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
	private $_customStyle='';

	/**
	 * @return string the background color of the control
	 */
	public function getBackColor()
	{
		return isset($this->_fields['background-color'])?$this->_fields['background-color']:'';
	}

	/**
	 * @param string the background color of the control
	 */
	public function setBackColor($value)
	{
		if($value==='')
			unset($this->_fields['background-color']);
		else
			$this->_fields['background-color']=$value;
	}

	/**
	 * @return string the border color of the control
	 */
	public function getBorderColor()
	{
		return isset($this->_fields['border-color'])?$this->_fields['border-color']:'';
	}

	/**
	 * @param string the border color of the control
	 */
	public function setBorderColor($value)
	{
		if($value==='')
			unset($this->_fields['border-color']);
		else
			$this->_fields['border-color']=$value;
	}

	/**
	 * @return string the border style of the control
	 */
	public function getBorderStyle()
	{
		return isset($this->_fields['border-style'])?$this->_fields['border-style']:'';
	}

	/**
	 * Sets the border style of the control.
	 * @param string the border style of the control
	 */
	public function setBorderStyle($value)
	{
		if($value==='')
			unset($this->_fields['border-style']);
		else
			$this->_fields['border-style']=$value;
	}

	/**
	 * @return string the border width of the control
	 */
	public function getBorderWidth()
	{
		return isset($this->_fields['border-width'])?$this->_fields['border-width']:'';
	}

	/**
	 * @param string the border width of the control
	 */
	public function setBorderWidth($value)
	{
		if($value==='')
			unset($this->_fields['border-width']);
		else
			$this->_fields['border-width']=$value;
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
		return isset($this->_fields['color'])?$this->_fields['color']:'';
	}

	/**
	 * @param string the foreground color of the control
	 */
	public function setForeColor($value)
	{
		if($value==='')
			unset($this->_fields['color']);
		else
			$this->_fields['color']=$value;
	}

	/**
	 * @return string the height of the control
	 */
	public function getHeight()
	{
		return isset($this->_fields['height'])?$this->_fields['height']:'';
	}

	/**
	 * @param string the height of the control
	 */
	public function setHeight($value)
	{
		if($value==='')
			unset($this->_fields['height']);
		else
			$this->_fields['height']=$value;
	}

	/**
	 * @return string the custom style of the control
	 */
	public function getCustomStyle()
	{
		return $this->_customStyle;
	}

	/**
	 * Sets custom style fields from a string.
	 * Custom style fields will be overwritten by style fields explicitly defined.
	 * @param string the custom style of the control
	 */
	public function setCustomStyle($value)
	{
		$this->_customStyle=$value;
	}

	/**
	 * @return string a single style field value set via {@link setStyleField}. Defaults to empty string.
	 */
	public function getStyleField($name)
	{
		return isset($this->_fields[$name])?$this->_fields[$name]:'';
	}

	/**
	 * Sets a single style field value.
	 * Style fields set by this method will overwrite those set by {@link setCustomStyle}.
	 * @param string style field name
	 * @param string style field value
	 */
	public function setStyleField($name,$value)
	{
		$this->_fields[$name]=$value;
	}

	/**
	 * Clears a single style field value;
	 * @param string style field name
	 */
	public function clearStyleField($name)
	{
		unset($this->_fields[$name]);
	}

	/**
	 * @return boolean whether a style field has been defined by {@link setStyleField}
	 */
	public function hasStyleField($name)
	{
		return isset($this->_fields[$name]);
	}

	/**
	 * @return string the width of the control
	 */
	public function getWidth()
	{
		return isset($this->_fields['width'])?$this->_fields['width']:'';
	}

	/**
	 * @param string the width of the control
	 */
	public function setWidth($value)
	{
		if($value==='')
			unset($this->_fields['width']);
		else
			$this->_fields['width']=$value;
	}

	/**
	 * @param boolean if the style contains nothing
	 */
	public function getIsEmpty()
	{
		return empty($this->_fields) && $this->_class==='' && $this->_customStyle==='' && (!$this->_font || $this->_font->getIsEmpty());
	}

	/**
	 * Resets the style to the original empty state.
	 */
	public function reset()
	{
		$this->_fields=array();
		$this->_font=null;
		$this->_class='';
		$this->_customStyle='';
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
		foreach($style->_fields as $name=>$value)
			$this->_fields[$name]=$value;
		if($style->_class!=='')
			$this->_class=$style->_class;
		if($style->_customStyle!=='')
			$this->_customStyle=$style->_customStyle;
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
		foreach($this->_fields as $name=>$value)
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
		if($this->_customStyle!=='')
		{
			foreach(explode(';',$this->_customStyle) as $style)
			{
				$arr=explode(':',$style);
				if(isset($arr[1]) && trim($arr[0])!=='')
					$writer->addStyleAttribute(trim($arr[0]),trim($arr[1]));
			}
		}
		foreach($this->_fields as $name=>$value)
			$writer->addStyleAttribute($name,$value);
		if($this->_font!==null)
			$this->_font->addAttributesToRender($writer);
		if($this->_class!=='')
			$writer->addAttribute('class',$this->_class);
	}
}


class TTableStyle extends TStyle
{
}

class TTableItemStyle extends TStyle
{
}


?>