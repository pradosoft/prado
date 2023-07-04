<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TControl;

/**
 * TDataListItemEventParameter class
 *
 * TDataListItemEventParameter encapsulates the parameter data for
 * {@see \Prado\Web\UI\WebControls\TDataList::onItemCreated ItemCreated} event of {@see \Prado\Web\UI\WebControls\TDataList} controls.
 * The {@see getItem Item} property indicates the DataList item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataListItemEventParameter extends \Prado\TEventParameter
{
	/**
	 * The datalist item control responsible for the event.
	 * @var IItemDataRenderer&\Prado\Web\UI\TControl
	 */
	private $_item;

	/**
	 * Constructor.
	 * @param IItemDataRenderer&\Prado\Web\UI\TControl $item DataList item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item = $item;
	}

	/**
	 * @return IItemDataRenderer&\Prado\Web\UI\TControl datalist item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}
