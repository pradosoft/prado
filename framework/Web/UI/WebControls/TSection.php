<?php

/**
 * TSection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSection class
 *
 * TSection represents the HTML5 `<section>` element. The `<section>` element represents
 * a generic section of a document, such as a chapter, or a topic.
 *
 * The default tag is `section`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TSection extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'section';
	}
}
