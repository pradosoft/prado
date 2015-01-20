<?php
/**
 * TImageMap and related class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * Class TPolygonHotSpot.
 *
 * TPolygonHotSpot defines a polygon hot spot region in a {@link
 * TImageMap} control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TPolygonHotSpot extends THotSpot
{
	/**
	 * @return string shape of this hotspot.
	 */
	public function getShape()
	{
		return 'poly';
	}

	/**
	 * @return string coordinates of the vertices defining the polygon.
	 * Coordinates are concatenated together with comma ','. Each pair
	 * represents (x,y) of a vertex.
	 */
	public function getCoordinates()
	{
		return $this->getViewState('Coordinates','');
	}

	/**
	 * @param string coordinates of the vertices defining the polygon.
	 * Coordinates are concatenated together with comma ','. Each pair
	 * represents (x,y) of a vertex.
	 */
	public function setCoordinates($value)
	{
		$this->setViewState('Coordinates',$value,'');
	}
}