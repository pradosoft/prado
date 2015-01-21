<?php
/**
 * TDiscriminator and TSubMap classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Data\SqlMap\Configuration
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSubMap class defines a submapping value and the corresponding <resultMap>
 *
 * The {@link Value setValue()} property is used for comparison with the
 * discriminator column value. When the {@link Value setValue()} matches
 * that of the discriminator column value, the corresponding {@link ResultMapping setResultMapping}
 * is used inplace of the current result map.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Configuration
 * @since 3.1
 */
class TSubMap extends TComponent
{
	private $_value;
	private $_resultMapping;

	/**
	 * @return string value for comparison with discriminator column value.
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @param string value for comparison with discriminator column value.
	 */
	public function setValue($value)
	{
		$this->_value = $value;
	}

	/**
	 * The result map to use when the Value matches the discriminator column value.
	 * @return string ID of a result map
	 */
	public function getResultMapping()
	{
		return $this->_resultMapping;
	}

	/**
	 * @param string ID of a result map
	 */
	public function setResultMapping($value)
	{
		$this->_resultMapping = $value;
	}
}