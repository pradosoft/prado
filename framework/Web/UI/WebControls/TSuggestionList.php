<?php

/**
 * TSuggestionList class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Util\TDataFieldAccessor;
use Prado\Web\THttpUtility;

/**
 * TSuggestionList class
 *
 * TSuggestionList represents the HTML5 `<datalist>` element. A `<datalist>` holds
 * predefined values that the browser suggests for an input. The input remains
 * free-text; a suggestion fills the input when chosen. A {@see TTextBox} uses the
 * list through its {@see TTextBox::setSuggestionList SuggestionList} property,
 * which renders the input's `list` attribute.
 *
 * The items populate from template, {@see setDataSource DataSource}, or
 * {@see setDataSourceID DataSourceID}, as in {@see TListControl}. Each enabled
 * item renders an `<option>`:
 * - The item's {@see TListItem::getValue Value} is the suggested value.
 * - The item's {@see TListItem::getText Text} renders as the option label when
 *   it differs from the value.
 * - Items with an empty value do not render.
 * - Item attributes render on the `<option>`, except `Group`; a `<datalist>` has
 *   no option groups.
 *
 * Data binding follows {@see TListControl} with these differences:
 * - A scalar row with an integer key binds the scalar as both value and text.
 *   A scalar row with a string key binds the key as the value. This matches
 *   the string data source rule of {@see TDataBoundControl}.
 * - {@see setDataValueField DataValueField} defaults to
 *   {@see setDataTextField DataTextField} when only the text field is set.
 * - {@see setDataTextFormatString DataTextFormatString} formats the label only.
 *
 * A `<datalist>` has no user selection. The selection properties,
 * {@see setPromptText PromptText}, {@see setPromptValue PromptValue}, and
 * {@see setAutoPostBack AutoPostBack} have no effect on rendering. The control
 * always renders its `id`, and renders outside of a form.
 *
 * Template usage:
 * ```html
 * <com:TTextBox ID="City" SuggestionList="Cities" />
 * <com:TSuggestionList ID="Cities">
 *   <com:TListItem Text="Paris" />
 *   <com:TListItem Text="London" />
 *   <com:TListItem Value="NYC" Text="New York City" />
 * </com:TSuggestionList>
 * ```
 *
 * Accessibility: browsers expose the input as a combobox with the suggestions as
 * its options. Suggestions supplement the input's visible label; they do not
 * replace it. Browser support varies by input type, so the input must accept typed
 * values without the list.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSuggestionList extends TListControl
{
	/**
	 * @return string tag name of the suggestion list
	 */
	protected function getTagName()
	{
		return 'datalist';
	}

	/**
	 * Inputs reference a `<datalist>` by its `id`, so the `id` always renders.
	 * @return bool true
	 */
	public function getEnsureId()
	{
		return true;
	}

	/**
	 * Adds the web control attributes to the renderer.
	 * The form, `multiple`, and postback handling of {@see TListControl} does not
	 * apply to a `<datalist>`.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		TWebControl::addAttributesToRender($writer);
	}

	/**
	 * Populates the suggestions from the data source.
	 * @param \Traversable $data the data
	 */
	protected function performDataBinding($data)
	{
		$items = $this->getItems();
		if (!$this->getAppendDataBoundItems()) {
			$items->clear();
		}
		$textField = $this->getDataTextField();
		$valueField = $this->getDataValueField();
		if ($textField === '') {
			$textField = 0;
			if ($valueField === '') {
				$valueField = 1;
			}
		} elseif ($valueField === '') {
			$valueField = $textField;
		}
		$textFormat = $this->getDataTextFormatString();
		foreach ($data as $key => $object) {
			$item = $items->createListItem();
			if (is_array($object) || is_object($object)) {
				$text = TDataFieldAccessor::getDataFieldValue($object, $textField);
				$item->setValue(TDataFieldAccessor::getDataFieldValue($object, $valueField));
			} else {
				$text = $object;
				$item->setValue(is_string($key) ? $key : TPropertyValue::ensureString($object));
			}
			$item->setText($this->formatDataValue($textFormat, $text));
		}
	}

	/**
	 * Renders an `<option>` for each enabled item with a value.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 */
	public function renderContents($writer)
	{
		if (!$this->getHasItems()) {
			return;
		}
		$writer->writeLine();
		foreach ($this->getItems() as $item) {
			if ($item->getEnabled() && ($value = $item->getValue()) !== '') {
				$this->renderOption($writer, $item, $value);
			}
		}
	}

	/**
	 * Renders one item as an `<option>`.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 * @param TListItem $item the item to render
	 * @param string $value the item value
	 */
	protected function renderOption($writer, $item, $value)
	{
		if ($item->getHasAttributes()) {
			foreach ($item->getAttributes() as $name => $attribute) {
				if (strcasecmp($name, 'group') !== 0) {
					$writer->addAttribute($name, $attribute);
				}
			}
		}
		$writer->addAttribute('value', $value);
		$writer->renderBeginTag('option');
		if (($text = $item->getText()) !== $value) {
			$writer->write(THttpUtility::htmlEncode($text));
		}
		$writer->renderEndTag();
		$writer->writeLine();
	}
}
