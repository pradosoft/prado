<?php
/**
 * THeader6 class file
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

/**
 * THeader6 class
 *
 * This is a simple class to enable your application to have headers but then have your
 * theme be able to redefine the TagName
 * This is also useful for the {@link TWebControlDecorator} (used by themes).
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.2
 */

class THeader6 extends THtmlElement
{

	/**
	 * @return string tag name
	 */
	public function getDefaultTagName()
	{
		return 'h6';
	}
}
