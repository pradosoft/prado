<?php

/**
 * TNestedPathObject class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness;

use Prado\TComponent;

/**
 * TNestedPathObject is the nested object reached by the 'Cfg' property of
 * {@see TNestedPathComponent} and {@see \Prado\Test\Unit\Harness\Web\UI\TNestedPathControl}.
 *
 * Its owner's setter discards it and builds a new one, which makes the order in
 * which 'Cfg' and 'Cfg.Size' are applied observable: applying 'Cfg' last erases
 * the value written through 'Cfg.Size'.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNestedPathObject extends TComponent
{
	private $_size = 'unset';

	/**
	 * @param string $value the size written through the nested path.
	 */
	public function setSize($value)
	{
		$this->_size = $value;
	}

	/**
	 * @return string the size, 'unset' until written.
	 */
	public function getSize()
	{
		return $this->_size;
	}
}
