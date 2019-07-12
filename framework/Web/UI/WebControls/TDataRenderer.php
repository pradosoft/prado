<?php
/**
 * TDataRenderer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 * @since 3.1.2
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\TTemplateControl;

/**
 * TDataRenderer class
 *
 * TDataRenderer is the convenient base class for template-based renderer controls.
 * It extends {@link TTemplateControl} and implements the methods required
 * by the {@link \Prado\IDataRenderer} interface.
 *
 * The following property is provided by TDataRenderer:
 * - {@link getData Data}: data associated with this renderer.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.1.2
 */
abstract class TDataRenderer extends TTemplateControl implements \Prado\IDataRenderer
{
	/**
	 * @var mixed data associated with this renderer
	 */
	private $_data;

	/**
	 * @return mixed data associated with the item
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed $value data to be associated with the item
	 */
	public function setData($value)
	{
		$this->_data = $value;
	}
}
