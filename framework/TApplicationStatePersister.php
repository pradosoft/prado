<?php

/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * TApplicationStatePersister class.
 * TApplicationStatePersister provides a file-based persistent storage
 * for application state. Application state, when serialized, is stored
 * in a file named 'global.cache' under the 'runtime' directory of the application.
 * Cache will be exploited if it is enabled.
 *
 * {@see save()} writes the state to a temporary file in the runtime directory and
 * renames it over 'global.cache', so a concurrent {@see load()} reads either the
 * previous state or the new state. {@see load()} treats an empty or unreadable
 * file as no state.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="state" class="Prado\TApplicationStatePersister" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'state' => [
 *             'class' => 'Prado\TApplicationStatePersister',
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TApplicationStatePersister extends \Prado\TModule implements IStatePersister
{
	/**
	 * Name of the value stored in cache
	 */
	public const CACHE_NAME = 'prado:appstate';

	/**
	 * Initializes module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration (may be null)
	 */
	public function init($config)
	{
		$this->setAppApplicationStatePersister();
		parent::init($config);
	}

	/**
	 * Registers this module as the application state persister when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setAppApplicationStatePersister()
	{
		$this->getApplication()?->setApplicationStatePersister($this);
	}

	/**
	 * @return string the file path storing the application state
	 */
	protected function getStateFilePath()
	{
		return $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'global.cache';
	}

	/**
	 * Loads application state from persistent storage.
	 * The cache takes precedence over the state file. An empty or unreadable state file is no state.
	 * @return ?mixed application state, or null when there is no state
	 */
	public function load()
	{
		if (($cache = $this->getApplication()->getCache()) !== null && ($value = $cache->get(self::CACHE_NAME)) !== false) {
			return unserialize($value);
		}
		$content = @file_get_contents($this->getStateFilePath());
		if ($content === false || $content === '') {
			return null;
		}
		return unserialize($content);
	}

	/**
	 * Saves application state in persistent storage.
	 * The state file is written only when the cache is absent or holds a different state.
	 * @param mixed $state application state
	 */
	public function save($state)
	{
		$content = serialize($state);
		if (($cache = $this->getApplication()->getCache()) !== null) {
			if ($cache->get(self::CACHE_NAME) === $content) {
				return;
			}
			$cache->set(self::CACHE_NAME, $content);
		}
		$this->writeStateFile($content);
	}

	/**
	 * Writes the serialized state to a temporary file and renames it over the state file.
	 * A reader in another process sees the old or the new content, never a partial file.
	 * The temporary file is removed when the write or the rename fails.
	 * @param string $content the serialized application state
	 * @since 4.4.0
	 */
	protected function writeStateFile(string $content): void
	{
		$fileName = $this->getStateFilePath();
		$tmpFile = $fileName . '.' . bin2hex(random_bytes(4)) . '.tmp';
		try {
			file_put_contents($tmpFile, $content);
			rename($tmpFile, $fileName);
		} finally {
			if (is_file($tmpFile)) {
				@unlink($tmpFile);
			}
		}
	}
}
