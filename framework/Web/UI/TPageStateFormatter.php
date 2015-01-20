<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */


/**
 * TPageStateFormatter class.
 *
 * TPageStateFormatter is a utility class to transform the page state
 * into and from a string that can be properly saved in persistent storage.
 *
 * Depending on the {@link TPage::getEnableStateValidation() EnableStateValidation}
 * and {@link TPage::getEnableStateEncryption() EnableStateEncryption},
 * TPageStateFormatter may do HMAC validation and encryption to prevent
 * the state data from being tampered or viewed.
 * The private keys and hashing/encryption methods are determined by
 * {@link TApplication::getSecurityManager() SecurityManager}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.1
 */
class TPageStateFormatter
{
	/**
	 * @param TPage
	 * @param mixed state data
	 * @return string serialized data
	 */
	public static function serialize($page,$data)
	{
		$sm=$page->getApplication()->getSecurityManager();
		if($page->getEnableStateValidation())
			$str=$sm->hashData(serialize($data));
		else
			$str=serialize($data);
		if($page->getEnableStateCompression() && extension_loaded('zlib'))
			$str=gzcompress($str);
		if($page->getEnableStateEncryption())
			$str=$sm->encrypt($str);
		return base64_encode($str);
	}

	/**
	 * @param TPage
	 * @param string serialized data
	 * @return mixed unserialized state data, null if data is corrupted
	 */
	public static function unserialize($page,$data)
	{
		$str=base64_decode($data);
		if($str==='')
			return null;
		if($str!==false)
		{
			$sm=$page->getApplication()->getSecurityManager();
			if($page->getEnableStateEncryption())
				$str=$sm->decrypt($str);
			if($page->getEnableStateCompression() && extension_loaded('zlib'))
				$str=@gzuncompress($str);
			if($page->getEnableStateValidation())
			{
				if(($str=$sm->validateData($str))!==false)
					return unserialize($str);
			}
			else
				return unserialize($str);
		}
		return null;
	}
}