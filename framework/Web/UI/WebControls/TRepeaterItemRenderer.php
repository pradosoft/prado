<?php
/**
 * TRepeaterItemRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRepeaterItemRenderer class
 *
 * TRepeaterItemRenderer can be used as a convenient base class to
 * define an item renderer class specific for {@link TRepeater}.
 *
 * TRepeaterItemRenderer extends {@link TItemDataRenderer} and implements
 * the bubbling scheme for the OnCommand event of repeater items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.0
 */
class TRepeaterItemRenderer extends TItemDataRenderer
{
	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl $sender the sender of the event
	 * @param TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof \Prado\Web\UI\TCommandEventParameter) {
			$this->raiseBubbleEvent($this, new TRepeaterCommandEventParameter($this, $sender, $param));
			return true;
		} else {
			return false;
		}
	}
}
