<?php
/**
 * TXmlElement, TXmlDocument, TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Xml
 */

namespace Prado\Xml;

use Prado\Exceptions\TIOException;

/**
 * TXmlDocument class.
 *
 * TXmlDocument represents a DOM representation of an XML file.
 * Besides all properties and methods inherited from {@link TXmlElement},
 * you can load an XML file or string by {@link loadFromFile} or {@link loadFromString}.
 * You can also get the version and encoding of the XML document by
 * the Version and Encoding properties.
 *
 * To construct an XML string, you may do the following:
 * <code>
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
 * </code>
 * The above code represents the following XML string:
 * <code>
 * <?xml version="1.0" encoding="utf-8"?>
 * <Root>
 *   <Proc Name="xxxx">
 *     <Query ID="xxxx">
 *       <Attr Name="aaa">1</Attr>
 *       <Attr Name="bbb">1</Attr>
 *     </Query>
 *   </Proc>
 * </Root>
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Xml
 * @since 3.0
 */
class TXmlDocument extends TXmlElement
{
	/**
	 * @var string version of this XML document
	 */
	private $_version;
	/**
	 * @var string encoding of this XML document
	 */
	private $_encoding;

	/**
	 * Constructor.
	 * @param string $version version of this XML document
	 * @param string $encoding encoding of this XML document
	 */
	public function __construct($version = '1.0', $encoding = '')
	{
		parent::__construct('');
		$this->setVersion($version);
		$this->setEncoding($encoding);
	}

	/**
	 * @return string version of this XML document
	 */
	public function getVersion()
	{
		return $this->_version;
	}

	/**
	 * @param string $version version of this XML document
	 */
	public function setVersion($version)
	{
		$this->_version = $version;
	}

	/**
	 * @return string encoding of this XML document
	 */
	public function getEncoding()
	{
		return $this->_encoding;
	}

	/**
	 * @param string $encoding encoding of this XML document
	 */
	public function setEncoding($encoding)
	{
		$this->_encoding = $encoding;
	}

	/**
	 * Loads and parses an XML document.
	 * @param string $file the XML file path
	 * @throws TIOException if the file fails to be opened.
	 * @return bool whether the XML file is parsed successfully
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
	 * @param string $string the XML string
	 * @return bool whether the XML string is parsed successfully
	 */
	public function loadFromString($string)
	{
		// TODO: since PHP 5.1, we can get parsing errors and throw them as exception
		$doc = new \DOMDocument();
		if ($doc->loadXML($string) === false) {
			return false;
		}

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
			$bSimpleXml = (boolean) function_exists('simplexml_load_string');
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
	 * @param string $file the name of the file to be stored with XML output
	 * @throws TIOException if the file cannot be written
	 */
	public function saveToFile($file)
	{
		if (($fw = fopen($file, 'w')) !== false) {
			fwrite($fw, $this->saveToString());
			fclose($fw);
		} else {
			throw new TIOException('xmldocument_file_write_failed', $file);
		}
	}

	/**
	 * Saves this XML document as an XML string
	 * @return string the XML string of this XML document
	 */
	public function saveToString()
	{
		$version = empty($this->_version) ? ' version="1.0"' : ' version="' . $this->_version . '"';
		$encoding = empty($this->_encoding) ? '' : ' encoding="' . $this->_encoding . '"';
		return "<?xml{$version}{$encoding}?>\n" . $this->toString(0);
	}

	/**
	 * Magic-method override. Called whenever this document is used as a string.
	 * <code>
	 * $document = new TXmlDocument();
	 * $document->TagName = 'root';
	 * echo $document;
	 * </code>
	 * or
	 * <code>
	 * $document = new TXmlDocument();
	 * $document->TagName = 'root';
	 * $xml = (string)$document;
	 * </code>
	 * @return string string representation of this document
	 */
	public function __toString()
	{
		return $this->saveToString();
	}

	/**
	 * Recursively converts DOM XML nodes into TXmlElement
	 * @param DOMXmlNode $node the node to be converted
	 * @return TXmlElement the converted TXmlElement
	 */
	protected function buildElement($node)
	{
		$element = new TXmlElement($node->tagName);
		$element->setValue($node->nodeValue);
		foreach ($node->attributes as $name => $attr) {
			$element->getAttributes()->add(($attr->prefix === '' ? '' : $attr->prefix . ':') . $name, $attr->value);
		}

		foreach ($node->childNodes as $child) {
			if ($child instanceof \DOMElement) {
				$element->getElements()->add($this->buildElement($child));
			}
		}
		return $element;
	}
}
