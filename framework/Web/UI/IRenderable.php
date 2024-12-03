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
 * IRenderable interface.
 *
 * This interface must be implemented by classes that can be rendered
 * to end-users.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IRenderable
{
	/**
	 * Renders the component to end-users.
	 * @param \Prado\IO\ITextWriter $writer writer for the rendering purpose
	 */
	public function render($writer);
}
