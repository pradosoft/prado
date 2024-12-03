<?php

/**
 * TRepeater class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TControl;

/**
 * TRepeaterItemEventParameter class
 *
 * TRepeaterItemEventParameter encapsulates the parameter data for
 * {@see \Prado\Web\UI\WebControls\TRepeater::onItemCreated ItemCreated} event of {@see \Prado\Web\UI\WebControls\TRepeater} controls.
 * The {@see getItem Item} property indicates the repeater item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TRepeaterItemEventParameter extends \Prado\TEventParameter
{
	/**
	 * The repeater item control responsible for the event.
	 * @var \Prado\Web\UI\TControl
	 */
	private $_item;

	/**
	 * Constructor.
	 * @param \Prado\Web\UI\TControl $item repeater item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item = $item;
		parent::__construct();
	}

	/**
	 * @return \Prado\Web\UI\TControl repeater item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}
