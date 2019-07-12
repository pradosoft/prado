<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * IActiveControl interface.
 *
 * Active controls must implement IActiveControl interface.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1
 */
interface IActiveControl
{
	/**
	 * @return TBaseActiveControl Active control properties.
	 */
	public function getActiveControl();
}
