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
 * Using GeSHi and TTextProcessor classes
 */
Prado::using('System.3rdParty.geshi.geshi');
Prado::using('System.Web.UI.WebControls.TTextProcessor');

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
class TTextHighlighter extends TTextProcessor
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
	 * Valid values are those file names (without suffix) that are contained
	 * in '3rdParty/geshi/geshi' (e.g. 'php','prado','css','html','javascript',
	 * 'xml'.)
	 */
	public function setLanguage($value)
	{
		$this->setViewState('Language', $value, 'php');
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
	 * @return boolean true will show "Copy Code" link. Defaults to false.
	 */
	public function getEnableCopyCode()
	{
		return $this->getViewState('CopyCode', false);
	}

	/**
	 * @param boolean true to show the "Copy Code" link.
	 */
	public function setEnableCopyCode($value)
	{
		$this->setViewState('CopyCode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Registers css style for the highlighted result.
	 * This method overrides parent implementation.
	 * @param THtmlWriter writer
	 */
	public function onPreRender($writer)
	{
		parent::onPreRender($writer);
		$this->registerHighlightScripts();
	}

	/**
	 * Register CSS stylesheet file and javascript file.
	 * @throws TConfigurationException if highlight.css file cannot be found
	 */
	protected function registerHighlightScripts()
	{
		$cs=$this->getPage()->getClientScript();
		$cssKey='prado:TTextHighlighter';
		if(!$cs->isStyleSheetFileRegistered($cssKey))
		{
			if(($cssFile=Prado::getPathOfNamespace('System.3rdParty.geshi.highlight','.css'))===null)
				throw new TConfigurationException('texthighlighter_stylesheet_invalid');
			$styleSheet = $this->publishFilePath($cssFile);
			$cs->registerStyleSheetFile($cssKey, $styleSheet);
		}
		$cs->registerPradoScript('prado');
	}

	/**
	 * Processes a text string.
	 * This method is required by the parent class.
	 * @param string text string to be processed
	 * @return string the processed text result
	 */
	public function processText($text)
	{
		$geshi = new GeSHi(trim($text), $this->getLanguage());
		if($this->getShowLineNumbers())
			$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
		$geshi->enable_classes();
		if($this->getEnableCopyCode())
			$geshi->set_header_content($this->getHeaderTemplate());

		return $geshi->parse_code();
	}

	/**
	 * @return string header template with "Copy code" link.
	 */
	protected function getHeaderTemplate()
	{
		$id = $this->getClientID();
		return TJavaScript::renderScriptBlock("new Prado.WebUI.TTextHighlighter('{$id}');");
	}
}
?>