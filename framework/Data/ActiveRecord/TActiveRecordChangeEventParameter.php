<?php
/**
 * TActiveRecordChangeEventParameter class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord;

use Prado\TPropertyValue;

/**
 * TActiveRecordChangeEventParameter class
 *
 * TActiveRecordChangeEventParameter encapsulates the parameter data for
 * ActiveRecord change commit events that are broadcasted. The following change events
 * may be raise: {@see \Prado\Data\ActiveRecord\TActiveRecord::OnInsert}, {@see \Prado\Data\ActiveRecord\TActiveRecord::OnUpdate} and
 * {@see \Prado\Data\ActiveRecord\TActiveRecord::OnDelete}. The {@see setIsValid IsValid} parameter can
 * be set to false to prevent the requested change event to be performed.
 *
 * @author Wei Zhuo<weizhuo@gmail.com>
 * @since 3.1.2
 */
class TActiveRecordChangeEventParameter extends \Prado\TEventParameter
{
	private $_isValid = true;

	/**
	 * @return bool whether the event should be performed.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}

	/**
	 * @param bool $value set to false to prevent the event.
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}
}
