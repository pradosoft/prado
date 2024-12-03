<?php

/**
 * TTextHighlighter class file
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Prado;
use Prado\Web\THttpUtility;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TTextHighlighter class.
 *
 * TTextHighlighter does syntax highlighting its body content, including
 * static text and rendering results of child controls.
 * You can set {@see setLanguage Language} to specify what kind of syntax
 * the body content is and {@see setSyntaxStyle SyntaxStyle} to specify the
 * style used to highlight the content.
 *
 * The list of supported syntaxes is available at https://github.com/isagalaev/highlight.js/tree/master/src/languages
 * The list of supported styles is available at https://github.com/isagalaev/highlight.js/tree/master/src/styles
 *
 * By setting {@see setShowLineNumbers ShowLineNumbers} to true, the highlighted
 * result may be shown with line numbers. To style lin numbers, use the css class "hljs-line-numbers".
 *
 * By default the contents are encoded using {@see \Prado\Web\THttpUtility::htmlEncode} before being rendered, and
 * any leading end-of-line character is removed to avoid an empty line being rendered.
 * Setting {@see setEncodeHtml EncodeHtml} to false the original content will be rendered.
 *
 * Note, TTextHighlighter requires {@see \Prado\Web\UI\WebControls\THead} to be placed on the page template
 * because it needs to insert some CSS styles.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @since 3.0
 */
class TTextHighlighter extends TTextProcessor
{
	/**
	 * @return string tag name of the panel
	 */
	protected function getTagName()
	{
		return 'pre';
	}

	/**
	 * @return string language whose syntax is to be used for highlighting. Defaults to 'php'.
	 */
	public function getLanguage()
	{
		return $this->getViewState('Language', 'php');
	}

	/**
	 * @param string $value language (case-insensitive) whose syntax is to be used for highlighting.
	 * If a language is not supported, it will be displayed as plain text.
	 */
	public function setLanguage($value)
	{
		$this->setViewState('Language', strtolower($value), 'php');
	}

	/**
	 * @return bool whether to show line numbers in the highlighted result.
	 */
	public function getShowLineNumbers()
	{
		return $this->getViewState('ShowLineNumbers', false);
	}

	/**
	 * @param bool $value whether to show line numbers in the highlighted result.
	 */
	public function setShowLineNumbers($value)
	{
		$this->setViewState('ShowLineNumbers', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool true will show "Copy Code" link. Defaults to false.
	 */
	public function getEnableCopyCode()
	{
		return $this->getViewState('CopyCode', false);
	}

	/**
	 * @param bool $value true to show the "Copy Code" link.
	 */
	public function setEnableCopyCode($value)
	{
		$this->setViewState('CopyCode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string style of syntax highlightning
	 */
	public function getSyntaxStyle()
	{
		return $this->getViewState('SyntaxStyle', 'default');
	}

	/**
	 * @param string $value style of syntax highlightning
	 */
	public function setSyntaxStyle($value)
	{
		$this->setViewState('SyntaxStyle', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * @return int tab size. Defaults to 4.
	 */
	public function getTabSize()
	{
		return $this->getViewState('TabSize', 4);
	}

	/**
	 * @param int $value tab size
	 */
	public function setTabSize($value)
	{
		$this->setViewState('TabSize', TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return bool wether the contents are html encoded. Defaults to true.
	 */
	public function getEncodeHtml()
	{
		return $this->getViewState('EncodeHtml', true);
	}

	/**
	 * @param bool $value wether to html-encode the contents using {@see \Prado\Web\THttpUtility::htmlEncode}.
	 */
	public function setEncodeHtml($value)
	{
		$this->setViewState('EncodeHtml', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Registers css style for the highlighted result.
	 * This method overrides parent implementation.
	 * @param \Prado\TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
	}

	/**
	 * Registers the stylesheet for presentation.
	 */
	protected function registerStyleSheet()
	{
		$cs = $this->getPage()->getClientScript();
		$cssFile = Prado::getPathOfNamespace('Vendor.bower-asset.highlightjs.styles.' . $this->getSyntaxStyle(), '.css');
		$cssKey = 'prado:TTextHighlighter:' . $cssFile;
		if (!$cs->isStyleSheetFileRegistered($cssKey)) {
			$cs->registerStyleSheetFile($cssKey, $this->publishFilePath($cssFile));
		}
	}

	/**
	 * Get javascript text highlighter options.
	 * @return array text highlighter client-side options
	 */
	protected function getTextHighlightOptions()
	{
		$options = [];
		$options['ID'] = $this->getClientID();
		$options['tabsize'] = str_repeat(' ', $this->getTabSize());
		$options['copycode'] = $this->getEnableCopyCode();
		$options['linenum'] = $this->getShowLineNumbers();

		return $options;
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->renderClientControlScript($writer);
		$writer->addAttribute('id', $this->getClientID());
		parent::renderBeginTag($writer);

		$writer->addAttribute('id', $this->getClientID() . '_code');
		$writer->addAttribute('class', $this->getLanguage());
		$writer->renderBeginTag('code');
	}

	/**
	 * Processes a text string.
	 * This method must be implemented by child classes.
	 * @param string $text text string to be processed
	 * @return string the processed text result
	 */
	public function processText($text)
	{
		if ($this->getEncodeHtml()) {
			return THttpUtility::htmlEncode(ltrim($text));
		} else {
			return $text;
		}
	}

	/**
	 * Renders the closing tag for the control
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		$writer->renderEndTag();
		parent::renderEndTag($writer);
	}

	protected function renderClientControlScript($writer)
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('texthighlight');

		$options = TJavaScript::encode($this->getTextHighlightOptions());
		$code = "new Prado.WebUI.TTextHighlighter($options);";
		$cs->registerEndScript("prado:" . $this->getClientID(), $code);
	}
}
