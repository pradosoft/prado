<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

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
 * @package Prado\Web\UI
 * @since 3.1
 */
class TPageStateFormatter
{
	/**
	 * @param TPage $page
	 * @param mixed $data state data
	 * @return string serialized data
	 */
	public static function serialize($page, $data)
	{
		$sm = $page->getApplication()->getSecurityManager();
		if ($page->getEnableStateIGBinary() && extension_loaded('igbinary')) {
			if ($page->getEnableStateValidation()) {
				$str = $sm->hashData(igbinary_serialize($data));
			} else {
				$str = igbinary_serialize($data);
			}
		} else {
			if ($page->getEnableStateValidation()) {
				$str = $sm->hashData(serialize($data));
			} else {
				$str = serialize($data);
			}
		}
		if ($page->getEnableStateCompression() && extension_loaded('zlib')) {
			$str = gzcompress($str);
		}
		if ($page->getEnableStateEncryption()) {
			$str = $sm->encrypt($str);
		}
		return base64_encode($str);
	}

	/**
	 * @param TPage $page
	 * @param string $data serialized data
	 * @return mixed unserialized state data, null if data is corrupted
	 */
	public static function unserialize($page, $data)
	{
		$str = base64_decode($data);
		if ($str === '') {
			return null;
		}
		if ($str !== false) {
			$sm = $page->getApplication()->getSecurityManager();
			if ($page->getEnableStateEncryption()) {
				$str = $sm->decrypt($str);
			}
			if ($page->getEnableStateCompression() && extension_loaded('zlib')) {
				$str = @gzuncompress($str);
			}

			if ($page->getEnableStateIGBinary() && extension_loaded('igbinary')) {
				if ($page->getEnableStateValidation()) {
					if (($str = $sm->validateData($str)) !== false) {
						return igbinary_unserialize($str);
					}
				} else {
					return igbinary_unserialize($str);
				}
			} else {
				if ($page->getEnableStateValidation()) {
					if (($str = $sm->validateData($str)) !== false) {
						return unserialize($str);
					}
				} else {
					return unserialize($str);
				}
			}
		}
		return null;
	}
}
