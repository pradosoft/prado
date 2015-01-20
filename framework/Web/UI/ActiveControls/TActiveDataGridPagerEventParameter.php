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
 * TActiveDataGridPagerEventParameter class
 *
 * TActiveDataGridPagerEventParameter encapsulates the parameter data for
 * {@link TActiveDataGrid::onPagerCreated OnPagerCreated} event of {@link TActiveDataGrid} controls.
 * The {@link getPager Pager} property indicates the datagrid pager related with the event.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package System.Web.UI.ActiveControls
 * @since 3.2.1
 */
class TActiveDataGridPagerEventParameter extends TDataGridPagerEventParameter
{
	/**
	 * Constructor.
	 * @param TActiveDataGridPager datagrid pager related with the corresponding event
	 */
	public function __construct(TActiveDataGridPager $pager)
	{
		$this->_pager=$pager;
	}
}