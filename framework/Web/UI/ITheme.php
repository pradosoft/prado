<?php

/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * ITheme interface.
 *
 * This interface must be implemented by theme.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface ITheme
{
	/**
	 * Applies this theme to the specified control.
	 * @param \Prado\Web\UI\TControl $control the control to be applied with this theme
	 */
	public function applySkin($control);
}
