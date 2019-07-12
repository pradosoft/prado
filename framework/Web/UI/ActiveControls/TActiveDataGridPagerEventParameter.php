<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TDataGridPagerEventParameter;

/**
 * TActiveDataGridPagerEventParameter class
 *
 * TActiveDataGridPagerEventParameter encapsulates the parameter data for
 * {@link TActiveDataGrid::onPagerCreated OnPagerCreated} event of {@link TActiveDataGrid} controls.
 * The {@link getPager Pager} property indicates the datagrid pager related with the event.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.2.1
 */
class TActiveDataGridPagerEventParameter extends TDataGridPagerEventParameter
{
	/**
	 * Constructor.
	 * @param TActiveDataGridPager $pager datagrid pager related with the corresponding event
	 */
	public function __construct(TActiveDataGridPager $pager)
	{
		$this->_pager = $pager;
	}
}
