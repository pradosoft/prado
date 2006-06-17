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
 * The {@link setPreRenderControlTypes PreRenderControlTypes} property can
 * be used to specify that controls type/class names that should pre-render itself
 * even though they may not be rendered on the page. This is useful to publish
 * controls that require assets and is only visible after a callback response.
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
	 * @param string comma delimited list of controls that wish to be prerendered 
	 * so as to publish its assets.
	 */
	public function setPreRenderControlTypes($value)
	{
		$this->setViewState('PreRenderControls', $value);
	}
	
	/**
	 * @return string comma delimited list of controls types that require prerendering.
	 */
	public function getPreRenderControlTypes()
	{
		return $this->getViewState('PreRenderControls', '');
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
		$this->preRenderControls($param);
	}
	
	/**
	 * PreRender other controls to allow them to publish their assets. Useful
	 * when callback response components that require assets to be present on the page.
	 * @param mixed event paramater
	 */
	protected function preRenderControls($param)
	{
		$types = preg_split('/,|\s+/', $this->getPreRenderControlTypes());
		foreach($types as $type)
		{
			$control = Prado::createComponent($type);
			$control->setPage($this->getPage());
			$control->onPreRender($param);
		}		
	}
}

?>