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
 * The page's {@see \Prado\Web\UI\TPage::getStateCompression() StateCompression} compresses
 * the state, so an application selects a codec such as `zstd` or `br` where the extension
 * is installed.
 * The coding carries no marker in the state, so the state is read back under the coding
 * that wrote it: the setting names the coding for both directions, and its length
 * threshold plays no part.
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
	 * Compresses the state under the page's state content coding. The state is returned
	 * unchanged when compression is off or the coding's codec cannot run here.  The
	 * length threshold plays no part: a state carries no marker naming its coding, so a
	 * state that skipped compression could not be told from one that did not.
	 * @param TPage $page the page the state belongs to.
	 * @param string $str the uncompressed state.
	 * @return string the state, compressed when the settings ask for it.
	 * @since 4.4.0
	 */
	protected static function compress($page, string $str): string
	{
		$compression = $page->getStateCompression();
		if (!$compression->getShouldCompress()) {
			return $str;
		}
		return $compression->compress($str);
	}

	/**
	 * Decompresses the state under the page's state content coding, the inverse of
	 * {@see compress()}. A state that is corrupt, or written under another coding,
	 * fails to decode and is reported as corrupted.
	 * @param TPage $page the page the state belongs to.
	 * @param string $str the compressed state.
	 * @return false|string the decompressed state, or false when it cannot be decoded.
	 * @since 4.4.0
	 */
	protected static function decompress($page, string $str): false|string
	{
		$compression = $page->getStateCompression();
		if (!$compression->getShouldCompress()) {
			return $str;
		}
		try {
			return $compression->decompress($str);
		} catch (TIOException $e) {
			return false;
		}
	}
}
