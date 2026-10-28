<?php

/**
 * TRubyText class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRubyText class
 * TRubyText represents the HTML `<rt>` element, the ruby annotation of the
 * base text before it in a {@see TRuby}: a reading, a pronunciation, or a
 * translation.
 * Accessibility: screen readers read the annotation after its base text.
 * The default tag is `rt`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRubyText extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'rt';
	}
}
