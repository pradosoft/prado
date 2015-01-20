<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @copyright Copyright &copy; 2009 LANDWEHR Computer und Software GmbH
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveDataGridPager class.
 *
 * TActiveDataGridPager represents a pager in an activedatagrid.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package System.Web.UI.ActiveControls
 * @since 3.2.1
 */
class TActiveDataGridPager extends TDataGridPager
{
	protected $_callbackoptions;

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		if($this->_callbackoptions === null)
			$this->_callbackoptions = new TCallbackOptions;
		return $this->_callbackoptions->getClientSide();
	}
}