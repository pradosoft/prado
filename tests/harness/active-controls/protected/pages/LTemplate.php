<?php


/**
 * class file.
 *
 * @license http://opensource.org/licenses/mozilla1.1.php Mozilla Public License
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
	public function __construct()
	{
		parent::__construct();
		$this->setEnableViewState(false);
	}

	public function setSize($value)
	{
		$this->setViewState("Size", $value);
	}

	public function getSize()
	{
		return $this->getViewState("Size", "Small");
	}

	public function onInit($param)
	{
		parent :: onInit($param);
		$this->Controls[] = "OnInit";
	}

	public function onLoad($param)
	{
		parent :: onLoad($param);
		$this->Controls[] = "OnLoad";
		$this->adjustLayout();
	}

	public function onPreRender($param)
	{
		parent :: onPreRender($param);
		$this->Controls[] = "OnPreRender";
	}

	public function adjustLayout()
	{
		if ($this->getSize() == "Large") {
			$this->Small->setVisible(false);
		} else {
			$this->Large->setVisible(false);
		}
	}
}
