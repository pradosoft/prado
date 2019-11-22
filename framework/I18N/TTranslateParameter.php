<?php
/**
 * TTranslateParameter component.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\I18N
 */

namespace Prado\I18N;

use Prado\Exceptions\TException;
use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\TControl;

/**
 * TTranslateParameter component should be used inside the TTranslate component to
 * allow parameter substitution.
 *
 * For example, the strings "{greeting}" and "{name}" will be replace
 * with the values of "Hello" and "World", respectively.
 * The substitution string must be enclose with "{" and "}".
 * The parameters can be further translated by using TTranslate.
 * <code>
 * <com:TTranslate>
 *   {greeting} {name}!
 *   <com:TTranslateParameter Key="name">World</com:TTranslateParameter>
 *   <com:TTranslateParameter Key="greeting">Hello</com:TTranslateParameter>
 * </com:TTranslate>
 * </code>
 *
 * Properties
 * - <b>Key</b>, string, <b>required</b>.
 *   <br>Gets or sets the string in TTranslate to substitute.
 * - <b>Trim</b>, boolean,
 *   <br>Gets or sets an option to trim the contents of the TParam.
 *   Default is to trim the contents.
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\I18N
 */
class TTranslateParameter extends TControl
{
	/**
	 * The substitution key.
	 * @var string
	 */
	protected $key;

	/**
	 * To trim or not to trim the contents.
	 * @var bool
	 */
	protected $trim = true;


	/**
	 * Get the parameter substitution key.
	 * @return string substitution key.
	 */
	public function getKey()
	{
		if (empty($this->key)) {
			throw new TException('The Key property must be specified.');
		}
		return $this->key;
	}

	/**
	 * Set the parameter substitution key.
	 * @param string $value substitution key.
	 */
	public function setKey($value)
	{
		$this->key = $value;
	}

	/**
	 * Set the option to trim the contents.
	 * @param bool $value trim or not.
	 */
	public function setTrim($value)
	{
		$this->trim = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Trim the content or not.
	 * @return bool trim or not.
	 */
	public function getTrim()
	{
		return $this->trim;
	}

	public function getValue()
	{
		return $this->getViewState('Value', '');
	}

	public function setValue($value)
	{
		$this->setViewState('Value', $value, '');
	}

	/**
	 * @return string parameter contents.
	 */
	public function getParameter()
	{
		$value = $this->getValue();
		if (strlen($value) > 0) {
			return $value;
		}
		$htmlWriter = Prado::createComponent($this->GetResponse()->getHtmlWriterType(), new TTextWriter());
		$this->renderControl($htmlWriter);
		return $this->getTrim() ?
			trim($htmlWriter->flush()) : $htmlWriter->flush();
	}
}
