<?php

/**
 * MessageSource_gettext class file.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Qiang Xue. All rights reserved.
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N\core
 */

namespace Prado\I18N\core;

use Prado\Exceptions\TException;
use Prado\Exceptions\TIOException;
use Prado\I18N\core\Gettext\TGettext;

/**
 * Get the Gettext class.
 */
require_once(__DIR__ . '/Gettext/TGettext.php');

/**
 * MessageSource_gettext class.
 *
 * Using Gettext MO format as the message source for translation.
 * The gettext classes are based on PEAR's gettext MO and PO classes.
 *
 * See the MessageSource::factory() method to instantiate this class.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N\core
 */
class MessageSource_gettext extends MessageSource
{
	/**
	 * Message data filename extension.
	 * @var string
	 */
	protected $dataExt = '.mo';

	/**
	 * PO data filename extension
	 * @var string
	 */
	protected $poExt = '.po';

	/**
	 * Separator between culture name and source.
	 * @var string
	 */
	protected $dataSeparator = '.';

	public function __construct($source)
	{
		$this->source = (string) $source;
	}


	/**
	 * Load the messages from a MO file.
	 * @param string $filename MO file.
	 * @return array of messages.
	 */
	protected function &loadData($filename)
	{
		$mo = TGettext::factory('MO', $filename);
		$mo->load();
		$result = $mo->toArray();

		$results = [];
		$count = 0;
		foreach ($result['strings'] as $source => $target) {
			$results[$source][] = $target; //target
			$results[$source][] = $count++; //id
			$results[$source][] = ''; //comments
		}
		return $results;
	}

	/**
	 * Determin if the MO file source is valid.
	 * @param string $filename MO file
	 * @return bool true if valid, false otherwise.
	 */
	protected function isValidSource($filename)
	{
		return is_file($filename);
	}

	/**
	 * Get the MO file for a specific message catalogue and cultural
	 * vairant.
	 * @param string $variant message catalogue
	 * @return string full path to the MO file.
	 */
	protected function getSource($variant)
	{
		return $this->source . '/' . $variant;
	}

	/**
	 * Get the last modified unix-time for this particular catalogue+variant.
	 * Just use the file modified time.
	 * @param string $source catalogue+variant
	 * @return int last modified in unix-time format.
	 */
	protected function getLastModified($source)
	{
		if (is_file($source)) {
			return filemtime($source);
		} else {
			return 0;
		}
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
				$catalogues[] = $catalogue . $this->dataSeparator .
								$variant . $this->dataExt;
			}
		}
		$byDir = $this->getCatalogueByDir($catalogue);
		$catalogues = array_merge($byDir, array_reverse($catalogues));
		return $catalogues;
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
	 * Get the variant for a catalogue depending on the current culture.
	 * @param string $catalogue catalogue
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
			$po = $this->getPOFile($file);
			if (is_file($file) || is_file($po)) {
				return [$variant, $file, $po];
			}
		}
		return false;
	}

	private function getPOFile($MOFile)
	{
		$filebase = substr($MOFile, 0, strlen($MOFile) - strlen($this->dataExt));
		return $filebase . $this->poExt;
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
			[$variant, $MOFile, $POFile] = $variants;
		} else {
			[$variant, $MOFile, $POFile] = $this->createMessageTemplate($catalogue);
		}

		if (is_writable($MOFile) == false) {
			throw new TIOException("Unable to save to file {$MOFile}, file must be writable.");
		}
		if (is_writable($POFile) == false) {
			throw new TIOException("Unable to save to file {$POFile}, file must be writable.");
		}

		//set the strings as untranslated.
		$strings = [];
		foreach ($messages as $message) {
			$strings[$message] = '';
		}

		//load the PO
		$po = TGettext::factory('PO', $POFile);
		$po->load();
		$result = $po->toArray();

		$existing = count($result['strings']);

		//add to strings to the existing message list
		$result['strings'] = array_merge($result['strings'], $strings);

		$new = count($result['strings']);

		if ($new > $existing) {
			//change the date 2004-12-25 12:26
			$result['meta']['PO-Revision-Date'] = @date('Y-m-d H:i:s');

			$po->fromArray($result);
			$mo = $po->toMO();
			if ($po->save() && $mo->save($MOFile)) {
				if (!empty($this->cache)) {
					$this->cache->clean($variant, $this->culture);
				}
				return true;
			} else {
				return false;
			}
		}
		return false;
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
			[$variant, $MOFile, $POFile] = $variants;
		} else {
			return false;
		}

		if (is_writable($MOFile) == false) {
			throw new TIOException("Unable to modify file {$MOFile}, file must be writable.");
		}
		if (is_writable($POFile) == false) {
			throw new TIOException("Unable to modify file {$POFile}, file must be writable.");
		}

		$po = TGettext::factory('PO', $POFile);
		$po->load();
		$result = $po->toArray();

		foreach ($result['strings'] as $string => $value) {
			if ($string == $message) {
				$result['meta']['PO-Revision-Date'] = @date('Y-m-d H:i:s');
				unset($result['strings'][$string]);

				$po->fromArray($result);
				$mo = $po->toMO();
				if ($po->save() && $mo->save($MOFile)) {
					if (!empty($this->cache)) {
						$this->cache->clean($variant, $this->culture);
					}
					return true;
				} else {
					return false;
				}
			}
		}

		return false;
	}

	/**
	 * Update the translation.
	 * @param string $text the source string.
	 * @param string $target the new translation string.
	 * @param string $comments comments
	 * @param string $catalogue the catalogue of the translation.
	 * @return bool true if translation was updated, false otherwise.
	 */
	public function update($text, $target, $comments, $catalogue = 'messages')
	{
		$variants = $this->getVariants($catalogue);
		if ($variants) {
			[$variant, $MOFile, $POFile] = $variants;
		} else {
			return false;
		}

		if (is_writable($MOFile) == false) {
			throw new TIOException("Unable to update file {$MOFile}, file must be writable.");
		}
		if (is_writable($POFile) == false) {
			throw new TIOException("Unable to update file {$POFile}, file must be writable.");
		}


		$po = TGettext::factory('PO', $POFile);
		$po->load();
		$result = $po->toArray();

		foreach ($result['strings'] as $string => $value) {
			if ($string == $text) {
				$result['strings'][$string] = $target;
				$result['meta']['PO-Revision-Date'] = @date('Y-m-d H:i:s');

				$po->fromArray($result);
				$mo = $po->toMO();

				if ($po->save() && $mo->save($MOFile)) {
					if (!empty($this->cache)) {
						$this->cache->clean($variant, $this->culture);
					}
					return true;
				} else {
					return false;
				}
			}
		}

		return false;
	}


	/**
	 * Returns a list of catalogue as key and all it variants as value.
	 * @return array list of catalogues
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
			if (is_dir($dir . '/' . $file)
				&& preg_match('/^[a-z]{2}(_[A-Z]{2,3})?$/', $file)) {
				$catalogue = array_merge(
					$catalogue,
					$this->getCatalogues($dir . '/' . $file, $file)
				);
			}

			$pos = strpos($file, $this->dataExt);

			if ($pos > 0
				&& substr($file, -1 * strlen($this->dataExt)) == $this->dataExt) {
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

	protected function createMessageTemplate($catalogue)
	{
		if ($catalogue === null) {
			$catalogue = 'messages';
		}
		$variants = $this->getCatalogueList($catalogue);
		$variant = array_shift($variants);
		$mo_file = $this->getSource($variant);
		$po_file = $this->getPOFile($mo_file);

		$dir = dirname($mo_file);
		if (!is_dir($dir)) {
			@mkdir($dir);
			@chmod($dir, PRADO_CHMOD);
		}
		if (!is_dir($dir)) {
			throw new TException("Unable to create directory $dir");
		}

		$po = TGettext::factory('PO', $po_file);
		$result['meta']['PO-Revision-Date'] = @date('Y-m-d H:i:s');
		$result['strings'] = [];

		$po->fromArray($result);
		$mo = $po->toMO();
		if ($po->save() && $mo->save($mo_file)) {
			return [$variant, $mo_file, $po_file];
		} else {
			throw new TException("Unable to create file $po_file and $mo_file");
		}
	}
}
