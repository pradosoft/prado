<?php
/**
 * TCheckBoxList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.WebControls
 */

class TCheckBoxItem extends TCheckBox {
	/**
	 * Override client implementation to avoid emitting the javascript
	 */
	protected function renderClientControlScript($writer)
	{
	}
}