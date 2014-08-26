<?php
/**
 * TActiveRecordException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Data.ActiveRecord
 */

/**
 * Base exception class for Active Records.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordException extends TDbException
{
	/**
	 * @return string path to the error message file
	 */
	protected function getErrorMessageFile()
	{
		$lang=Prado::getPreferredLanguage();
		$path = dirname(__FILE__);
		$msgFile=$path.'/messages-'.$lang.'.txt';
		if(!is_file($msgFile))
			$msgFile=$path.'/messages.txt';
		return $msgFile;
	}
}

/**
 * TActiveRecordConfigurationException class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordConfigurationException extends TActiveRecordException
{

}

