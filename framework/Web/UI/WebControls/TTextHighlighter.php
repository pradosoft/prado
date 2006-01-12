<?php
/**
 * TTextHighlighter class file
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 Wei Zhuo
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Using GeSHi and TTextWriter classes
 */
Prado::using('System.Web.UI.WebControls.Highlighter.geshi');
Prado::using('System.IO.TTextWriter');

/**
 * TTextHighlighter class.
 *
 * TTextHighlighter does syntax highlighting its body content, including
 * static text and rendering results of child controls.
 * You can set {@link setLanguage Language} to specify what kind of syntax
 * the body content is. Currently, TTextHighlighter supports the following
 * languages: 'php','prado','css','html','javascript' and 'xml', where 'prado'
 * refers to PRADO template syntax. By setting {@link setShowLineNumbers ShowLineNumbers}
 * to true, the highlighted result may be shown with line numbers.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTextHighlighter extends TWebControl
{
	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return string language whose syntax is to be used for highlighting. Defaults to 'php'.
	 */
	public function getLanguage()
	{
		return $this->getViewState('Language', 'php');
	}

	/**
	 * @param string language whose syntax is to be used for highlighting.
	 * Valid values include 'php','prado','css','html','javascript','xml'.
	 */
	public function setLanguage($value)
	{
		$this->setViewState('Language', TPropertyValue::ensureEnum($value,'php','prado','css','html','javascript','xml'), 'php');
	}

	/**
	 * @return boolean whether to show line numbers in the highlighted result.
	 */
	public function getShowLineNumbers()
	{
		return $this->getViewState('ShowLineNumbers', false);
	}

	/**
	 * @param boolean whether to show line numbers in the highlighted result.
	 */
	public function setShowLineNumbers($value)
	{
		$this->setViewState('ShowLineNumbers', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Registers css style for the highlighted result.
	 * This method overrides parent implementation.
	 * @param THtmlWriter writer
	 */
	protected function onPreRender($writer)
	{
		parent::onPreRender($writer);
		$this->registerHighlightStyleSheet();
	}

	/**
	 * HTML-decodes static text.
	 * This method overrides parent implementation.
	 * @param mixed object to be added as body content
	 */
	public function addParsedObject($object)
	{
		if(is_string($object))
			$object=html_entity_decode($object);
		parent::addParsedObject($object);
	}

	/**
	 * Renders body content.
	 * This method overrides parent implementation by replacing
	 * the body content with syntax highlighted result.
	 * @param THtmlWriter writer
	 */
	protected function renderContents($writer)
	{
		$textWriter=new TTextWriter;
		parent::renderContents(new THtmlWriter($textWriter));
		$writer->write($this->highlightText($textWriter->flush()));
	}

	/**
	 * Register CSS style sheet file.
	 */
	protected function registerHighlightStyleSheet()
	{
		$cs = $this->getPage()->getClientScript();
		$cssKey='prado:TTextHighlighter';
		if(!$cs->isStyleSheetFileRegistered($cssKey))
		{
			$styleSheet = $this->getAsset('Highlighter/code_highlight.css');
			$cs->registerStyleSheetFile($cssKey, $styleSheet);
		}
	}

	/**
	 * Returns the highlighted text.
	 * @param string text to highlight.
	 * @return string highlighted text.
	 */
	protected function highlightText($text)
	{
		$geshi = new GeSHi(trim($text), $this->getLanguage());
		if($this->getShowLineNumbers())
			$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
		$geshi->enable_classes();
		return $geshi->parse_code();
	}
}
?>