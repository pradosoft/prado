<?php
/**
 * TActiveRecordException class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Exceptions
 */

/**
 * Base exception class for Active Records.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Exceptions
 * @since 3.1
 */
class TActiveRecordException extends TException
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

class TActiveRecordConfigurationException extends TActiveRecordException
{

}

?>