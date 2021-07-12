<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TControl;

/**
 * TDataListItemEventParameter class
 *
 * TDataListItemEventParameter encapsulates the parameter data for
 * {@link TDataList::onItemCreated ItemCreated} event of {@link TDataList} controls.
 * The {@link getItem Item} property indicates the DataList item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataListItemEventParameter extends \Prado\TEventParameter
{
	/**
	 * The datalist item control responsible for the event.
	 * @var \Prado\Web\UI\TControl&IItemDataRenderer
	 */
	private $_item;

	/**
	 * Constructor.
	 * @param \Prado\Web\UI\TControl&IItemDataRenderer $item DataList item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item = $item;
	}

	/**
	 * @return \Prado\Web\UI\TControl&IItemDataRenderer datalist item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}
