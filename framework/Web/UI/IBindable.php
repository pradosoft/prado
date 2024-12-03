<?php

/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * IBindable interface.
 *
 * This interface must be implemented by classes that are capable of performing databinding.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IBindable
{
	/**
	 * Performs databinding.
	 */
	public function dataBind();
}
