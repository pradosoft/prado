<?php
/**
 * TDiscriminator and TSubMap classes file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Configuration;

/**
 * TSubMap class defines a submapping value and the corresponding <resultMap>
 *
 * The {@see Value setValue()} property is used for comparison with the
 * discriminator column value. When the {@see Value setValue()} matches
 * that of the discriminator column value, the corresponding {@see ResultMapping setResultMapping}
 * is used inplace of the current result map.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSubMap extends \Prado\TComponent
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
	 * @param string $value value for comparison with discriminator column value.
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
	 * @param string $value ID of a result map
	 */
	public function setResultMapping($value)
	{
		$this->_resultMapping = $value;
	}
}
