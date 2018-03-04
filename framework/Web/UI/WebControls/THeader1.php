<?php
/**
 * THeader1 class file
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * THeader1 class
 *
 * This is a simple class to enable your application to have headers but then have your
 * theme be able to redefine the TagName
 * This is also useful for the {@link TWebControlDecorator} (used by themes).
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.2a
 */

class THeader1 extends THtmlElement
{

	/**
	 * @return string tag name
	 */
	public function getDefaultTagName()
	{
		return 'h1';
	}
}
