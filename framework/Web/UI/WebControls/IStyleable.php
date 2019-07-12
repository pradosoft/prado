<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * IStyleable interface.
 *
 * This interface should be implemented by classes that support CSS styles.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.0
 */
interface IStyleable
{
	/**
	 * @return bool whether the object has defined any style information
	 */
	public function getHasStyle();
	/**
	 * @return TStyle the object representing the css style of the object
	 */
	public function getStyle();
	/**
	 * Removes all styles associated with the object
	 */
	public function clearStyle();
}
