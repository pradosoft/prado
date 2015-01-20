<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */


/**
 * TDataListItemEventParameter class
 *
 * TDataListItemEventParameter encapsulates the parameter data for
 * {@link TDataList::onItemCreated ItemCreated} event of {@link TDataList} controls.
 * The {@link getItem Item} property indicates the DataList item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemEventParameter extends TEventParameter
{
	/**
	 * The datalist item control responsible for the event.
	 * @var TControl
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TControl DataList item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TControl datalist item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}