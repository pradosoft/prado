<?php
/**
 * TScaffoldEditView class and IScaffoldEditRenderer interface file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold
 */

namespace Prado\Data\ActiveRecord\Scaffold;

/**
 * Load scaffold base.
 */
use Prado\Data\ActiveRecord\Scaffold\InputBuilder\TScaffoldInputBase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TListItemType;

/**
 * Template control for editing an Active Record instance.
 * The <b>RecordClass</b> determines the Active Record class to be edited.
 * A particular record can be edited by specifying the {@link setRecordPk RecordPk}
 * value (may be an array for composite keys).
 *
 * The default editor input controls are created based on the column types.
 * The editor layout can be specified by a renderer by set the value
 * of the {@link setEditRenderer EditRenderer} property to the class name of a
 * class that implements TScaffoldEditRenderer. A renderer is an external
 * template control that implements IScaffoldEditRenderer.
 *
 * The <b>Data</b> of the IScaffoldEditRenderer will be set as the current Active
 * Record to be edited. The <b>UpdateRecord()</b> method of IScaffoldEditRenderer
 * is called when request to save the record is requested.
 *
 * Validators in the custom external editor template should have the
 * {@link TBaseValidator::setValidationGroup ValidationGroup} property set to the
 * value of the {@link getValidationGroup} of the TScaffoldEditView instance
 * (the edit view instance is the <b>Parent</b> of the IScaffoldEditRenderer in most
 * cases.
 *
 * Cosmetic changes to the default editor should be done using Cascading Stylesheets.
 * For example, a particular field/property can be hidden by specifying "display:none" for
 * the corresponding style (each field/property has unique Css class name as "property_xxx", where
 * xxx is the property name).
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Scaffold
 * @since 3.1
 */
class TScaffoldEditView extends TScaffoldBase
{
	/**
	 * @var IScaffoldEditRenderer custom scaffold edit renderer
	 */
	private $_editRenderer;

	/**
	 * Initialize the editor form if it is Visible.
	 * @param mixed $param
	 */
	public function onLoad($param)
	{
		if ($this->getVisible()) {
			$this->initializeEditForm();
		}
	}

	/**
	 * @return string the class name for scaffold editor. Defaults to empty, meaning not set.
	 */
	public function getEditRenderer()
	{
		return $this->getViewState('EditRenderer', '');
	}

	/**
	 * @param string $value the class name for scaffold editor. Defaults to empty, meaning not set.
	 */
	public function setEditRenderer($value)
	{
		$this->setViewState('EditRenderer', $value, '');
	}

	/**
	 * @param array $value Active Record primary key value to be edited.
	 */
	public function setRecordPk($value)
	{
		$this->clearRecordObject();
		$val = TPropertyValue::ensureArray($value);
		$this->setViewState('PK', count($val) > 0 ? $val : null);
	}

	/**
	 * @return array Active Record primary key value.
	 */
	public function getRecordPk()
	{
		return $this->getViewState('PK');
	}

	/**
	 * @return TActiveRecord current Active Record instance
	 */
	protected function getCurrentRecord()
	{
		return $this->getRecordObject($this->getRecordPk());
	}

	/**
	 * Initialize the editor form
	 */
	public function initializeEditForm()
	{
		$record = $this->getCurrentRecord();
		$classPath = $this->getEditRenderer();
		if ($classPath === '') {
			$columns = $this->getTableInfo()->getColumns();
			$this->getInputRepeater()->setDataSource($columns);
			$this->getInputRepeater()->dataBind();
		} else {
			if ($this->_editRenderer === null) {
				$this->createEditRenderer($record, $classPath);
			} else {
				$this->_editRenderer->setData($record);
			}
		}
	}

	/**
	 * Instantiate the external edit renderer.
	 * @param TActiveRecord $record record to be edited
	 * @param string $classPath external edit renderer class name.
	 * @throws TConfigurationException raised when renderer is not an
	 * instance of IScaffoldEditRenderer.
	 */
	protected function createEditRenderer($record, $classPath)
	{
		$this->_editRenderer = Prado::createComponent($classPath);
		if ($this->_editRenderer instanceof IScaffoldEditRenderer) {
			$index = $this->getControls()->remove($this->getInputRepeater());
			$this->getControls()->insertAt($index, $this->_editRenderer);
			$this->_editRenderer->setData($record);
		} else {
			throw new TConfigurationException(
				'scaffold_invalid_edit_renderer',
				$this->getID(),
				get_class($record)
			);
		}
	}

	/**
	 * Initialize the default editor using the scaffold input builder.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function createRepeaterEditItem($sender, $param)
	{
		$type = $param->getItem()->getItemType();
		if ($type == TListItemType::Item || $type == TListItemType::AlternatingItem) {
			$item = $param->getItem();
			$column = $item->getData();
			if ($column === null) {
				return;
			}

			$record = $this->getCurrentRecord();
			$builder = $this->getScaffoldInputBuilder($record);
			$builder->createScaffoldInput($this, $item, $column, $record);
		}
	}

	/**
	 * Bubble the command name event. Stops bubbling when the page validator false.
	 * Otherwise, the bubble event is continued.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function bubbleEvent($sender, $param)
	{
		switch (strtolower($param->getCommandName())) {
			case 'save':
				return $this->doSave() ? false : true;
			case 'clear':
				$this->setRecordPk(null);
				$this->initializeEditForm();
				return false;
			default:
				return false;
		}
	}

	/**
	 * Check the validators, then tries to save the record.
	 * @return bool true if the validators are true, false otherwise.
	 */
	protected function doSave()
	{
		if ($this->getPage()->getIsValid()) {
			$record = $this->getCurrentRecord();
			if ($this->_editRenderer === null) {
				$table = $this->getTableInfo();
				$builder = $this->getScaffoldInputBuilder($record);
				foreach ($this->getInputRepeater()->getItems() as $item) {
					$column = $table->getColumn($item->getCustomData());
					$builder->loadScaffoldInput($this, $item, $column, $record);
				}
			} else {
				$this->_editRenderer->updateRecord($record);
			}
			$record->save();
			return true;
		} elseif ($this->_editRenderer !== null) {
			//preserve the form data.
			$this->_editRenderer->updateRecord($this->getCurrentRecord());
		}

		return false;
	}

	/**
	 * @return TRepeater default editor input controls repeater
	 */
	protected function getInputRepeater()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_repeater');
	}

	/**
	 * @return TButton Button triggered to save the Active Record.
	 */
	public function getSaveButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_save');
	}

	/**
	 * @return TButton Button to clear the editor inputs.
	 */
	public function getClearButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_clear');
	}

	/**
	 * @return TButton Button to cancel the edit action (e.g. hide the edit view).
	 */
	public function getCancelButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_cancel');
	}

	/**
	 * Create the default scaffold editor control factory.
	 * @param TActiveRecord $record record instance.
	 * @return TScaffoldInputBase scaffold editor control factory.
	 */
	protected function getScaffoldInputBuilder($record)
	{
		static $_builders = [];
		$class = get_class($record);
		if (!isset($_builders[$class])) {
			$_builders[$class] = TScaffoldInputBase::createInputBuilder($record);
		}
		return $_builders[$class];
	}

	/**
	 * @return string editor validation group name.
	 */
	public function getValidationGroup()
	{
		return 'group_' . $this->getUniqueID();
	}
}
