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
 * Class TCircleHotSpot.
 *
 * TCircleHotSpot defines a circular hot spot region in a {@link TImageMap}
 * control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TCircleHotSpot extends THotSpot
{
	/**
	 * @return string shape of this hotspot.
	 */
	public function getShape()
	{
		return 'circle';
	}

	/**
	 * @return string coordinates defining this hotspot shape
	 */
	public function getCoordinates()
	{
		return $this->getX() . ',' . $this->getY() . ',' . $this->getRadius();
	}

	/**
	 * @return int radius of the circular HotSpot region. Defaults to 0.
	 */
	public function getRadius()
	{
		return $this->getViewState('Radius', 0);
	}

	/**
	 * @param int $value radius of the circular HotSpot region.
	 */
	public function setRadius($value)
	{
		$this->setViewState('Radius', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the X coordinate of the center of the circular HotSpot region. Defaults to 0.
	 */
	public function getX()
	{
		return $this->getViewState('X', 0);
	}

	/**
	 * @param int $value the X coordinate of the center of the circular HotSpot region.
	 */
	public function setX($value)
	{
		$this->setViewState('X', TPropertyValue::ensureInteger($value), 0);
	}

	/**
	 * @return int the Y coordinate of the center of the circular HotSpot region. Defaults to 0.
	 */
	public function getY()
	{
		return $this->getViewState('Y', 0);
	}

	/**
	 * @param int $value the Y coordinate of the center of the circular HotSpot region.
	 */
	public function setY($value)
	{
		$this->setViewState('Y', TPropertyValue::ensureInteger($value), 0);
	}
}
