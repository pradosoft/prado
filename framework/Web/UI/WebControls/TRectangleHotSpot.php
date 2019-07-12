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

use Prado\TPropertyValue;

/**
 * Class TRectangleHotSpot.
 *
 * TRectangleHotSpot defines a rectangle hot spot region in a {@link
 * TImageMap} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TRectangleHotSpot extends THotSpot
{
	/**
	 * @return string shape of this hotspot.
	 */
	public function getShape()
	{
		return 'rect';
	}

	/**
	 * @return string coordinates defining this hotspot shape
	 */
	public function getCoordinates()
	{
		return $this->getLeft() . ',' . $this->getTop() . ',' . $this->getRight() . ',' . $this->getBottom();
	}

	/**
	 * @return int the Y coordinate of the bottom side of the rectangle HotSpot region. Defaults to 0.
	 */
	public function getBottom()
	{
		return $this->getViewState('Bottom', 0);
	}

	/**
	 * @param int $value the Y coordinate of the bottom side of the rectangle HotSpot region.
	 */
	public function setBottom($value)
	{
		$this->setViewState('Bottom', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the X coordinate of the right side of the rectangle HotSpot region. Defaults to 0.
	 */
	public function getLeft()
	{
		return $this->getViewState('Left', 0);
	}

	/**
	 * @param int $value the X coordinate of the right side of the rectangle HotSpot region.
	 */
	public function setLeft($value)
	{
		$this->setViewState('Left', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the X coordinate of the right side of the rectangle HotSpot region. Defaults to 0.
	 */
	public function getRight()
	{
		return $this->getViewState('Right', 0);
	}

	/**
	 * @param int $value the X coordinate of the right side of the rectangle HotSpot region.
	 */
	public function setRight($value)
	{
		$this->setViewState('Right', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the Y coordinate of the top side of the rectangle HotSpot region. Defaults to 0.
	 */
	public function getTop()
	{
		return $this->getViewState('Top', 0);
	}

	/**
	 * @param int $value the Y coordinate of the top side of the rectangle HotSpot region.
	 */
	public function setTop($value)
	{
		$this->setViewState('Top', TPropertyValue::ensureInteger($value), 0);
	}
}
