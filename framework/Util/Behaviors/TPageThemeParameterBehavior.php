<?php

/**
 * TPageThemeParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TPageThemeParameterBehavior sets the Theme of a page from a parameter.
 * This parameterizes the TPage theme variable.   {@link ThemeParameter} is
 * the  key to the Application Parameters for setting the TPage.Theme.
 *
 * This is useful for setting Titles on Plugin Modules.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TPageThemeParameterBehavior extends TBehavior 
{
	/**
	 * Default ThemeParameter
	 */
	const THEME_PARAMETER_NAME = 'prop:TPage.Theme';
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_themeParameter = self::THEME_PARAMETER_NAME;
	
	/**
	 * This handles the TPage.onPreInit event.
	 * @return array of events as keys and methods as values
	 */
	public function events()
	{
		return ['onPreInit' => 'setPageThemeFromParameter'];
	}
	
	/**
	 * This method sets the Owner (TPage) Theme to the Application Variable of
	 * ThemeParameter.
	 * @param $sender object raising the event
	 * @param $param the parameter of the raised event
	 */
	public function setPageThemeFromParameter($sender, $param)
	{
		if($theme = Prado::getApplication()->getParameters()->itemAt($this->_themeParameter))
			$this->getOwner()->setTheme($theme);
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Theme.
	 */
	public function getThemeParameter()
	{
		return $this->_themeParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Theme.
	 */
	public function setThemeParameter($value)
	{
		$this->_themeParameter = $value;
	}
}
