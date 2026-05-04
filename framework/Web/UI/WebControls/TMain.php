<?php

/**
 * TMain class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TMain class
 *
 * TMain represents the HTML5 `<main>` element. The `<main>` element represents the
 * dominant content of the `<body>` of a document. The main content area consists of
 * content that is directly related to or expands upon the central topic of a document,
 * or the central focus of a web application.
 *
 * The default tag is `main`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TMain extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'main';
	}
}
