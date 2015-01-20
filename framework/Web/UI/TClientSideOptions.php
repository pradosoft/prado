<?php
/**
 * TClientScriptManager and TClientSideOptions class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Gabor Berczi <gabor.berczi@devworx.hu> (lazyload additions & progressive rendering)
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

/**
 * TClientSideOptions abstract class.
 *
 * TClientSideOptions manages client-side options for components that have
 * common client-side javascript behaviours and client-side events such as
 * between ActiveControls and validators.
 *
 * @author <weizhuo[at]gmail[dot]com>
 * @package System.Web.UI
 * @since 3.0
 */
abstract class TClientSideOptions extends TComponent
{
	/**
	 * @var TMap list of client-side options.
	 */
	private $_options;

	/**
	 * Adds on client-side event handler by wrapping the code within a
	 * javascript function block. If the code begins with "javascript:", the
	 * code is assumed to be a javascript function block rather than arbiturary
	 * javascript statements.
	 * @param string option name
	 * @param string javascript statements.
	 */
	protected function setFunction($name, $code)
	{
		if(!TJavaScript::isJsLiteral($code))
			$code = TJavaScript::quoteJsLiteral($this->ensureFunction($code));
		$this->setOption($name, $code);
	}

	/**
	 * @return string gets a particular option, null if not set.
	 */
	protected function getOption($name)
	{
		if ($this->_options)
			return $this->_options->itemAt($name);
		else
			return null;
	}

	/**
	 * @param string option name
	 * @param mixed option value.
	 */
	protected function setOption($name, $value)
	{
		$this->getOptions()->add($name, $value);
	}

	/**
	 * @return TMap gets the list of options as TMap
	 */
	public function getOptions()
	{
		if (!$this->_options)
			$this->_options = Prado::createComponent('System.Collections.TMap');
		return $this->_options;
	}

	/**
	 * Ensure that the javascript statements are wrapped in a javascript
	 * function block as <code>function(sender, parameter){ //code }</code>.
	 */
	protected function ensureFunction($javascript)
	{
		return "function(sender, parameter){ {$javascript} }";
	}
}
