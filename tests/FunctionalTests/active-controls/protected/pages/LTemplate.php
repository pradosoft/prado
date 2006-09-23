<?php


/**
 * class file.
 *
 * @license http://opensource.org/licenses/mozilla1.1.php Mozilla Public License
 * @copyright 2005, diemeisterei GmbH. All rights reserved.
 * @author $Author: tobias $
 * @version $Revision: 1.5 $  $Date: 2006/01/03 19:08:05 $
 * @package Lithron
 * @subpackage none
 */

/**
 *
 *
 * @package Lithron
 * @subpackage none
 */

class LTemplate extends TTemplateControl
{
	function __construct()
	{
		parent::__construct();
		$this->setEnableViewState(false);
	}

	function setSize($value)
	{
		$this->setViewState("Size", $value);
	}

	function getSize()
	{
		return $this->getViewState("Size", "Small");
	}

	function onInit($param)
	{
		parent :: onInit($param);
		$this->Controls[] = "OnInit";
	}

	function onLoad($param)
	{
		parent :: onLoad($param);
		$this->Controls[] = "OnLoad";
		$this->adjustLayout();
	}

	function onPreRender($param)
	{
		parent :: onPreRender($param);
		$this->Controls[] = "OnPreRender";
	}

	function adjustLayout()
	{
		if ($this->getSize() == "Large")
		{
			$this->Small->setVisible(false);
		}
		else
		{
			$this->Large->setVisible(false);
		}
	}

}
?>