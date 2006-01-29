<?php

/**
 * TParam component.
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Xiang Wei Zhuo. 
 *
 * To contact the author write to {@link mailto:qiang.xue@gmail.com Qiang Xue}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.2 $  $Date: 2005/01/05 03:15:13 $
 * @package System.I18N
 */
 
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
 * Namespace: System.I18N
 *
 * Properties
 * - <b>Key</b>, string, <b>required</b>.
 *   <br>Gets or sets the string in TTranslate to substitute.
 * - <b>Trim</b>, boolean,
 *   <br>Gets or sets an option to trim the contents of the TParam.
 *   Default is to trim the contents.
 * 
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v3.0, last update on Friday, 6 January 2006
 * @package System.I18N
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
	 * @var boolean 
	 */
	protected $trim = true;

	
	/**
	 * Get the parameter substitution key.
	 * @return string substitution key. 
	 */
	public function getKey()
	{
		if(empty($this->key))
			throw new TException('The Key property must be specified.');
		return $this->key;
	}
	
	/**
	 * Set the parameter substitution key.
	 * @param string substitution key. 
	 */
	public function setKey($value)
	{
		$this->key = $value;
	}
	
	/**
	 * Set the option to trim the contents.
	 * @param boolean trim or not.
	 */
	public function setTrim($value)
	{
		$this->trim = TPropertyValue::ensureBoolean($value);
	}
	
	/**
	 * Trim the content or not.
	 * @return boolean trim or not. 
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
		if(strlen($value) > 0)
			return $value;
		$textWriter = new TTextWriter;
		$this->renderControl(new THtmlWriter($textWriter));
		return $this->getTrim() ? 
			trim($textWriter->flush()) : $textWriter->flush();
	}
}

?>