<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TControl;

/**
 * TPagerPageChangedEventParameter class
 *
 * TPagerPageChangedEventParameter encapsulates the parameter data for
 * {@link TPager::onPageIndexChanged PageIndexChanged} event of {@link TPager} controls.
 *
 * The {@link getCommandSource CommandSource} property refers to the control
 * that originally raises the OnCommand event, while {@link getNewPageIndex NewPageIndex}
 * returns the new page index carried with the page command.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.2
 */
class TPagerPageChangedEventParameter extends \Prado\TEventParameter
{
	/**
	 * @var integer new page index
	 */
	private $_newIndex;
	/**
	 * @var TControl original event sender
	 */
	private $_source;

	/**
	 * Constructor.
	 * @param TControl the control originally raises the <b>OnCommand</b> event.
	 * @param integer new page index
	 */
	public function __construct($source, $newPageIndex)
	{
		$this->_source = $source;
		$this->_newIndex = $newPageIndex;
	}

	/**
	 * @return TControl the control originally raises the <b>OnCommand</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}

	/**
	 * @return integer new page index
	 */
	public function getNewPageIndex()
	{
		return $this->_newIndex;
	}
}
