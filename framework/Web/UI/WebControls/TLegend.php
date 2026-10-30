<?php

/**
 * TLegend class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TLegend class
 *
 * TLegend represents the HTML `<legend>` element, the caption of its parent
 * {@see TFieldSet}. It is the first child of the fieldset, and holds markup or
 * controls that {@see TFieldSet::setLegend TFieldSet.Legend} text cannot.
 * ```html
 * <com:TFieldSet>
 *     <com:TLegend>Contact <abbr title="required">*</abbr></com:TLegend>
 *     <com:TTextBox ID="Email" TextMode="Email" />
 * </com:TFieldSet>
 * ```
 *
 * The default tag is `legend`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * Accessibility: the first `<legend>` of a fieldset names the fieldset's `group`.
 * A legend elsewhere names nothing.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TLegend extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'legend';
	}
}
