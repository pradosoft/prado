<?php
/**
 * TCompositeControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TCompositeControl class.
 * TCompositeControl is the base class for controls that are composed
 * by other controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TCompositeControl extends TControl implements INamingContainer
{
	/**
	 * Performs the OnInit step for the control and all its child controls.
	 * This method overrides the parent implementation
	 * by ensuring child controls are created first.
	 * Only framework developers should use this method.
	 * @param TControl $namingContainer the naming container control
	 */
	protected function initRecursive($namingContainer = null)
	{
		$this->ensureChildControls();
		parent::initRecursive($namingContainer);
	}
}
