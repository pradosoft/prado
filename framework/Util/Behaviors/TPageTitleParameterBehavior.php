<?php

/**
 * TPageTitleParameterBehavior class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Behaviors;

use Prado\Prado;
use Prado\Util\TBehavior;

/**
 * TPageTitleParameterBehavior sets the Title of a page from a parameter.
 * This parameterizes the TPage title variable.   {@link TitleParameter} is
 * the  key to the Application Parameters for setting the TPage.Title.
 *
 * This is useful for setting Titles on Plugin Modules.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @package Prado\Util\Behaviors
 * @since 4.2.0
 */
class TPageTitleParameterBehavior extends TBehavior 
{
	/**
	 * Default ThemeParameter
	 */
	const TITLE_PARAMETER_NAME = 'prop:TPage.Title';
	
	/**
	 * @var string the page theme is set to this parameter key
	 */
	private $_titleParameter = self::TITLE_PARAMETER_NAME;
	
	/**
	 * This handles the TPage.onPreInit event.
	 * @return array of events as keys and methods as values
	 */
	public function events()
	{
		return ['onPreInit' => 'setPageTitleFromParameter'];
	}
	
	/**
	 * This method sets the Owner (TPage) Theme to the Application Variable of
	 * ThemeParameter.
	 * @param $sender object raising the event
	 * @param $param the parameter of the raised event
	 */
	public function setPageTitleFromParameter($sender, $param)
	{
		if($title = Prado::getApplication()->getParameters()->itemAt($this->_titleParameter)) {
			$this->getOwner()->setTitle($title);
		}
	}
	
	/**
	 * @return string Application parameter key to set the TPage.Title.
	 */
	public function getTitleParameter()
	{
		return $this->_titleParameter;
	}
	
	/**
	 * @param $value string Application parameter key to set the TPage.Title.
	 */
	public function setTitleParameter($value)
	{
		$this->_titleParameter = $value;
	}
}
