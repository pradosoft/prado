<?php

/**
 * TWizardStep component.
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
 * @version $Revision: 1.1 $  $Date: 2005/01/22 03:11:28 $
 * @package System.Web.UI.WebControls
 */

/**
 * Each step of the TWizard is specified by one TWizardStep component.
 * A wizard step can be of type "Auto" or "Final" be specifying the 
 * Type property. The "Final" step type should be the very last step
 * of the form to show a final confirmation/"Thank you" note. All other
 * steps should be of Type="Auto". The Title property is by default
 * used by the Navigation side bar as the name of the links to each form.
 *
 * TWizardStep should be used within a TWizard component.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>Type</b>, string, 
 *   <br>Gets or sets the step type. Valid types are
 * "Auto" and "Final".
 * - <b>Title</b>, string,
 *   <br>Gets or sets the title for this wizard step.
 * 
 * @author Xiang Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version v1.0, last update on Sat Dec 11 15:25:11 EST 2004
 * @package System.Web.UI.WebControls
 */
class TWizardStep extends TPanel
{
	/**
	 * Wizard step type "Auto"
	 * @var string 
	 */
	const TYPE_AUTO = 'Auto';
	
	/**
	 * Wizard step type "Final"
	 * @var string 
	 */
	const TYPE_FINAL = 'Final';
	
	/**
	 * Get the wizard step type.
	 * @return string step type. 
	 */
	function getType()
	{
		return $this->getViewState('Type',self::TYPE_AUTO);
	}

	/**
	 * Set the wizard step type, default is "Auto". Valid step
	 * types are "Auto" and "Final".
	 * @param string step type
	 */
	function setType($value)
	{
		$this->setViewState('Type',	TPropertyValue::ensureEnum(
			$value, self::TYPE_AUTO, self::TYPE_FINAL), self::TYPE_AUTO);
	}
}

?>