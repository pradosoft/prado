<?php

/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

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
 * State without HMAC validation is client controlled, so it is restored with
 * `unserialize()` and `allowed_classes` false: objects in it are not instantiated.
 * igbinary has no class filter, so {@see \Prado\Web\UI\TPage::getEnableStateIGBinary() EnableStateIGBinary}
 * applies only to validated state.  A page that disables validation either keeps
 * objects out of its state or uses a server-side persister such as
 * {@see \Prado\Web\UI\TSessionPageStatePersister}.
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
		$str = static::getUseIGBinary($page) ? igbinary_serialize($data) : serialize($data);
		if ($page->getEnableStateValidation()) {
			$str = $sm->hashData($str);
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
	 * Restores the state data from the string produced by {@see self::serialize()}.
	 * A missing or empty state is treated as a corrupted state.
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
			$str = $sm->decrypt($str);
		}
		if ($page->getEnableStateCompression() && extension_loaded('zlib')) {
			$str = @gzuncompress($str);
		}

		if (!$page->getEnableStateValidation()) {
			return unserialize($str, ['allowed_classes' => false]);
		}
		if (($str = $sm->validateData($str)) === false) {
			return null;
		}
		return static::getUseIGBinary($page) ? igbinary_unserialize($str) : unserialize($str);
	}

	/**
	 * Whether the state is serialized with igbinary.  Only validated state uses igbinary,
	 * since `igbinary_unserialize()` cannot exclude classes from client-controlled state.
	 * @param TPage $page the page the state belongs to.
	 * @return bool whether igbinary serializes the page state.
	 * @since 4.3.3
	 */
	protected static function getUseIGBinary($page): bool
	{
		return $page->getEnableStateValidation() && $page->getEnableStateIGBinary() && extension_loaded('igbinary');
	}
}
