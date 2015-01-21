<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado
 */

namespace Prado;

/**
 * IRenderable interface.
 *
 * This interface must be implemented by classes that can be rendered
 * to end-users.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
 * @since 3.0
 */
interface IRenderable
{
	/**
	 * Renders the component to end-users.
	 * @param ITextWriter writer for the rendering purpose
	 */
	public function render($writer);
}