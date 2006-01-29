<?php

/**
 * TWizardTemplate component.
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
 * @version $Revision: 1.3 $  $Date: 2005/01/23 23:23:54 $
 * @package System.Web.UI.WebControls
 */

/**
 * The TWizardTemplate component if present within a TWizard will override
 * the specific default templates. The allowable templated to be overidden are
 * 
 *  # NavigationStart -- used for the 1st page of the form
 *  # NavigationStep -- used for each intermediate step of the form
 *  # NavigationFinish -- used for the last step of the form
 *  # NavigationSideBar -- displays the list of links to each form
 *
 * The type of template is specified by the Type property, e.g. 
 * Type="NavigationStart".
 *
 * Multiple instances of the same template are allowed. If a template
 * is not specified, the default templates will be used.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Type</b>, string, 
 *   <br>Gets or sets the template type. Valid types are
 * "NavigationStart", "NavigationStep", "NavigationFinish" and 
 * "NavigationSideBar".
 * 
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Sat Dec 11 15:25:11 EST 2004
 * @package System.Web.UI.WebControls
 */
class TWizardTemplate extends TPanel
{
	/**
	 * Navigation template ID for the 1st page of the form.
	 * @var string 
	 */
	const ID_START='NavigationStart';

	/**
	 * Navigation template ID for each intermediate step of the form.
	 * @var string 
	 */
	const ID_STEP='NavigationStep';
	
	/**
	 * Navigation template ID for the last step of the form.
	 * @var string 
	 */	
	const ID_FINISH='NavigationFinish';
	
	/**
	 * Navigation template ID for the list of links to each form.
	 * @var string 
	 */		
	const ID_SIDEBAR='NavigationSideBar';

	/**
	 * Template type.
	 * @var type 
	 */
	private $type;

	/**
	 * Set the template type, must be of "NavigationStart", 
	 * "NavigationStep", "NavigationFinish" or "NavigationSideBar".
	 * @param string template type.
	 */
	function setType($value)
	{
		$this->type = TPropertyValue::ensureEnum($value,
			self::ID_START, self::ID_STEP, self::ID_FINISH, self::ID_SIDEBAR);
	}

	/**
	 * Gets the template type.
	 * @return string template type. 
	 */
	function getType()
	{
		return $this->type;
	}

	/**
	 * Override the parent implementation. 
	 * Adds all components within the TWizardTemplate body as it's child.
	 * @param object an object within the TWizardTemplate
	 * has been handled.
	 * @param object a component object.
	 * @param object the template owner object
	 */
	 //TODO, how does this work? naming container?
	/*public function addParsedObject($object,$context)
	{
		if($object instanceof TComponent)
			$this->addChild($object);
		$this->addBody($object);
	}*/
}

?>