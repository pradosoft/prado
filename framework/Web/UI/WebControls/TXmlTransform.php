<?php

/**
 * TXmlTransform class file
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use DOMDocument;
use Prado\Collections\TAttributeCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use XSLTProcessor;

/**
 * TXmlTransform class
 *
 * TXmlTransform uses the PHP's XSL extension to perform
 * {@see http://www.w3.org/TR/xslt XSL transformations} using the
 * {@see http://xmlsoft.org/XSLT/ libxslt library}.
 *
 * To associate an XML style sheet with TXmlTransform set the
 * {@see setTransformPath TransformPath} property to the namespace or path to the style sheet
 * or set the {@see setTransformContent TransformContent} property to the XML style sheet
 * data as a string.
 *
 * To associate the XML data to be transformed set the {@see setDocumentPath DocumentPath}
 * property to the namespace or path to the XML document or set the
 * {@see setDocumentContent DocumentContent} property to the XML data as a string.
 *
 * To add additional parameters to the transformation process you can use the {@see getParameters Parameters}
 * property.
 *
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1
 */
class TXmlTransform extends \Prado\Web\UI\TControl
{
	public const EXT_XML_FILE = '.xml';
	public const EXT_XSL_FILE = '.xsl';

	/**
	 * Constructor
	 *
	 * Initializes the TXmlTransform object and ensure that the XSL extension is available
	 * @throws TConfigurationException If XSL extension is not available
	 */
	public function __construct()
	{
		if (!class_exists('XSLTProcessor', false)) {
			throw new TConfigurationException('xmltransform_xslextension_required');
		}
		parent::__construct();
	}

	/**
	 * @return string The path to the XML style sheet.
	 */
	public function getTransformPath()
	{
		return $this->getViewState('TransformPath', '');
	}

	/**
	 * @param string $value The path to the XML style sheet.  It must be in namespace format.
	 */
	public function setTransformPath($value)
	{
		if (!is_file($value)) {
			$value = Prado::getPathOfNamespace($value, self::EXT_XSL_FILE);
			if ($value === null) {
				throw new TInvalidDataValueException('xmltransform_transformpath_invalid', $value);
			}
		}
		$this->setViewState('TransformPath', $value, '');
	}

	/**
	 * @return string XML style sheet as string
	 */
	public function getTransformContent()
	{
		return $this->getViewState('TransformContent', '');
	}

	/**
	 * @param string $value XML style sheet as string
	 */
	public function setTransformContent($value)
	{
		$this->setViewState('TransformContent', $value, '');
	}

	/**
	 * @return string The path to the XML document. It must be in namespace format.
	 */
	public function getDocumentPath()
	{
		return $this->getViewState('DocumentPath', '');
	}

	/**
	 * @param string $value Namespace or path to XML document
	 * @throws TInvalidDataValueException
	 */
	public function setDocumentPath($value)
	{
		if (!is_file($value)) {
			$value = Prado::getPathOfNamespace($value, self::EXT_XML_FILE);
			if ($value === null) {
				throw new TInvalidDataValueException('xmltransform_documentpath_invalid', $value);
			}
		}
		$this->setViewState('DocumentPath', $value, '');
	}

	/**
	 * @return string XML data
	 */
	public function getDocumentContent()
	{
		return $this->getViewState('DocumentContent', '');
	}

	/**
	 * @param string $value XML data. If not empty, it takes precedence over {@see setDocumentPath DocumentPath}.
	 */
	public function setDocumentContent($value)
	{
		$this->setViewState('DocumentContent', $value, '');
	}

	/**
	 * Returns the list of parameters to be applied to the transform.
	 * @return TAttributeCollection the list of custom parameters
	 */
	public function getParameters()
	{
		if ($params = $this->getViewState('Parameters', null)) {
			return $params;
		} else {
			$params = new TAttributeCollection();
			$this->setViewState('Parameters', $params, null);
			return $params;
		}
	}

	private function getTransformXmlDocument()
	{
		if (($content = $this->getTransformContent()) !== '') {
			$document = new DOMDocument();
			$document->loadXML($content);
			return $document;
		} elseif (($path = $this->getTransformPath()) !== '') {
			$document = new DOMDocument();
			$document->load($path);
			return $document;
		} else {
			throw new TConfigurationException('xmltransform_transform_required');
		}
	}

	private function getSourceXmlDocument()
	{
		if (($content = $this->getDocumentContent()) !== '') {
			$document = new DOMDocument();
			$document->loadXML($content);
			return $document;
		} elseif (($path = $this->getDocumentPath()) !== '') {
			$document = new DOMDocument();
			$document->load($path);
			return $document;
		} else {
			return null;
		}
	}

	/**
	 * Performs XSL transformation and render the output.
	 * @param \Prado\Web\UI\THtmlWriter $writer The writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (($document = $this->getSourceXmlDocument()) === null) {
			$htmlWriter = Prado::createComponent($this->getResponse()->getHtmlWriterType(), new TTextWriter());
			parent::render($htmlWriter);
			$document = new DOMDocument();
			$document->loadXML($htmlWriter->flush());
		}
		$stylesheet = $this->getTransformXmlDocument();

		// Perform XSL transformation
		$xslt = new XSLTProcessor();
		$xslt->importStyleSheet($stylesheet);

		// Check for parameters
		$parameters = $this->getParameters();
		foreach ($parameters as $name => $value) {
			$xslt->setParameter('', $name, $value);
		}
		$output = $xslt->transformToXML($document);

		// Write output
		$writer->write($output);
	}
}
