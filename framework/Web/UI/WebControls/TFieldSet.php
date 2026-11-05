<?php

/**
 * TFieldSet class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TFieldSet class
 *
 * TFieldSet represents the HTML `<fieldset>` element, a group of form controls
 * with a caption. {@see setLegend Legend} renders the caption as the first child,
 * a `<legend>`, HTML-encoded when {@see setEncode Encode} is true. A
 * {@see TLegend} child renders a caption with markup or controls instead; it is
 * the first child, and Legend stays empty.
 * ```html
 * <com:TFieldSet ID="Shipping" Legend="Shipping address">
 *     <com:TLabel ForControl="Street" Text="Street" />
 *     <com:TTextBox ID="Street" />
 * </com:TFieldSet>
 * ```
 *
 * TFieldSet extends {@see TPanel}, so {@see setDefaultButton DefaultButton}
 * applies to the group. {@see setGroupingText GroupingText} is the same value as
 * Legend; a TFieldSet renders no inner fieldset.
 *
 * {@see setEnabled Enabled} false renders `disabled`, and the browser disables
 * every control in the group and submits none of their values. The child controls
 * are disabled on the server as well: {@see \Prado\Web\UI\TControl::getEnabled()}
 * with `$checkParents` reaches the fieldset, so they do not read the missing post
 * data as cleared values. The controls in the first legend stay enabled in the
 * browser and are disabled on the server.
 *
 * Accessibility: the fieldset is a `group` named by its legend. A group of radio
 * buttons or check boxes uses a fieldset whose legend asks the question
 * (WCAG 1.3.1). A fieldset without a legend is named with `Aria.label` or
 * `Aria.labelledby`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFieldSet extends TPanel
{
	/**
	 * @return string tag name of the fieldset
	 */
	protected function getTagName()
	{
		return 'fieldset';
	}

	/**
	 * @return string the caption of the fieldset. Defaults to empty.
	 */
	public function getLegend()
	{
		return $this->getGroupingText();
	}

	/**
	 * Sets the caption rendered as the `<legend>` of the fieldset.
	 * @param string $value the caption; empty renders no legend.
	 */
	public function setLegend($value)
	{
		$this->setGroupingText($value);
	}

	/**
	 * Renders the legend; the fieldset is this control's own tag.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $text the {@see setLegend Legend}
	 */
	protected function renderGroupingBeginTag($writer, $text)
	{
		$this->renderLegend($writer, $text);
	}

	/**
	 * Renders nothing; the fieldset is this control's own tag.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderGroupingEndTag($writer)
	{
	}
}
