<?php
/**
 * TRadioButtonList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRadioButtonItem class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 */

class TRadioButtonItem extends TRadioButton
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}
}
