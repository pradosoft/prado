<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

/**
 * ISurroundable interface
 *
 * Identifies controls that may create an additional surrounding tag. The id of the
 * tag can be obtained with {@see getSurroundingTagID}, the tag used to render the
 * surrounding container is obtained by {@see getSurroundingTag}.
 *
 * @since 3.1.2
 */
interface ISurroundable
{
	/**
	 * @return string the tag used to wrap the control in (if surrounding is needed).
	 */
	public function getSurroundingTag();

	/**
	 * @return string the id of the embedding tag of the control or the control's clientID if not surrounded.
	 */
	public function getSurroundingTagID();
}
