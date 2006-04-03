<?php
/**
 * TTranslate, I18N translation component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.I18N
 */

/**
 * Get the parent control class.
 */
Prado::using('System.I18N.TI18NControl');

/**
 * TTranslate class.
 *
 * This component performs message/string translation. The translation
 * source is set in the TGlobalization handler. The following example
 * demonstrated a simple message translation.
 * <code>
 * <com:TTranslate Text="Goodbye" />
 * </code>
 *
 * Depending on the culture set on the page, the phrase "Goodbye" will
 * be translated.
 *
 * The values of any attribute in TTranslate are consider as values for
 * substitution. Strings enclosed with "{" and "}" are consider as the
 * parameters. The following example will substitution the string
 * "{time}" with the value of the attribute "time="#time()". Note that
 * the value of the attribute time is evaluated.
 * <code>
 * <com:TTranslate time="#time()">
 *   The unix-time is "{time}".
 * </com:TTranslate>
 * </code>
 *
 * More complex string substitution can be applied using the
 * TParam component.
 *
 * Namespace: System.I18N
 *
 * Properties
 * - <b>Text</b>, string,
 *   <br>Gets or sets the string to translate.
 * - <b>Catalogue</b>, string,
 *   <br>Gets or sets the catalogue for message translation. The
 *    default catalogue can be set by the @Page directive.
 * - <b>Key</b>, string,
 *   <br>Gets or sets the key used to message look up.
 * - <b>Trim</b>, boolean,
 *   <br>Gets or sets an option to trim the contents.
 *   Default is to trim the contents.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Fri Dec 24 21:38:49 EST 2004
 * @package System.I18N
 */
class TTranslate extends TI18NControl
{
	/**
	 * @return string the text to be localized/translated.
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text for localization.
	 * @param string the text for translation.
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * Set the key for message lookup.
	 * @param string key
	 */
	public function setKey($value)
	{
		$this->setViewState('Key',$value,'');
	}

	/**
	 * Get the key for message lookup.
	 * @return string key
	 */
	public function getKey()
	{
		return $this->getViewState('Key','');
	}

	/**
	 * Get the message catalogue.
	 * @return string catalogue.
	 */
	public function getCatalogue()
	{
		return $this->getViewState('Catalogue','');
	}

	/**
	 * Set the message catalogue.
	 * @param string catalogue.
	 */
	public function setCatalogue($value)
	{
		$this->setViewState('Catalogue',$value,'');
	}

	/**
	 * Set the option to trim the contents.
	 * @param boolean trim or not.
	 */
	public function setTrim($value)
	{
		$this->setViewState('Trim',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Trim the content or not.
	 * @return boolean trim or not.
	 */
	public function getTrim()
	{
		return $this->getViewState('Trim',true);
	}

	/**
	 * renders the translated string.
	 */
	public function render($writer)
	{
		$textWriter=new TTextWriter;
		$htmlWriter=new THtmlWriter($textWriter);
		$subs = array();
		foreach($this->getControls() as $control)
		{
			if($control instanceof TTranslateParameter)
				$subs['{'.$control->getKey().'}'] = $control->getParameter();
			elseif($control instanceof TControl)
				$control->render($htmlWriter);
			elseif(is_string($control))
				$textWriter->write($control);
		}

		$text = $this->getText();
		if(strlen($text)==0)
			$text = $textWriter->flush();
		if($this->getTrim())
			$text = trim($text);

		$writer->write($this->translateText($text, $subs));
	}

	/**
	 * Translates the text with subsititution.
	 * @param string text for translation
	 * @param array list of substitutions
	 * @return string translated text
	 */
	protected function translateText($text, $subs)
	{
		$app = $this->Application->getGlobalization();

		//no translation handler provided
		if(is_null($config = $app->getTranslationConfiguration()))
			return strtr($text, $subs);

		Translation::init();

		$catalogue = $this->getCatalogue();
		if(empty($catalogue) && isset($config['catalogue']))
			$catalogue = $config['catalogue'];

		$key = $this->getKey();
		if(!empty($key)) $text = $key;

		//translate it
		return Translation::formatter()->format($text,
										$subs, $catalogue, $this->getCharset());
	}
}

?>