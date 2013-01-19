<?php
/**
 * THeader5 class file
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: THeader5.php 2590 2008-12-10 11:34:24Z carlgmathisen $
 * @package System.Web.UI.WebControls
 */

/**
 * THeader5 class
 *
 * This is a simple class to enable your application to have headers but then have your
 * theme be able to redefine the TagName
 * This is also useful for the {@link TWebControlDecorator} (used by themes).
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @version $Id: THeader5.php 2541 2008-10-21 15:05:13Z javalizard $
 * @package System.Web.UI.WebControls
 * @since 3.2
 */
 
class THeader5 extends THtmlElement {	
	
	/**
	 * @return string tag name
	 */
	public function getDefaultTagName()
	{
		return 'h5';
	}
	
}
