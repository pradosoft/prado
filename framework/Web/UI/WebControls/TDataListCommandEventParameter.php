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
 * TDataListCommandEventParameter class
 *
 * TDataListCommandEventParameter encapsulates the parameter data for
 * {@link TDataList::onItemCommand ItemCommand} event of {@link TDataList} controls.
 *
 * The {@link getItem Item} property indicates the DataList item related with the event.
 * The {@link getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataListCommandEventParameter extends \Prado\Web\UI\TCommandEventParameter
{
	/**
	 * @var TControl the datalist item control responsible for the event.
	 */
	private $_item;
	/**
	 * @var TControl the control originally raises the <b>OnCommand</b> event.
	 */
	private $_source;

	/**
	 * Constructor.
	 * @param TControl $item datalist item responsible for the event
	 * @param TControl $source original event sender
	 * @param \Prado\Web\UI\TCommandEventParameter $param original event parameter
	 */
	public function __construct($item, $source, \Prado\Web\UI\TCommandEventParameter $param)
	{
		$this->_item = $item;
		$this->_source = $source;
		parent::__construct($param->getCommandName(), $param->getCommandParameter());
	}

	/**
	 * @return TControl the datalist item control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return TControl the control originally raises the <b>OnCommand</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}
