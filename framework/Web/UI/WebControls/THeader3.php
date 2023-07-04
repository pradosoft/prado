<?php
/**
 * THeader3 class file
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * THeader3 class
 *
 * This is a simple class to enable your application to have headers but then have your
 * theme be able to redefine the TagName
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator} (used by themes).
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @since 3.2a
 */
class THeader3 extends THtmlElement
{
	/**
	 * @return string tag name
	 */
	public function getDefaultTagName()
	{
		return 'h3';
	}
}
