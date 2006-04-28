<?php
/**
 * TCompositeControl class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */
 
/**
 * The TCompositeControl class is an abstract class that provides naming
 * container and control designer functionality for custom controls that
 * encompass child controls in their entirety or use the functionality of other
 * controls. You cannot use this class directly.
 * 
 * To create a custom composite control, derive from the CompositeControl class.
 * The functionality this class provides is built-in verification that child
 * controls have been created prior to being accessed
 * 
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0 
 */
abstract class TCompositeControl extends TTemplateControl
{
	/**
	 * The constructor ensures the child controls are created.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->recreateChildControls();
	}
	
	/**
	 * Recreates the child controls in a control derived from TCompositeControl.
	 */
	protected function recreateChildControls()
	{
		$this->ensureChildControls();		
	}
	
}

?>
