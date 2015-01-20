<?php
/**
 * TColorPicker class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TColorPickerClientSide class.
 *
 * Client-side javascript code options.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI.WebControls
 * @since 3.1
 */
class TColorPickerClientSide extends TClientSideOptions
{
	/**
	 * @return string javascript code for when a color is selected.
	 */
	public function getOnColorSelected()
	{
		return $this->getOption('OnColorSelected');
	}

	/**
	 * @param string javascript code for when a color is selected.
	 */
	public function setOnColorSelected($javascript)
	{
		$this->setFunction('OnColorSelected', $javascript);
	}
}
