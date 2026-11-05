<?php

/**
 * TNoScript class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TNoScript class
 *
 * TNoScript represents the HTML `<noscript>` element. A browser with scripting
 * disabled renders its content; a browser with scripting enabled renders nothing.
 * It holds the fallback for a feature that needs JavaScript, such as a
 * {@see TCanvas} drawing or a callback-only control.
 * ```html
 * <com:TNoScript>
 *     <p>The chart needs JavaScript. <com:THyperLink NavigateUrl="report.csv" Text="Download the data" /></p>
 * </com:TNoScript>
 * ```
 *
 * The default tag is `noscript`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * The content renders on the server in every request; the browser decides whether
 * it shows. Controls inside a TNoScript post back only without JavaScript, so they
 * use no `AutoPostBack` and no callbacks. A callback that re-renders a TNoScript
 * inserts content that a scripting browser does not show.
 *
 * Accessibility: content inside `<noscript>` is absent from the accessibility tree
 * when scripting is enabled. The fallback gives the same information or a way to
 * reach it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TNoScript extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'noscript';
	}
}
