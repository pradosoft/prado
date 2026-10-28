<?php

/**
 * TSup class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSup class
 * TSup represents the HTML `<sup>` element, a superscript required by a
 * typographical convention: an exponent, an ordinal indicator, the abbreviation
 * Mlle. It is not for styling alone.
 * Accessibility: browsers expose `<sup>` with the `superscript` role. Most screen
 * readers do not announce a superscript, so x<sup>2</sup> reads as "x 2"; text
 * that depends on it says so in words or in a visually hidden span. A footnote
 * reference is a link inside the TSup, whose text names the footnote.
 * The default tag is `sup`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSup extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'sup';
	}
}
