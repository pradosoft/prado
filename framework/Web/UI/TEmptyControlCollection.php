<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * TEmptyControlCollection class
 *
 * TEmptyControlCollection implements an empty control list that prohibits adding
 * controls to it. This is useful for controls that do not allow child controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TEmptyControlCollection extends TControlCollection
{
	/**
	 * Constructor.
	 * @param TControl the control that owns this collection.
	 */
	public function __construct(TControl $owner)
	{
		parent::__construct($owner, true);
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by ignoring new addition.
	 * @param integer the speicified position.
	 * @param mixed new item
	 */
	public function insertAt($index, $item)
	{
		if (!is_string($item)) {  // string is possible if property tag is used. we simply ignore it in this case
			parent::insertAt($index, $item);
		}  // this will generate an exception in parent implementation
	}
}
