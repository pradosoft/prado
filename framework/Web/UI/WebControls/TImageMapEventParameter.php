<?php

/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TImageMapEventParameter class.
 *
 * TImageMapEventParameter represents a postback event parameter
 * when a hotspot is clicked and posts back in a {@see \Prado\Web\UI\WebControls\TImageMap}.
 * To retrieve the post back value associated with the hotspot being clicked,
 * access {@see getPostBackValue PostBackValue}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
		parent::__construct();
	}

	/**
	 * @return string post back value associated with the hotspot clicked
	 */
	public function getPostBackValue()
	{
		return $this->_postBackValue;
	}
}
