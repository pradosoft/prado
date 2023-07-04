<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TDataGridPagerEventParameter;

/**
 * TActiveDataGridPagerEventParameter class
 *
 * TActiveDataGridPagerEventParameter encapsulates the parameter data for
 * {@see \Prado\Web\UI\ActiveControls\TActiveDataGrid::onPagerCreated OnPagerCreated} event of {@see \Prado\Web\UI\ActiveControls\TActiveDataGrid} controls.
 * The {@see getPager Pager} property indicates the datagrid pager related with the event.
 *
 * @author Fabio Bas <ctrlaltca@gmail.com>
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
		parent::__construct($pager);
	}
}
