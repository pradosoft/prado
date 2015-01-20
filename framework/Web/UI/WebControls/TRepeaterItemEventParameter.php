<?php
/**
 * TRepeater class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TRepeaterItemEventParameter class
 *
 * TRepeaterItemEventParameter encapsulates the parameter data for
 * {@link TRepeater::onItemCreated ItemCreated} event of {@link TRepeater} controls.
 * The {@link getItem Item} property indicates the repeater item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TRepeaterItemEventParameter extends TEventParameter
{
	/**
	 * The repeater item control responsible for the event.
	 * @var TControl
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TControl repeater item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TControl repeater item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}