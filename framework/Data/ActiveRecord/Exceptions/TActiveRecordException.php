<?php
/**
 * TActiveRecordException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Exceptions
 */

namespace Prado\Data\ActiveRecord\Exceptions;

use Prado\Exceptions\TDbException;
use Prado\Prado;

/**
 * Base exception class for Active Records.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Exceptions
 * @since 3.1
 */
class TActiveRecordException extends TDbException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang = Prado::getPreferredLanguage();
		$path = __DIR__;
		$msgFile = $path . '/messages-' . $lang . '.txt';
		if (!is_file($msgFile)) {
			$msgFile = $path . '/messages.txt';
		}
		return $msgFile;
	}
}
