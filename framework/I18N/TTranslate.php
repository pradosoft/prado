<?php
/**
 * TTranslate, I18N translation component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\I18N;

/**
 * Get the parent control class.
 */
use Prado\Collections\TAttributeCollection;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\TControl;

/**
 * TTranslate class.
 *
 * This component performs message/string translation. The translation
 * source is set in the TGlobalization handler. The following example
 * demonstrated a simple message translation.
 * ```php
 * <com:TTranslate Text="Goodbye" />
 * ```
 *
 * Depending on the culture set on the page, the phrase "Goodbye" will
 * be translated.
 *
 * The {@see getParameters Parameters} property can be use to add name values pairs for
 * substitution. Substrings enclosed with "{" and "}" in the translation message are consider as the
 * parameter names during substitution lookup. The following example will substitute the substring
 * "{time}" with the value of the parameter attribute "Parameters.time=<%= time() %>. Note that
 * the value of the parameter named "time" is evaluated.
 * ```php
 * <com:TTranslate Parameters.time=<%= time() %> >
 *   The unix-time is "{time}".
 * </com:TTranslate>
 * ```
 *
 * More complex string substitution can be applied using the
 * TTranslateParameter component.
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
 */
class TTranslate extends TI18NControl
{
	/**
	 * @return string the text to be localized/translated.
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the text for localization.
	 * @param string $value the text for translation.
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * Set the key for message lookup.
	 * @param string $value key
	 */
	public function setKey($value)
	{
		$this->setViewState('Key', $value, '');
	}

	/**
	 * Get the key for message lookup.
	 * @return string key
	 */
	public function getKey()
	{
		return $this->getViewState('Key', '');
	}

	/**
	 * Get the message catalogue.
	 * @return string catalogue.
	 */
	public function getCatalogue()
	{
		return $this->getViewState('Catalogue', '');
	}

	/**
	 * Set the message catalogue.
	 * @param string $value catalogue.
	 */
	public function setCatalogue($value)
	{
		$this->setViewState('Catalogue', $value, '');
	}

	/**
	 * Set the option to trim the contents.
	 * @param bool $value trim or not.
	 */
	public function setTrim($value)
	{
		$this->setViewState('Trim', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Trim the content or not.
	 * @return bool trim or not.
	 */
	public function getTrim()
	{
		return $this->getViewState('Trim', true);
	}

	/**
	 * Returns the list of custom parameters.
	 * Custom parameters are name-value pairs that may subsititute translation
	 * place holders during rendering.
	 * @return TAttributeCollection the list of custom parameters
	 */
	public function getParameters()
	{
		if ($parameters = $this->getViewState('Parameters', null)) {
			return $parameters;
		} else {
			$parameters = new TAttributeCollection();
			$parameters->setCaseSensitive(true);
			$this->setViewState('Parameters', $parameters, null);
			return $parameters;
		}
	}

	/**
	 * @param mixed $name
	 * @return bool whether the named parameter exists
	 */
	public function hasParameter($name)
	{
		if ($parameters = $this->getViewState('Parameters', null)) {
			return $parameters->contains($name);
		} else {
			return false;
		}
	}

	/**
	 * @param mixed $name
	 * @return string parameter value, null if parameter does not exist
	 */
	public function getParameter($name)
	{
		if ($parameters = $this->getViewState('Parameters', null)) {
			return $parameters->itemAt($name);
		} else {
			return null;
		}
	}

	/**
	 * @param string $name parameter name
	 * @param string $value value of the parameter
	 */
	public function setParameter($name, $value)
	{
		$this->getParameters()->add($name, $value);
	}

	/**
	 * Removes the named parameter.
	 * @param string $name the name of the parameter to be removed.
	 * @return string parameter value removed, null if parameter does not exist.
	 */
	public function removeParameter($name)
	{
		if ($parameters = $this->getViewState('Parameters', null)) {
			return $parameters->remove($name);
		} else {
			return null;
		}
	}

	/**
	 * renders the translated string.
	 * @param mixed $writer
	 */
	public function render($writer)
	{
		$htmlWriter = Prado::createComponent($this->GetResponse()->getHtmlWriterType(), new TTextWriter());
		$subs = [];
		foreach ($this->getParameters() as $key => $value) {
			$subs['{' . $key . '}'] = $value;
		}
		foreach ($this->getControls() as $control) {
			if ($control instanceof TTranslateParameter) {
				$subs['{' . $control->getKey() . '}'] = $control->getParameter();
			} elseif ($control instanceof TControl) {
				$control->render($htmlWriter);
			} elseif (is_string($control)) {
				$htmlWriter->write($control);
			}
		}

		$text = $this->getText();
		if (strlen($text) == 0) {
			$text = $htmlWriter->flush();
		}
		if ($this->getTrim()) {
			$text = trim($text);
		}

		$writer->write($this->translateText($text, $subs));
	}

	/**
	 * Translates the text with subsititution.
	 * @param string $text text for translation
	 * @param array $subs list of substitutions
	 * @return string translated text
	 */
	protected function translateText($text, $subs)
	{
		$app = $this->getApplication()->getGlobalization();

		//no translation handler provided
		if (($config = $app->getTranslationConfiguration()) === null) {
			return strtr($text, $subs);
		}

		$catalogue = $this->getCatalogue();
		if (empty($catalogue) && isset($config['catalogue'])) {
			$catalogue = $config['catalogue'];
		}
		if (empty($catalogue)) {
			$catalogue = 'messages';
		}
		Translation::init($catalogue);

		$key = $this->getKey();
		if (!empty($key)) {
			$text = $key;
		}

		//translate it
		return Translation::formatter($catalogue)->format(
			$text,
			$subs,
			$catalogue,
			$this->getCharset()
		);
	}
}
