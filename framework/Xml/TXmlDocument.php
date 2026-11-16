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
	public function __construct($version = '1.0', $encoding = '')
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
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * Sets the version of this XML document.
	 * @param ?string $version Version of this XML document
	 */
	public function setVersion($version)
	{
		$this->_version = $version;
	}

	/**
	 * Gets the encoding of this XML document.
	 * @return ?string Encoding of this XML document
	 */
	public function getEncoding()
	{
		return $this->_encoding;
	}

	/**
	 * Sets the encoding of this XML document.
	 * @param ?string $encoding Encoding of this XML document
	 */
	public function setEncoding($encoding)
	{
		$this->_encoding = $encoding;
	}

	/**
	 * Loads and parses an XML document from a file.
	 * @param string $file The XML file path
	 * @throws TIOException if the file fails to be opened.
	 * @return bool Whether the XML file was parsed successfully
	 */
	public function loadFromFile($file)
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
	 * A recoverable libxml error, such as an undeclared namespace prefix, does not fail the parse.
	 * Each element keeps the namespace declarations it makes as `xmlns` and `xmlns:<prefix>` attributes.
	 * @param ?string $string The XML string
	 * @return bool Whether the XML string was parsed successfully
	 */
	public function loadFromString($string)
	{
		if (empty($string)) {
			return false;
		}

		$doc = new \DOMDocument();

		$oldUseInternalErrors = libxml_use_internal_errors(true);
		if ($doc->loadXML($string) === false) {
			libxml_clear_errors();
			libxml_use_internal_errors($oldUseInternalErrors);

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
		libxml_clear_errors();
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

		foreach ($this->getNamespaceDeclarations($element) as $name => $uri) {
			$attributes->add($name, $uri);
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
	public function saveToFile($file)
	{
		if (($fw = @fopen($file, 'w')) !== false) {
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
	public function saveToString()
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
	public function __toString()
	{
		return $this->saveToString();
	}

	/**
	 * Gets the namespace declarations a DOM element makes, as attribute names and URIs.
	 * A declaration is an in-scope namespace that the parent element lacks or binds to another URI.
	 * The default namespace is named `xmlns`, and a prefixed namespace `xmlns:<prefix>`.
	 * An undeclared default namespace (`xmlns=""`) has an empty URI.
	 * @param \DOMElement $domElement The DOM element
	 * @return array<string, string> The declarations, attribute name to namespace URI
	 * @since 4.4.0
	 */
	protected function getNamespaceDeclarations(\DOMElement $domElement): array
	{
		$xpath = new \DOMXPath($domElement->ownerDocument);
		$inherited = [];
		if ($domElement->parentNode instanceof \DOMElement) {
			$inherited = $this->getInScopeNamespaces($xpath, $domElement->parentNode);
		}
		$declarations = [];
		foreach ($this->getInScopeNamespaces($xpath, $domElement) as $name => $uri) {
			if (($inherited[$name] ?? null) !== $uri) {
				$declarations[$name] = $uri;
			}
		}
		return $declarations;
	}

	/**
	 * Gets the namespaces in scope on a DOM element, as attribute names and URIs, without the `xml` namespace.
	 * libxml lists an element's own namespaces in reverse declaration order, so the list is reversed.
	 * @param \DOMXPath $xpath The XPath object of the element's document
	 * @param \DOMElement $domElement The DOM element
	 * @return array<string, string> The namespaces, attribute name to namespace URI
	 * @since 4.4.0
	 */
	private function getInScopeNamespaces(\DOMXPath $xpath, \DOMElement $domElement): array
	{
		$namespaces = [];
		foreach ($xpath->query('namespace::*', $domElement) as $namespace) {
			$prefix = $namespace->localName;
			if ($prefix !== 'xml') {
				$namespaces[$prefix === 'xmlns' ? 'xmlns' : 'xmlns:' . $prefix] = (string) $namespace->nodeValue;
			}
		}
		return array_reverse($namespaces, true);
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
		foreach ($this->getNamespaceDeclarations($domElement) as $name => $uri) {
			$element->getAttributes()->add($name, $uri);
		}
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
