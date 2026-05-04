<?php

/**
 * TArticle class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TArticle class
 *
 * TArticle represents the HTML5 `<article>` element. The `<article>` element is a
 * a self-contained composition in a document, page, application, or site. The tag
 * can be changed through the {@see setTagName TagName} property (e.g. from a theme
 * or template).
 *
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TArticle extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'article';
	}
}
