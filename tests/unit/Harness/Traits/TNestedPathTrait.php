<?php

/**
 * TNestedPathTrait class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use Prado\Test\Unit\Harness\TNestedPathObject;

/**
 * TNestedPathTrait gives a class a 'Cfg' property whose setter replaces the
 * nested object outright, the way configuration-style setters do.
 *
 * A source that applies 'Cfg' after 'Cfg.Size' loses the nested write, so a
 * class using this trait reports whether that source orders a parent path
 * before the paths nested beneath it. Mix it into whatever base class the
 * source under test requires, such as a TLogRoute or a TUrlMappingPattern.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TNestedPathTrait
{
	private $_cfg;

	/**
	 * @return TNestedPathObject the nested object, created on first access.
	 */
	public function getCfg()
	{
		return $this->_cfg ??= new TNestedPathObject();
	}

	/**
	 * Replaces the nested object, discarding anything written through it.
	 * @param string $value the value recorded on the replacement object.
	 */
	public function setCfg($value)
	{
		$this->_cfg = new TNestedPathObject();
		$this->_cfg->setSize('parent:' . $value);
	}
}
