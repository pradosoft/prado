<?php
/**
 * TColorPicker class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TClientSideOptions;

/**
 * TColorPickerClientSide class.
 *
 * Client-side javascript code options.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
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
	 * @param string $javascript javascript code for when a color is selected.
	 */
	public function setOnColorSelected($javascript)
	{
		$this->setFunction('OnColorSelected', $javascript);
	}
}
