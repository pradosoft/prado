<?php

/**
 * MessageSource_PHP class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.0
 * @package Prado\I18N\core
 */

namespace Prado\I18N\core;

/**
 * Get the MessageSource class file.
 */
use Prado\Exceptions\TException;
use Prado\Exceptions\TIOException;

/**
 * MessageSource_PHP class.
 *
 * Using PHP arrays as the message source for translation.
 *
 * See the MessageSource::factory() method to instantiate this class.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @since 4.0
 * @package Prado\I18N\core
 */
class MessageSource_PHP extends MessageSource
{
	/**
	 * Message data filename extension.
	 * @var string
	 */
	protected $dataExt = '.php';

	/**
	 * Separator between culture name and source.
	 * @var string
	 */
	protected $dataSeparator = '.';

	/**
	 * Constructor.
	 * @param string $source the directory where the messages are stored.
	 * @see MessageSource::factory();
	 */
	public function __construct($source)
	{
		$this->source = (string) $source;
	}

	/**
	 * Load the messages from a PHP file.
	 * @param string $filename PHP file.
	 * @return array of messages.
	 */
	protected function &loadData($filename)
	{
		//load it.
		if (false === ($php = include($filename))) {
			return false;
		}

		$translationUnit = $php['trans-unit'];

		$translations = [];

		foreach ($translationUnit as $k => $unit) {
			$source = (string) $unit['source'];
			$translations[$source][] = (string) $unit['target'];
			$translations[$source][] = (string) $k;
			$translations[$source][] = array_key_exists('note', $unit) ? (string) $unit['note'] : '';
		}

		return $translations;
	}

	/**
	 * Get the last modified unix-time for this particular catalogue+variant.
	 * Just use the file modified time.
	 * @param string $source catalogue+variant
	 * @return int last modified in unix-time format.
	 */
	protected function getLastModified($source)
	{
		return is_file($source) ? filemtime($source) : 0;
	}

	/**
	 * Get the PHP file for a specific message catalogue and cultural
	 * variant.
	 * @param string $variant message catalogue
	 * @return string full path to the PHP file.
	 */
	protected function getSource($variant)
	{
		return $this->source . '/' . $variant;
	}

	/**
	 * Determin if the PHP file source is valid.
	 * @param string $source PHP file
	 * @return bool true if valid, false otherwise.
	 */
	protected function isValidSource($source)
	{
		return is_file($source);
	}

	/**
	 * Get all the variants of a particular catalogue.
	 * @param string $catalogue catalogue name
	 * @return array list of all variants for this catalogue.
	 */
	protected function getCatalogueList($catalogue)
	{
		$variants = explode('_', $this->culture);
		$source = $catalogue . $this->dataExt;
		$catalogues = [$source];
		$variant = null;

		for ($i = 0, $k = count($variants); $i < $k; ++$i) {
			if (isset($variants[$i][0])) {
				$variant .= ($variant) ? '_' . $variants[$i] : $variants[$i];
				$catalogues[] = $catalogue . $this->dataSeparator . $variant . $this->dataExt;
			}
		}

		$byDir = $this->getCatalogueByDir($catalogue);
		return array_merge($byDir, array_reverse($catalogues));
	}

	/**
	 * Traverse through the directory structure to find the catalogues.
	 * This should only be called by getCatalogueList()
	 * @param string $catalogue a particular catalogue.
	 * @return array a list of catalogues.
	 * @see getCatalogueList()
	 */
	private function getCatalogueByDir($catalogue)
	{
		$variants = explode('_', $this->culture);
		$catalogues = [];
		$variant = null;

		for ($i = 0, $k = count($variants); $i < $k; ++$i) {
			if (isset($variants[$i][0])) {
				$variant .= ($variant) ? '_' . $variants[$i] : $variants[$i];
				$catalogues[] = $variant . '/' . $catalogue . $this->dataExt;
			}
		}

		return array_reverse($catalogues);
	}

	/**
	 * Returns a list of catalogue and its culture ID.
	 * E.g. array('messages','en_AU')
	 * @return array list of catalogues
	 * @see getCatalogues()
	 */
	public function catalogues()
	{
		return $this->getCatalogues();
	}

	/**
	 * Returns a list of catalogue and its culture ID. This takes care
	 * of directory structures.
	 * E.g. array('messages','en_AU')
	 * @param null|mixed $dir
	 * @param null|mixed $variant
	 * @return array list of catalogues
	 */
	protected function getCatalogues($dir = null, $variant = null)
	{
		$dir = $dir ? $dir : $this->source;
		$files = scandir($dir);
		$catalogue = [];

		foreach ($files as $file) {
			if (is_dir($dir . '/' . $file) && preg_match('/^[a-z]{2}(_[A-Z]{2,3})?$/', $file)) {
				$catalogue = array_merge(
					$catalogue,
					$this->getCatalogues($dir . '/' . $file, $file)
				);
			}

			$pos = strpos($file, $this->dataExt);
			if ($pos > 0 && substr($file, -1 * strlen($this->dataExt)) == $this->dataExt) {
				$name = substr($file, 0, $pos);
				$dot = strrpos($name, $this->dataSeparator);
				$culture = $variant;
				$cat = $name;

				if (is_int($dot)) {
					$culture = substr($name, $dot + 1, strlen($name));
					$cat = substr($name, 0, $dot);
				}

				$details[0] = $cat;
				$details[1] = $culture;
				$catalogue[] = $details;
			}
		}
		sort($catalogue);
		return $catalogue;
	}

	/**
	 * Get the variant for a catalogue depending on the current culture.
	 * @param string $catalogue
	 * @return string the variant.
	 * @see save()
	 * @see update()
	 * @see delete()
	 */
	private function getVariants($catalogue = 'messages')
	{
		if ($catalogue === null) {
			$catalogue = 'messages';
		}

		foreach ($this->getCatalogueList($catalogue) as $variant) {
			$file = $this->getSource($variant);
			if (is_file($file)) {
				return [$variant, $file];
			}
		}
		return false;
	}

	/**
	 * @param string $php included php file
	 * @param string $filename destination file
	 * @param string $variant catalogue variant
	 */
	protected function internalSaveFile($php, $filename, $variant)
	{
		$php['info']['date'] = @date('Y-m-d\TH:i:s\Z');

		//save it and clear the cache for this variant
		if (false === file_put_contents($filename, "<?php\nreturn " . var_export($php, true) . ';')) {
			return false;
		}

		if (!empty($this->cache)) {
			$this->cache->clean($variant, $this->culture);
		}

		return true;
	}

	/**
	 * Save the list of untranslated blocks to the translation source.
	 * If the translation was not found, you should add those
	 * strings to the translation source via the <b>append()</b> method.
	 * @param string $catalogue the catalogue to add to
	 * @return bool true if saved successfuly, false otherwise.
	 */
	public function save($catalogue = 'messages')
	{
		$messages = $this->untranslated;
		if (count($messages) <= 0) {
			return false;
		}

		$variants = $this->getVariants($catalogue);

		if ($variants) {
			[$variant, $filename] = $variants;
		} else {
			[$variant, $filename] = $this->createMessageTemplate($catalogue);
		}

		if (is_writable($filename) == false) {
			throw new TIOException("Unable to save to file {$filename}, file must be writable.");
		}

		//import the existing php file
		$php = include($filename);

		//for each message add it to the XML file using DOM
		foreach ($messages as $message) {
			$php['trans-unit'][] = [
				'source' => $message,
				'target' => '',
			];
		}

		return $this->internalSaveFile($php, $filename, $variant);
	}

	/**
	 * Update the translation.
	 * @param string $text the source string.
	 * @param string $target the new translation string.
	 * @param string $comments comments
	 * @param string $catalogue the catalogue to save to.
	 * @return bool true if translation was updated, false otherwise.
	 */
	public function update($text, $target, $comments, $catalogue = 'messages')
	{
		$variants = $this->getVariants($catalogue);

		if ($variants) {
			[$variant, $filename] = $variants;
		} else {
			return false;
		}

		if (is_writable($filename) == false) {
			throw new TIOException("Unable to update file {$filename}, file must be writable.");
		}

		//import the existing php file
		$php = include($filename);

		//for each of the existin units
		foreach ($php['trans-unit'] as $k => $unit) {
			if ($unit['source'] == $text) {
				$php['trans-unit'][$k]['target'] = $target;
				if (!empty($comments)) {
					$php['trans-unit'][$k]['note'] = $comments;
				}

				break;
			}
		}

		return $this->internalSaveFile($php, $filename, $variant);
	}

	/**
	 * Delete a particular message from the specified catalogue.
	 * @param string $message the source message to delete.
	 * @param string $catalogue the catalogue to delete from.
	 * @return bool true if deleted, false otherwise.
	 */
	public function delete($message, $catalogue = 'messages')
	{
		$variants = $this->getVariants($catalogue);
		if ($variants) {
			[$variant, $filename] = $variants;
		} else {
			return false;
		}

		if (is_writable($filename) == false) {
			throw new TIOException("Unable to modify file {$filename}, file must be writable.");
		}

		//import the existing php file
		$php = include($filename);

		//for each of the existin units
		foreach ($php['trans-unit'] as $k => $unit) {
			if ($unit['source'] == $message) {
				unset($php['trans-unit'][$k]);
				return $this->internalSaveFile($php, $filename, $variant);
			}
		}

		return false;
	}

	/**
	 * @param string $catalogue the catalogue name, defaults to "messages"
	 */
	protected function createMessageTemplate($catalogue)
	{
		if ($catalogue === null) {
			$catalogue = 'messages';
		}

		$variants = $this->getCatalogueList($catalogue);
		$variant = array_shift($variants);
		$file = $this->getSource($variant);
		$dir = dirname($file);

		if (!is_dir($dir)) {
			@mkdir($dir);
			@chmod($dir, PRADO_CHMOD);
		}

		if (!is_dir($dir)) {
			throw new TException("Unable to create directory $dir");
		}

		file_put_contents($file, $this->getTemplate($catalogue));
		chmod($file, PRADO_CHMOD);

		return [$variant, $file];
	}

	protected function getTemplate($catalogue)
	{
		$date = @date('Y-m-d\TH:i:s\Z');
		$php = <<<EOD
<?php
return array(
	'info' => array(
		'source-language' => 'EN',
		'target-language' => '{$this->culture}',
		'original' => '$catalogue',
		'date' => '$date'
	),
	'trans-unit' => array(
	)
);
EOD;
		return $php;
	}
}
