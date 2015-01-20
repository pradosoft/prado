<?php
/**
 * TActiveCheckBoxList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

class TActiveCheckBoxListItem extends TActiveCheckBox
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 *
	 * @param THtmlWriter the writer for the rendering purpose
	 * @param string checkbox id
	 * @param string onclick js
	 */
	protected function renderInputTag($writer,$clientID,$onclick)
	{
		TCheckBox::renderInputTag($writer,$clientID,$onclick);
	}
}