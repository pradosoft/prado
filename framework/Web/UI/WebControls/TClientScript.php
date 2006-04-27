<?php
/**
 * TClientScript class file
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TClientScript class
 *
 * Allows importing of Prado Client Scripts from template via the 
 * {@link setUsingPradoScripts UsingPradoScripts} property. Multiple Prado
 * client-scripts can be specified using comma delimited string of the
 * javascript library to include on the page. For example,
 * 
 * <code>
 * <com:TClientScript UsingPradoScripts="effects, rico" />
 * </code>
 * 
 * @TODO May be use it to include stylesheets as well.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TClientScript extends TControl
{
	/**
	 * @return string comma delimited list of javascript libraries to included
	 * on the page.
	 */
	public function getUsingPradoScripts()
	{
		return $this->getViewState('PradoScripts', '');
	}
	
	/**
	 * Include javascript library to the current page. The current supported
	 * libraries are: "prado", "effects", "ajax", "validator", "logger",
	 * "datepicker", "rico", "colorpicker". Library dependencies are
	 * automatically resolved.
	 * 
	 * @param string comma delimited list of javascript libraries to include.
	 */
	public function setUsingPradoScripts($value)
	{
		$this->setViewState('PradoScripts', $value, '');
	}
	
	/**
	 * Calls the client script manager to add each of the requested client
	 * script libraries.
	 * @param mixed event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$scripts = preg_split('/,|\s+/', $this->getUsingPradoScripts());
		$cs = $this->getPage()->getClientScript();
		foreach($scripts as $script)
		{
			$script = trim($script);
			if(strlen($script) > 0)
				$cs->registerPradoScript($script);
		}
	}
}

?>