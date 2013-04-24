<?php
/**
 * TCompositeControl class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TCompositeControl.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI
 */

/**
 * TCompositeControl class.
 * TCompositeControl is the base class for controls that are composed
 * by other controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id: TCompositeControl.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Web.UI
 * @since 3.0
 */
class TCompositeControl extends TControl implements INamingContainer
{
	/**
	 * Performs the OnInit step for the control and all its child controls.
	 * This method overrides the parent implementation
	 * by ensuring child controls are created first.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function initRecursive($namingContainer=null)
	{
		$this->ensureChildControls();
		parent::initRecursive($namingContainer);
	}
}

