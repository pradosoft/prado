<?php

/**
 * TXmlDocument class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Xml;

use Prado\Exceptions\TIOException;

/**
 * TXmlDocument class.
 *
 * TXmlDocument represents a DOM representation of an XML file.
 * Besides all properties and methods inherited from {@see \Prado\Xml\TXmlElement},
 * you can load an XML file or string by {@see loadFromFile} or {@see loadFromString}.
 * You can also get the version and encoding of the XML document by
 * the Version and Encoding properties.
 *
 * This class implements important DOM properties and methods for better compatibility
 * with standard DOM access.
 *
 * To construct an XML string, you may do the following:
 * ```php
 * $doc=new TXmlDocument('1.0','utf-8');
 * $doc->TagName='Root';
 *
 * $proc=new TXmlElement('Proc');
 * $proc->setAttribute('Name','xxxx');
 * $doc->Elements[]=$proc;
 *
 * $query=new TXmlElement('Query');
 * $query->setAttribute('ID','xxxx');
 * $proc->Elements[]=$query;
 *
 * $attr=new TXmlElement('Attr');
 * $attr->setAttribute('Name','aaa');
 * $attr->Value='1';
 * $query->Elements[]=$attr;
 *
 * $attr=new TXmlElement('Attr');
 * $attr->setAttribute('Name','bbb');
 * $attr->Value='1';
 * $query->Elements[]=$attr;
 * ```
 * The above code represents the following XML string:
 * ```xml
 * <?xml version="1.0" encoding="utf-8"?>
 * <Root>
 *   <Proc Name="xxxx">
 *     <Query ID="xxxx">
 *       <Attr Name="aaa">1</Attr>
 *       <Attr Name="bbb">1</Attr>
 *     </Query>
 *   </Proc>
 * </Root>
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TXmlDocument extends TXmlElement
{
	/**
	 * @var string version of this XML document
	 */
	private ?string $_version = null;

	/**
	 * @var string encoding of this XML document
	 */
	private ?string $_encoding = null;

	/**
	 * @var bool is the instance loaded
	 */
	private bool $_loaded = false;

	/**
	 * Constructor.
	 * Initializes a new XML document with the specified version and encoding.
	 * @param ?string $version Version of this XML document
	 * @param ?string $encoding Encoding of this XML document
	 */
	public function __construct(?string $version = '1.0', ?string $encoding = '')
	{
		$this->setVersion($version);
		$this->setEncoding($encoding);
		parent::__construct('');

		$this->_loaded = true;
	}

	/**
	 * Validates the tag name for this document.
	 * @return bool Whether the tag name is valid
	 */
	protected function validateTagName(): bool
	{
		return $this->_loaded;
	}

	/**
	 * Gets the version of this XML document.
	 * @return ?string Version of this XML document
	 */
	public function getVersion(): ?string
	{
		return $this->_version;
	}

	/**
	 * Sets the version of this XML document.
	 * @param ?string $version Version of this XML document
	 */
	public function setVersion(?string $version): void
	{
		$this->_version = $version;
	}

	/**
	 * Gets the encoding of this XML document.
	 * @return ?string Encoding of this XML document
	 */
	public function getEncoding(): ?string
	{
		return $this->_encoding;
	}

	/**
	 * Sets the encoding of this XML document.
	 * @param ?string $encoding Encoding of this XML document
	 */
	public function setEncoding(?string $encoding): void
	{
		$this->_encoding = $encoding;
	}

	/**
	 * Loads and parses an XML document from a file.
	 * @param string $file The XML file path
	 * @throws TIOException if the file fails to be opened.
	 * @return bool Whether the XML file was parsed successfully
	 */
	public function loadFromFile(string $file): bool
	{
		if (($str = @file_get_contents($file)) !== false) {
			return $this->loadFromString($str);
		} else {
			throw new TIOException('xmldocument_file_read_failed', $file);
		}
	}

	/**
	 * Loads and parses an XML string.
	 * The version and encoding will be determined based on the parsing result.
	 * @param ?string $string The XML string
	 * @return bool Whether the XML string was parsed successfully
	 */
	public function loadFromString(?string $string): bool
	{
		if (empty($string)) {
			return false;
		}

		$doc = new \DOMDocument();

		$oldUseInternalErrors = libxml_use_internal_errors(true);
		if ($doc->loadXML($string) === false) {
			$errors = libxml_get_errors();
			libxml_use_internal_errors($oldUseInternalErrors);
			// @todo throw Errors as Exceptions? or is returning false good enough?

			// Reset
			$this->_loaded = false;

			$this->setVersion('1.0');
			$this->setEncoding('');
			$this->setTagName('');
			$this->setValue('');

			$elements = $this->getElements();
			$attributes = $this->getAttributes();
			$elements->clear();
			$attributes->clear();

			$this->_loaded = true;

			return false;
		}
		libxml_use_internal_errors($oldUseInternalErrors);

		$this->setEncoding($doc->encoding);
		$this->setVersion($doc->xmlVersion);

		$element = $doc->documentElement;
		$this->setTagName($element->tagName);
		$this->setValue($element->nodeValue);
		$elements = $this->getElements();
		$attributes = $this->getAttributes();
		$elements->clear();
		$attributes->clear();

		static $bSimpleXml;
		if ($bSimpleXml === null) {
			$bSimpleXml = (bool) function_exists('simplexml_load_string');
		}

		if ($bSimpleXml) {
			$simpleDoc = simplexml_load_string($string);
			$docNamespaces = $simpleDoc->getDocNamespaces(false);
			$simpleDoc = null;
			foreach ($docNamespaces as $prefix => $uri) {
				if ($prefix === '') {
					$attributes->add('xmlns', $uri);
				} else {
					$attributes->add('xmlns:' . $prefix, $uri);
				}
			}
		}

		foreach ($element->attributes as $name => $attr) {
			$attributes->add(($attr->prefix === '' ? '' : $attr->prefix . ':') . $name, $attr->value);
		}
		foreach ($element->childNodes as $child) {
			if ($child instanceof \DOMElement) {
				$elements->add($this->buildElement($child));
			}
		}

		return true;
	}

	/**
	 * Saves this XML document as an XML file.
	 * @param string $file The name of the file to be stored with XML output
	 * @throws TIOException if the file cannot be written
	 */
	public function saveToFile(string $file): void
	{
		if (($fw = fopen($file, 'w')) !== false) {
			fwrite($fw, $this->saveToString());
			fclose($fw);
		} else {
			throw new TIOException('xmldocument_file_write_failed', $file);
		}
	}

	/**
	 * Saves this XML document as an XML string.
	 * @return string The XML string of this XML document
	 */
	public function saveToString(): string
	{
		$version = empty($this->_version) ? ' version="1.0"' : ' version="' . $this->_version . '"';
		$encoding = empty($this->_encoding) ? '' : ' encoding="' . $this->_encoding . '"';
		return "<?xml{$version}{$encoding}?>\n" . $this->toString(0);
	}

	/**
	 * Magic-method override. Called whenever this document is used as a string.
	 * ```php
	 * $document = new TXmlDocument();
	 * $document->TagName = 'root';
	 * echo $document;
	 * ```
	 * or
	 * ```php
	 * $document = new TXmlDocument();
	 * $document->TagName = 'root';
	 * $xml = (string)$document;
	 * ```
	 * @return string String representation of this document
	 */
	public function __toString(): string
	{
		return $this->saveToString();
	}

	/**
	 * Recursively converts DOM XML Element into TXmlElement.
	 * @param \DOMElement $domElement The DOM element to convert
	 * @return TXmlElement The converted TXmlElement
	 */
	protected function buildElement(\DOMElement $domElement): TXmlElement
	{
		$element = new TXmlElement($domElement->tagName);
		$element->setValue($domElement->nodeValue);
		foreach ($domElement->attributes as $name => $attr) {
			$element->getAttributes()->add(($attr->prefix === '' ? '' : $attr->prefix . ':') . $name, $attr->value);
		}

		foreach ($domElement->childNodes as $child) {
			if ($child instanceof \DOMElement) {
				$element->getElements()->add($this->buildElement($child));
			}
		}
		return $element;
	}


	// From \DOMNode

	/**
	 * Gets the node type of this document.
	 * This method mimics the DOMNode::nodeType property.
	 * @return int The type of this element (XML_DOCUMENT_NODE)
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getNodeType(): int
	{
		return XML_DOCUMENT_NODE;
	}
}
