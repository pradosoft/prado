<?php
/**
 * TSqlMapStatement, TSqlMapInsert, TSqlMapUpdate, TSqlMapDelete,
 * TSqlMapSelect and TSqlMapSelectKey classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSqlMapInsert class corresponds to the <insert> element.
 *
 * The <insert> element allows <selectKey> child elements that can be used
 * to generate a key to be used for the insert command.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSqlMapInsert extends TSqlMapStatement
{
	private $_selectKey;

	/**
	 * @return TSqlMapSelectKey select key element.
	 */
	public function getSelectKey()
	{
		return $this->_selectKey;
	}

	/**
	 * @param TSqlMapSelectKey $value select key.
	 */
	public function setSelectKey($value)
	{
		$this->_selectKey = $value;
	}
}
