<?php

/**
 * TModuleConfigurationFileTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Traits;

use Prado\TApplication;
use Prado\Xml\TXmlDocument;

/**
 * TModuleConfigurationFileTrait trait
 *
 * Reads a Prado configuration file into the shape its loader expects.  PHP
 * files are `include`'d (and validated to return an array); XML files load
 * into a {@see TXmlDocument}.  When `$type` is `null` the format is detected
 * from the file extension (`.php` → PHP, else XML).
 *
 * Used by modules that load supplemental configuration from disk
 * (e.g. {@see \Prado\Security\TUserManager}'s `UserFile`,
 * {@see \Prado\TApplicationConfiguration}'s `loadFromFile()`).  The trait
 * holds only the raw read; shape dispatch (`array` → PHP loader,
 * `TXmlDocument` → XML loader) lives on the using class:
 *
 * ```php
 * $type    = $this->getApplication()?->getConfigurationType();
 * $content = $this->readConfigurationFile($type, $fname);
 * if ($content instanceof TXmlDocument) {
 *     $this->loadFromXml($content, dirname($fname));
 * } elseif (is_array($content)) {
 *     $this->loadFromPhp($content, dirname($fname));
 * }
 * ```
 *
 * Subclasses may override {@see readConfigurationFile()} to add formats
 * (JSON, YAML, …); the shape contract is "return an array, a
 * `TXmlDocument`, or `null`."
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TModuleConfigurationFileTrait
{
	/**
	 * Reads a configuration file into the shape its loader expects.  `null`
	 * `$type` auto-detects by extension (`.php` → PHP, else XML).  Override
	 * to add formats (JSON, YAML, …).
	 * @param ?string $type {@see \Prado\TApplication::CONFIG_TYPE_PHP},
	 *   {@see \Prado\TApplication::CONFIG_TYPE_XML}, or `null` to
	 *   auto-detect.
	 * @param string $fname configuration file name.
	 * @return null|array|TXmlDocument array for the PHP-form loader,
	 *   `TXmlDocument` for the XML-form loader, `null` when the include
	 *   returned a non-array (PHP path) so the caller can skip.
	 * @since 4.4.0
	 */
	protected function readConfigurationFile(?string $type, string $fname): null|array|TXmlDocument
	{
		if ($type === null) {
			$type = strtolower(pathinfo($fname, PATHINFO_EXTENSION)) === 'php'
				? TApplication::CONFIG_TYPE_PHP
				: TApplication::CONFIG_TYPE_XML;
		}
		if ($type === TApplication::CONFIG_TYPE_PHP) {
			$content = include $fname;
			return is_array($content) ? $content : null;
		}
		$dom = new TXmlDocument();
		$dom->loadFromFile($fname);
		return $dom;
	}
}
