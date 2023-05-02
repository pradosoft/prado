<?php
/**
 * TListControl class file
 *
 * @author Robin J. Rogge <rojaro@gmail.com>
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Exception;
use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;
use Prado\Util\TDataFieldAccessor;
use Prado\Web\THttpUtility;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TListControl class
 *
 * TListControl is a base class for list controls, such as {@link TListBox},
 * {@link TDropDownList}, {@link TCheckBoxList}, etc.
 * It manages the items and their status in a list control.
 * It also implements how the items can be populated from template and
 * data source.
 *
 * The property {@link getItems} returns a list of the items in the control.
 * To specify or determine which item is selected, use the
 * {@link getSelectedIndex SelectedIndex} property that indicates the zero-based
 * index of the selected item in the item list. You may also use
 * {@link getSelectedItem SelectedItem} and {@link getSelectedValue SelectedValue}
 * to get the selected item and its value. For multiple selection lists
 * (such as {@link TCheckBoxList} and {@link TListBox}), property
 * {@link getSelectedIndices SelectedIndices} is useful.
 *
 * TListControl implements {@link setAutoPostBack AutoPostBack} which allows
 * a list control to postback the page if the selections of the list items are changed.
 * The {@link setCausesValidation CausesValidation} and {@link setValidationGroup ValidationGroup}
 * properties may be used to specify that validation be performed when auto postback occurs.
 *
 * There are three ways to populate the items in a list control: from template,
 * using {@link setDataSource DataSource} and using {@link setDataSourceID DataSourceID}.
 * The latter two are covered in {@link TDataBoundControl}. To specify items via
 * template, using the following template syntax:
 * <code>
 * <com:TListControl>
 *   <com:TListItem Value="xxx" Text="yyy" >
 *   <com:TListItem Value="xxx" Text="yyy" Selected="true" >
 *   <com:TListItem Value="xxx" Text="yyy" >
 * </com:TListControl>
 * </code>
 *
 * When {@link setDataSource DataSource} or {@link setDataSourceID DataSourceID}
 * is used to populate list items, the {@link setDataTextField DataTextField} and
 * {@link setDataValueField DataValueField} properties are used to specify which
 * columns of the data will be used to populate the text and value of the items.
 * For example, if a data source is as follows,
 * <code>
 * $dataSource=array(
 *    array('name'=>'John', 'age'=>31),
 *    array('name'=>'Cary', 'age'=>28),
 *    array('name'=>'Rose', 'age'=>35),
 * );
 * </code>
 * setting {@link setDataTextField DataTextField} and {@link setDataValueField DataValueField}
 * to 'name' and 'age' will make the first item's text be 'John', value be 31,
 * the second item's text be 'Cary', value be 28, and so on.
 * The {@link setDataTextFormatString DataTextFormatString} property may be further
 * used to format how the item should be displayed. See {@link formatDataValue()}
 * for an explanation of the format string.
 *
 * The {@link setPromptText PromptText} and {@link setPromptValue PromptValue} properties can
 * be used to add a dummy list item that will be rendered first.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
abstract class TListControl extends TDataBoundControl implements \Prado\IDataRenderer
{
	/**
	 * @var \Prado\Collections\TListItemCollection item list
	 */
	private $_items;
	/**
	 * @var bool whether items are restored from viewstate
	 */
	private $_stateLoaded = false;
	/**
	 * @var mixed the following selection variables are used
	 * to keep selections when Items are not available
	 */
	private $_cachedSelectedIndex = -1;
	private $_cachedSelectedValue;
	private $_cachedSelectedIndices;
	private $_cachedSelectedValues;

	/**
	 * @return string tag name of the list control
	 */
	protected function getTagName()
	{
		return 'select';
	}

	/**
	 * @return bool whether to render javascript.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * @param bool $value whether to render javascript.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Adds attributes to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$page = $this->getPage();
		$page->ensureRenderInForm($this);
		if ($this->getIsMultiSelect()) {
			$writer->addAttribute('multiple', 'multiple');
		}
		if ($this->getEnabled(true)) {
			if ($this->getAutoPostBack()
				&& $this->getEnableClientScript()
				&& $page->getClientSupportsJavaScript()) {
				$this->renderClientControlScript($writer);
			}
		} elseif ($this->getEnabled()) {
			$writer->addAttribute('disabled', 'disabled');
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the javascript for list control.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
		$writer->addAttribute('id', $this->getClientID());
		$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * Derived classes may override this method and return customized js class names.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TListControl';
	}

	/**
	 * @return array postback options for JS postback code
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TListItem} objects into the {@link getItems Items} collection.
	 * All other objects are ignored.
	 * @param mixed $object object parsed from template
	 */
	public function addParsedObject($object)
	{
		// Do not add items from template if items are loaded from viewstate
		if (!$this->_stateLoaded && ($object instanceof TListItem)) {
			$index = $this->getItems()->add($object);
			if (($this->_cachedSelectedValue !== null && $this->_cachedSelectedValue === $object->getValue()) || ($this->_cachedSelectedIndex === $index)) {
				$object->setSelected(true);
				$this->_cachedSelectedValue = null;
				$this->_cachedSelectedIndex = -1;
			}
		}
	}

	/**
	 * Performs databinding to populate list items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param \Traversable $data the data
	 */
	protected function performDataBinding($data)
	{
		$items = $this->getItems();
		if (!$this->getAppendDataBoundItems()) {
			$items->clear();
		}
		$textField = $this->getDataTextField();
		if ($textField === '') {
			$textField = 0;
		}
		$valueField = $this->getDataValueField();
		if ($valueField === '') {
			$valueField = 1;
		}
		$textFormat = $this->getDataTextFormatString();
		$groupField = $this->getDataGroupField();
		foreach ($data as $key => $object) {
			$item = $items->createListItem();
			if (is_array($object) || is_object($object)) {
				$text = TDataFieldAccessor::getDataFieldValue($object, $textField);
				$value = TDataFieldAccessor::getDataFieldValue($object, $valueField);
				$item->setValue($value);
				if ($groupField !== '') {
					$item->setAttribute('Group', TDataFieldAccessor::getDataFieldValue($object, $groupField));
				}
			} else {
				$text = $object;
				$item->setValue("$key");
			}
			$item->setText($this->formatDataValue($textFormat, $text));
		}
		// SelectedValue or SelectedIndex may be set before databinding
		// so we make them be effective now
		if ($this->_cachedSelectedValue !== null) {
			$this->setSelectedValue($this->_cachedSelectedValue);
			$this->resetCachedSelections();
		} elseif ($this->_cachedSelectedIndex !== -1) {
			$this->setSelectedIndex($this->_cachedSelectedIndex);
			$this->resetCachedSelections();
		} elseif ($this->_cachedSelectedValues !== null) {
			$this->setSelectedValues($this->_cachedSelectedValues);
			$this->resetCachedSelections();
		} elseif ($this->_cachedSelectedIndices !== null) {
			$this->setSelectedIndices($this->_cachedSelectedIndices);
			$this->resetCachedSelections();
		}
	}

	private function resetCachedSelections()
	{
		$this->_cachedSelectedValue = null;
		$this->_cachedSelectedIndex = -1;
		$this->_cachedSelectedValues = null;
		$this->_cachedSelectedIndices = null;
	}

	/**
	 * Creates a collection object to hold list items.
	 * This method may be overriden to create a customized collection.
	 * @return \Prado\Collections\TListItemCollection the collection object
	 */
	protected function createListItemCollection()
	{
		return new \Prado\Collections\TListItemCollection();
	}

	/**
	 * Saves items into viewstate.
	 * This method is invoked right before control state is to be saved.
	 */
	public function saveState()
	{
		parent::saveState();
		if ($this->_items) {
			$this->setViewState('Items', $this->_items->saveState(), null);
		} else {
			$this->clearViewState('Items');
		}
	}

	/**
	 * Loads items from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
		$this->_stateLoaded = true;
		if (!$this->getIsDataBound()) {
			$this->_items = $this->createListItemCollection();
			$this->_items->loadState($this->getViewState('Items', null));
		}
		$this->clearViewState('Items');
	}

	/**
	 * @return bool whether this is a multiselect control. Defaults to false.
	 */
	protected function getIsMultiSelect()
	{
		return false;
	}

	/**
	 * @return bool whether performing databind should append items or clear the existing ones. Defaults to false.
	 */
	public function getAppendDataBoundItems()
	{
		return $this->getViewState('AppendDataBoundItems', false);
	}

	/**
	 * @param bool $value whether performing databind should append items or clear the existing ones.
	 */
	public function setAppendDataBoundItems($value)
	{
		$this->setViewState('AppendDataBoundItems', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool a value indicating whether an automatic postback to the server
	 * will occur whenever the user makes change to the list control and then tabs out of it.
	 * Defaults to false.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack', false);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * makes change to the list control and then tabs out of it.
	 * @param bool $value the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether postback event trigger by this list control will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * @param bool $value whether postback event trigger by this list control will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the field of the data source that provides the text content of the list items.
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField', '');
	}

	/**
	 * @param string $value the field of the data source that provides the text content of the list items.
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField', $value, '');
	}

	/**
	 * @return string the formatting string used to control how data bound to the list control is displayed.
	 */
	public function getDataTextFormatString()
	{
		return $this->getViewState('DataTextFormatString', '');
	}

	/**
	 * Sets data text format string.
	 * The format string is used in {@link TDataValueFormatter::format()} to format the Text property value
	 * of each item in the list control.
	 * @param string $value the formatting string used to control how data bound to the list control is displayed.
	 * @see TDataValueFormatter::format()
	 */
	public function setDataTextFormatString($value)
	{
		$this->setViewState('DataTextFormatString', $value, '');
	}

	/**
	 * @return string the field of the data source that provides the value of each list item.
	 */
	public function getDataValueField()
	{
		return $this->getViewState('DataValueField', '');
	}

	/**
	 * @param string $value the field of the data source that provides the value of each list item.
	 */
	public function setDataValueField($value)
	{
		$this->setViewState('DataValueField', $value, '');
	}

	/**
	 * @return string the field of the data source that provides the label of the list item groups
	 */
	public function getDataGroupField()
	{
		return $this->getViewState('DataGroupField', '');
	}

	/**
	 * @param string $value the field of the data source that provides the label of the list item groups
	 */
	public function setDataGroupField($value)
	{
		$this->setViewState('DataGroupField', $value, '');
	}

	/**
	 * @return int the number of items in the list control
	 */
	public function getItemCount()
	{
		return $this->_items ? $this->_items->getCount() : 0;
	}

	/**
	 * @return bool whether the list control contains any items.
	 */
	public function getHasItems()
	{
		return ($this->_items && $this->_items->getCount() > 0);
	}

	/**
	 * @return \Prado\Collections\TListItemCollection the item collection
	 */
	public function getItems()
	{
		if (!$this->_items) {
			$this->_items = $this->createListItemCollection();
		}
		return $this->_items;
	}

	/**
	 * @return int the index (zero-based) of the item being selected, -1 if no item is selected.
	 */
	public function getSelectedIndex()
	{
		if ($this->_items) {
			$n = $this->_items->getCount();
			for ($i = 0; $i < $n; ++$i) {
				if ($this->_items->itemAt($i)->getSelected()) {
					return $i;
				}
			}
		}
		return -1;
	}

	/**
	 * @param int $index the index (zero-based) of the item to be selected
	 */
	public function setSelectedIndex($index)
	{
		if (($index = TPropertyValue::ensureInteger($index)) < 0) {
			$index = -1;
		}
		if ($this->_items) {
			$this->clearSelection();
			if ($index >= 0 && $index < $this->_items->getCount()) {
				$this->_items->itemAt($index)->setSelected(true);
			}
		}
		$this->_cachedSelectedIndex = $index;
		if ($this->getAdapter() instanceof IListControlAdapter) {
			$this->getAdapter()->setSelectedIndex($index);
		}
	}

	/**
	 * @return array list of index of items that are selected
	 */
	public function getSelectedIndices()
	{
		$selections = [];
		if ($this->_items) {
			$n = $this->_items->getCount();
			for ($i = 0; $i < $n; ++$i) {
				if ($this->_items->itemAt($i)->getSelected()) {
					$selections[] = $i;
				}
			}
		}
		return $selections;
	}

	/**
	 * @param array $indices list of index of items to be selected
	 */
	public function setSelectedIndices($indices)
	{
		if ($this->getIsMultiSelect()) {
			if ($this->_items) {
				$this->clearSelection();
				$n = $this->_items->getCount();
				foreach ($indices as $index) {
					if ($index >= 0 && $index < $n) {
						$this->_items->itemAt($index)->setSelected(true);
					}
				}
			}
			$this->_cachedSelectedIndices = $indices;
		} else {
			throw new TNotSupportedException('listcontrol_multiselect_unsupported', $this::class);
		}

		if ($this->getAdapter() instanceof IListControlAdapter) {
			$this->getAdapter()->setSelectedIndices($indices);
		}
	}

	/**
	 * @return null|TListItem the selected item with the lowest cardinal index, null if no item is selected.
	 */
	public function getSelectedItem()
	{
		if (($index = $this->getSelectedIndex()) >= 0) {
			return $this->_items->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * Returns the value of the selected item with the lowest cardinal index.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getSelectedValue()}.
	 * @return string the value of the selected item with the lowest cardinal index, empty if no selection.
	 * @see getSelectedValue
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getSelectedValue();
	}

	/**
	 * Selects an item by the specified value.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setSelectedValue()}.
	 * @param string $value the value of the item to be selected.
	 * @see setSelectedValue
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setSelectedValue($value);
	}

	/**
	 * @return string the value of the selected item with the lowest cardinal index, empty if no selection
	 */
	public function getSelectedValue()
	{
		$index = $this->getSelectedIndex();
		return $index >= 0 ? $this->getItems()->itemAt($index)->getValue() : $this->getPromptValue();
	}

	/**
	 * Sets selection by item value.
	 * Existing selections will be cleared if the item value is found in the item collection.
	 * Note, if the value is null, existing selections will also be cleared.
	 * @param string $value the value of the item to be selected.
	 */
	public function setSelectedValue($value)
	{
		if ($this->_items) {
			if ($value === null) {
				$this->clearSelection();
			} elseif (($item = $this->_items->findItemByValue($value)) !== null) {
				$this->clearSelection();
				$item->setSelected(true);
			} else {
				$this->clearSelection();
			}
		}
		$this->_cachedSelectedValue = $value;
		if ($this->getAdapter() instanceof IListControlAdapter) {
			$this->getAdapter()->setSelectedValue($value);
		}
	}


	/**
	 * @return array list of the selected item values (strings)
	 */
	public function getSelectedValues()
	{
		$values = [];
		if ($this->_items) {
			foreach ($this->_items as $item) {
				if ($item->getSelected()) {
					$values[] = $item->getValue();
				}
			}
		}
		return $values;
	}

	/**
	 * @param array $values list of the selected item values
	 */
	public function setSelectedValues($values)
	{
		if ($this->getIsMultiSelect()) {
			if ($this->_items) {
				$this->clearSelection();
				$lookup = [];
				foreach ($this->_items as $item) {
					$lookup[$item->getValue()] = $item;
				}
				foreach ($values as $value) {
					if (isset($lookup["$value"])) {
						$lookup["$value"]->setSelected(true);
					}
				}
			}
			$this->_cachedSelectedValues = $values;
		} else {
			throw new TNotSupportedException('listcontrol_multiselect_unsupported', $this::class);
		}

		if ($this->getAdapter() instanceof IListControlAdapter) {
			$this->getAdapter()->setSelectedValues($values);
		}
	}

	/**
	 * @return string selected value
	 */
	public function getText()
	{
		return $this->getSelectedValue();
	}

	/**
	 * @param string $value value to be selected
	 */
	public function setText($value)
	{
		$this->setSelectedValue($value);
	}

	/**
	 * Clears all existing selections.
	 */
	public function clearSelection()
	{
		if ($this->_items) {
			foreach ($this->_items as $item) {
				$item->setSelected(false);
			}
		}

		if ($this->getAdapter() instanceof IListControlAdapter) {
			$this->getAdapter()->clearSelection();
		}
	}

	/**
	 * @return string the group of validators which the list control causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the list control causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * @return string the prompt text which is to be displayed as the first list item.
	 * @since 3.1.1
	 */
	public function getPromptText()
	{
		return $this->getViewState('PromptText', '');
	}

	/**
	 * @param string $value the prompt text which is to be displayed as the first list item.
	 * @since 3.1.1
	 */
	public function setPromptText($value)
	{
		$this->setViewState('PromptText', $value, '');
	}

	/**
	 * @return string the prompt selection value.
	 * @see getPromptText
	 * @since 3.1.1
	 */
	public function getPromptValue()
	{
		return $this->getViewState('PromptValue', '');
	}

	/**
	 * @param string $value the prompt selection value.
	 * @see setPromptText
	 * @since 3.1.1
	 */
	public function setPromptValue($value)
	{
		$this->setViewState('PromptValue', (string) $value, '');
	}

	/**
	 * Raises OnSelectedIndexChanged event when selection is changed.
	 * This method is invoked when the list control has its selection changed
	 * by end-users.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onSelectedIndexChanged($param)
	{
		$this->raiseEvent('OnSelectedIndexChanged', $this, $param);
		$this->onTextChanged($param);
	}

	/**
	 * Raises OnTextChanged event when selection is changed.
	 * This method is invoked when the list control has its selection changed
	 * by end-users.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onTextChanged($param)
	{
		$this->raiseEvent('OnTextChanged', $this, $param);
	}

	/**
	 * Renders the prompt text, if any.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 * @since 3.1.1
	 */
	protected function renderPrompt($writer)
	{
		$text = $this->getPromptText();
		$value = $this->getPromptValue();
		if ($value !== '' || $text !== '') {
			$writer->addAttribute('value', $value);
			$writer->renderBeginTag('option');
			$writer->write(THttpUtility::htmlEncode($text));
			$writer->renderEndTag();
			$writer->writeLine();
		}
	}

	/**
	 * Renders body content of the list control.
	 * This method renders items contained in the list control as the body content.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer
	 */
	public function renderContents($writer)
	{
		$this->renderPrompt($writer);

		if ($this->_items) {
			$writer->writeLine();
			$previousGroup = null;
			foreach ($this->_items as $item) {
				if ($item->getEnabled()) {
					if ($item->getHasAttributes()) {
						$group = $item->getAttributes()->remove('Group');
						if ($group !== $previousGroup) {
							if ($previousGroup !== null) {
								$writer->renderEndTag();
								$writer->writeLine();
								$previousGroup = null;
							}
							if ($group !== null) {
								$writer->addAttribute('label', $group);
								$writer->renderBeginTag('optgroup');
								$writer->writeLine();
								$previousGroup = $group;
							}
						}
						foreach ($item->getAttributes() as $name => $value) {
							$writer->addAttribute($name, $value);
						}
					} elseif ($previousGroup !== null) {
						$writer->renderEndTag();
						$writer->writeLine();
						$previousGroup = null;
					}
					if ($item->getSelected()) {
						$writer->addAttribute('selected', 'selected');
					}
					$writer->addAttribute('value', $item->getValue());
					$writer->renderBeginTag('option');
					$writer->write(THttpUtility::htmlEncode($item->getText()));
					$writer->renderEndTag();
					$writer->writeLine();
				}
			}
			if ($previousGroup !== null) {
				$writer->renderEndTag();
				$writer->writeLine();
			}
		}
	}

	/**
	 * Formats the text value according to a format string.
	 * If the format string is empty, the original value is converted into
	 * a string and returned.
	 * If the format string starts with '#', the string is treated as a PHP expression
	 * within which the token '{0}' is translated with the data value to be formated.
	 * Otherwise, the format string and the data value are passed
	 * as the first and second parameters in {@link sprintf}.
	 * @param string $formatString format string
	 * @param mixed $value the data to be formatted
	 * @return string the formatted result
	 */
	protected function formatDataValue($formatString, $value)
	{
		if ($formatString === '') {
			return TPropertyValue::ensureString($value);
		} elseif ($formatString[0] === '#') {
			$expression = strtr(substr($formatString, 1), ['{0}' => '$value']);
			try {
				return eval("return $expression;");
			} catch (Exception $e) {
				throw new TInvalidDataValueException('listcontrol_expression_invalid', $this::class, $expression, $e->getMessage());
			}
		} else {
			return sprintf($formatString, $value);
		}
	}
}
