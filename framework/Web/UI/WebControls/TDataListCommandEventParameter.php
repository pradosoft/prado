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
 * TDataListCommandEventParameter class
 *
 * TDataListCommandEventParameter encapsulates the parameter data for
 * {@see TDataList::onItemCommand ItemCommand} event of {@see TDataList} controls.
 *
 * The {@see getItem Item} property indicates the DataList item related with the event.
 * The {@see getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TDataListCommandEventParameter extends \Prado\Web\UI\TCommandEventParameter
{
	/**
	 * @var \Prado\Web\UI\TControl the datalist item control responsible for the event.
	 */
	private $_item;
	/**
	 * @var \Prado\Web\UI\TControl the control originally raises the <b>OnCommand</b> event.
	 */
	private $_source;

	/**
	 * Constructor.
	 * @param \Prado\Web\UI\TControl $item datalist item responsible for the event
	 * @param \Prado\Web\UI\TControl $source original event sender
	 * @param \Prado\Web\UI\TCommandEventParameter $param original event parameter
	 */
	public function __construct($item, $source, \Prado\Web\UI\TCommandEventParameter $param)
	{
		$this->_item = $item;
		$this->_source = $source;
		parent::__construct($param->getCommandName(), $param->getCommandParameter());
	}

	/**
	 * @return \Prado\Web\UI\TControl the datalist item control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return \Prado\Web\UI\TControl the control originally raises the <b>OnCommand</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}
