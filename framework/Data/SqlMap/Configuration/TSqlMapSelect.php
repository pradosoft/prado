<?php

/**
 * TSqlMapStatement, TSqlMapInsert, TSqlMapUpdate, TSqlMapDelete,
 * TSqlMapSelect and TSqlMapSelectKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapSelect class file.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSqlMapSelect extends TSqlMapStatement
{
	private $_generate;

	public function getGenerate()
	{
		return $this->_generate;
	}
	public function setGenerate($value)
	{
		$this->_generate = $value;
	}
}
