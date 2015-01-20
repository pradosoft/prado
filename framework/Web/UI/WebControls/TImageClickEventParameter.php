<?php
/**
 * TImageButton class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

/**
 * TImageClickEventParameter class
 *
 * TImageClickEventParameter encapsulates the parameter data for
 * {@link TImageButton::onClick Click} event of {@link TImageButton} controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TImageClickEventParameter extends TEventParameter
{
	/**
	 * the X coordinate of the clicking point
	 * @var integer
	 */
	private $_x=0;
	/**
	 * the Y coordinate of the clicking point
	 * @var integer
	 */
	private $_y=0;

	/**
	 * Constructor.
	 * @param integer X coordinate of the clicking point
	 * @param integer Y coordinate of the clicking point
	 */
	public function __construct($x,$y)
	{
		$this->_x=$x;
		$this->_y=$y;
	}

	/**
	 * @return integer X coordinate of the clicking point, defaults to 0
	 */
	public function getX()
	{
		return $this->_x;
	}

	/**
	 * @param integer X coordinate of the clicking point
	 */
	public function setX($value)
	{
		$this->_x=TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return integer Y coordinate of the clicking point, defaults to 0
	 */
	public function getY()
	{
		return $this->_y;
	}

	/**
	 * @param integer Y coordinate of the clicking point
	 */
	public function setY($value)
	{
		$this->_y=TPropertyValue::ensureInteger($value);
	}
}
