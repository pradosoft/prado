<?php
/**
 * TImageButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TImageClickEventParameter class
 *
 * TImageClickEventParameter encapsulates the parameter data for
 * {@see \Prado\Web\UI\WebControls\TImageButton::onClick Click} event of {@see \Prado\Web\UI\WebControls\TImageButton} controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TImageClickEventParameter extends \Prado\TEventParameter
{
	/**
	 * the X coordinate of the clicking point
	 * @var int
	 */
	private $_x = 0;
	/**
	 * the Y coordinate of the clicking point
	 * @var int
	 */
	private $_y = 0;

	/**
	 * Constructor.
	 * @param int $x X coordinate of the clicking point
	 * @param int $y Y coordinate of the clicking point
	 */
	public function __construct($x, $y)
	{
		$this->_x = $x;
		$this->_y = $y;
		parent::__construct();
	}

	/**
	 * @return int X coordinate of the clicking point, defaults to 0
	 */
	public function getX()
	{
		return $this->_x;
	}

	/**
	 * @param int $value X coordinate of the clicking point
	 */
	public function setX($value)
	{
		$this->_x = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return int Y coordinate of the clicking point, defaults to 0
	 */
	public function getY()
	{
		return $this->_y;
	}

	/**
	 * @param int $value Y coordinate of the clicking point
	 */
	public function setY($value)
	{
		$this->_y = TPropertyValue::ensureInteger($value);
	}
}
