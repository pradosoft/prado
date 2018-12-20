<?php

/**
 * MessageFormat class file.
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

use Prado\Util\TUtf8Converter;

/**
 * MessageFormat class.
 *
 * Format a message, that is, for a particular message find the
 * translated message.
 * Create a new message format instance and echo "Hello"
 * in simplified Chinese. This assumes that the world "Hello"
 * is translated in the database.
 *
 * <code>
 *   // db1 must be already configured
 *  $source = MessageSource::factory('Database', 'db1');
 *	$source->setCulture('zh_CN');
 *	$source->setCache(new MessageCache('./tmp'));
 *
 * 	$formatter = new MessageFormat($source);
 *
 *	echo $formatter->format('Hello');
 * </code>
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N\core
 */
class MessageFormat
{
	/**
	 * The message source.
	 * @var MessageSource
	 */
	protected $source;

	/**
	 * A list of loaded message catalogues.
	 * @var array
	 */
	protected $catagloues = [];

	/**
	 * The translation messages.
	 * @var array
	 */
	protected $messages = [];

	/**
	 * A list of untranslated messages.
	 * @var array
	 */
	protected $untranslated = [];

	/**
	 * The prefix and suffix to append to untranslated messages.
	 * @var array
	 */
	protected $postscript = ['', ''];

	/**
	 * Set the default catalogue.
	 * @var string
	 */
	public $Catalogue;

	/**
	 * Output encoding charset
	 * @var string
	 */
	protected $charset = 'UTF-8';

	/**
	 * Constructor.
	 * Create a new instance of MessageFormat using the messages
	 * from the supplied message source.
	 * @param IMessageSource $source the source of translation messages.
	 * @param string $charset charset for the message output.
	 */
	public function __construct(IMessageSource $source, $charset = 'UTF-8')
	{
		$this->source = $source;
		$this->setCharset($charset);
	}

	/**
	 * Sets the charset for message output.
	 * @param string $charset charset, default is UTF-8
	 */
	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	/**
	 * Gets the charset for message output. Default is UTF-8.
	 * @return string charset, default UTF-8
	 */
	public function getCharset()
	{
		return $this->charset;
	}

	/**
	 * Load the message from a particular catalogue. A listed
	 * loaded catalogues is kept to prevent reload of the same
	 * catalogue. The load catalogue messages are stored
	 * in the $this->message array.
	 * @param string $catalogue message catalogue to load.
	 */
	protected function loadCatalogue($catalogue)
	{
		if (in_array($catalogue, $this->catagloues)) {
			return;
		}

		if ($this->source->load($catalogue)) {
			$this->messages[$catalogue] = $this->source->read();
			$this->catagloues[] = $catalogue;
		}
	}

	/**
	 * Format the string. That is, for a particular string find
	 * the corresponding translation. Variable subsitution is performed
	 * for the $args parameter. A different catalogue can be specified
	 * using the $catalogue parameter.
	 * The output charset is determined by $this->getCharset();
	 * @param string $string the string to translate.
	 * @param array $args a list of string to substitute.
	 * @param null|string $catalogue get the translation from a particular message
	 * @param null|string $charset charset, the input AND output charset catalogue.
	 * @return string translated string.
	 */
	public function format($string, $args = [], $catalogue = null, $charset = null)
	{
		if (empty($charset)) {
			$charset = $this->getCharset();
		}

		//force args as UTF-8
		foreach ($args as $k => $v) {
			$args[$k] = TUtf8Converter::toUTF8($v, $charset);
		}
		$s = $this->formatString(TUtf8Converter::toUTF8($string, $charset), $args, $catalogue);
		return TUtf8Converter::fromUTF8($s, $charset);
	}

	/**
	 * Do string translation.
	 * @param string $string the string to translate.
	 * @param array $args a list of string to substitute.
	 * @param null|string $catalogue get the translation from a particular message catalogue.
	 * @return string translated string.
	 */
	protected function formatString($string, $args = [], $catalogue = null)
	{
		if (empty($catalogue)) {
			if (empty($this->Catalogue)) {
				$catalogue = 'messages';
			} else {
				$catalogue = $this->Catalogue;
			}
		}

		$this->loadCatalogue($catalogue);

		if (empty($args)) {
			$args = [];
		}

		foreach ($this->messages[$catalogue] as $variant) {
			// foreach of the translation units
			foreach ($variant as $source => $result) {
				// we found it, so return the target translation
				if ($source == $string) {
					//check if it contains only strings.
					if (is_string($result)) {
						$target = $result;
					} else {
						$target = $result[0];
					}
					//found, but untranslated
					if (empty($target)) {
						return 	$this->postscript[0] .
								strtr($string, $args) .
								$this->postscript[1];
					} else {
						return strtr($target, $args);
					}
				}
			}
		}

		// well we did not find the translation string.
		$this->source->append($string);

		return 	$this->postscript[0] .
				strtr($string, $args) .
				$this->postscript[1];
	}

	/**
	 * Get the message source.
	 * @return MessageSource
	 */
	public function getSource()
	{
		return $this->source;
	}

	/**
	 * Set the prefix and suffix to append to untranslated messages.
	 * e.g. $postscript=array('[T]','[/T]'); will output
	 * "[T]Hello[/T]" if the translation for "Hello" can not be determined.
	 * @param array $postscript first element is the prefix, second element the suffix.
	 */
	public function setUntranslatedPS($postscript)
	{
		if (is_array($postscript) && count($postscript) >= 2) {
			$this->postscript[0] = $postscript[0];
			$this->postscript[1] = $postscript[1];
		}
	}
}
