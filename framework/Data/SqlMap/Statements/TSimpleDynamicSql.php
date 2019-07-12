<?php
/**
 * TSimpleDynamicSql class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\SqlMap\Configuration\TSimpleDynamicParser;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;

/**
 * TSimpleDynamicSql class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TSimpleDynamicSql extends TStaticSql
{
	private $_mappings = [];

	public function __construct($mappings)
	{
		$this->_mappings = $mappings;
	}

	public function replaceDynamicParameter($sql, $parameter)
	{
		foreach ($this->_mappings as $property) {
			$value = TPropertyAccess::get($parameter, $property);
			$sql = preg_replace('/' . TSimpleDynamicParser::DYNAMIC_TOKEN . '/', str_replace('$', '\$', $value), $sql, 1);
		}
		return $sql;
	}
}
