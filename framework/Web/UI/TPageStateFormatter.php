<?php

/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TIOException;
use Prado\IO\Compression\TCompression;

/**
 * TPageStateFormatter class.
 *
 * TPageStateFormatter is a utility class to transform the page state
 * into and from a string that can be properly saved in persistent storage.
 *
 * Depending on the {@see \Prado\Web\UI\TPage::getEnableStateValidation() EnableStateValidation}
 * and {@see \Prado\Web\UI\TPage::getEnableStateEncryption() EnableStateEncryption},
 * TPageStateFormatter may do HMAC validation and encryption to prevent
 * the state data from being tampered or viewed.
 * The private keys and hashing/encryption methods are determined by
 * {@see \Prado\TApplication::getSecurityManager() SecurityManager}.
 *
 * {@see \Prado\Web\UI\TPage::getEnableStateCompression() EnableStateCompression} compresses
 * the state through {@see \Prado\IO\Compression\TCompression} under the content coding of
 * {@see \Prado\Web\UI\TPage::getStateCompressionMethod() StateCompressionMethod}, so a page
 * selects a codec such as `zstd` or `br` where the extension is installed. The coding
 * carries no marker in the state, so the state is read back under the coding that wrote it.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
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
		$str = static::compress($page, $str);
		if ($page->getEnableStateEncryption()) {
			$str = $sm->encrypt($str);
		}
		return base64_encode($str);
	}

	/**
	 * Restores the state data from the string produced by {@see self::serialize()}.
	 * A missing or empty state is treated as a corrupted state, as is a state that
	 * fails to decrypt or to decompress.
	 * @param TPage $page
	 * @param ?string $data serialized data
	 * @return mixed unserialized state data, null if the data is missing or corrupted
	 */
	public static function unserialize($page, $data)
	{
		if ($data === null || $data === '') {
			return null;
		}
		$str = base64_decode($data);
		if ($str === '' || $str === false) {
			return null;
		}
		$sm = $page->getApplication()->getSecurityManager();
		if ($page->getEnableStateEncryption()) {
			if (($str = $sm->decrypt($str)) === false) {
				return null;
			}
		}
		if (($str = static::decompress($page, $str)) === false) {
			return null;
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
		return null;
	}

	/**
	 * Compresses the state under the page's content coding. The state is returned
	 * unchanged when the page disables compression or the coding's codec cannot run here.
	 * @param TPage $page the page the state belongs to.
	 * @param string $str the uncompressed state.
	 * @return string the state, compressed when the page asks for it.
	 * @since 4.4.0
	 */
	protected static function compress($page, string $str): string
	{
		$method = $page->getStateCompressionMethod();
		if (!$page->getEnableStateCompression() || !TCompression::isAvailable($method)) {
			return $str;
		}
		return TCompression::compress($str, $method);
	}

	/**
	 * Decompresses the state under the page's content coding, the inverse of
	 * {@see compress()}. A state that is corrupt, or written under another coding,
	 * fails to decode and is reported as corrupted.
	 * @param TPage $page the page the state belongs to.
	 * @param string $str the compressed state.
	 * @return false|string the decompressed state, or false when it cannot be decoded.
	 * @since 4.4.0
	 */
	protected static function decompress($page, string $str): false|string
	{
		$method = $page->getStateCompressionMethod();
		if (!$page->getEnableStateCompression() || !TCompression::isAvailable($method)) {
			return $str;
		}
		try {
			return TCompression::decompress($str, $method);
		} catch (TIOException $e) {
			return false;
		}
	}
}
