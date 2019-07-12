<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * TImageMapEventParameter class.
 *
 * TImageMapEventParameter represents a postback event parameter
 * when a hotspot is clicked and posts back in a {@link TImageMap}.
 * To retrieve the post back value associated with the hotspot being clicked,
 * access {@link getPostBackValue PostBackValue}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TImageMapEventParameter extends \Prado\TEventParameter
{
	private $_postBackValue;

	/**
	 * Constructor.
	 * @param string $postBackValue post back value associated with the hotspot clicked
	 */
	public function __construct($postBackValue)
	{
		$this->_postBackValue = $postBackValue;
	}

	/**
	 * @return string post back value associated with the hotspot clicked
	 */
	public function getPostBackValue()
	{
		return $this->_postBackValue;
	}
}
