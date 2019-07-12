<?php
/**
 * TTextProcessor class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\IO\TTextWriter;

/**
 * TTextProcessor class.
 *
 * TTextProcessor is the base class for classes that process or transform
 * text content into different forms. The text content to be processed
 * is specified by {@link setText Text} property. If it is not set, the body
 * content enclosed within the processor control will be processed and rendered.
 * The body content includes static text strings and the rendering result
 * of child controls.
 *
 * Note, all child classes must implement {@link processText} method.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0.1
 */
abstract class TTextProcessor extends \Prado\Web\UI\WebControls\TWebControl
{
	/**
	 * Processes a text string.
	 * This method must be implemented by child classes.
	 * @param string $text text string to be processed
	 * @return string the processed text result
	 */
	abstract public function processText($text);

	/**
	 * HTML-decodes static text.
	 * This method overrides parent implementation.
	 * @param mixed $object object to be added as body content
	 */
	public function addParsedObject($object)
	{
		if (is_string($object)) {
			$object = html_entity_decode($object, ENT_QUOTES, 'UTF-8');
		}
		parent::addParsedObject($object);
	}

	/**
	 * @return string text to be processed
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value text to be processed
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value);
	}

	/**
	 * Renders body content.
	 * This method overrides the parent implementation by replacing
	 * the body content with the processed text content.
	 * @param THtmlWriter $writer writer
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) === '' && $this->getHasControls()) {
			$htmlWriter = Prado::createComponent($this->GetResponse()->getHtmlWriterType(), new TTextWriter());
			parent::renderContents($htmlWriter);
			$text = $htmlWriter->flush();
		}
		if ($text !== '') {
			$writer->write($this->processText($text));
		}
	}
}
