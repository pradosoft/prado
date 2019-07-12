<?php
/**
 * TScaffoldView class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\ActiveRecord\Scaffold
 */

namespace Prado\Data\ActiveRecord\Scaffold;

/**
 * Import scaffold base, list, edit and search controls.
 */
use Prado\Prado;

/**
 * TScaffoldView is a composite control consisting of TScaffoldListView
 * with a TScaffoldSearch. In addition, it will display a TScaffoldEditView
 * when an "edit" command is raised from the TScaffoldListView (when the
 * edit button is clicked). Futher more, the "add" button can be clicked
 * that shows an empty data TScaffoldListView for creating new records.
 *
 * The {@link getListView ListView} property gives a TScaffoldListView for
 * display the record data. The {@link getEditView EditView} is the
 * TScaffoldEditView that renders the
 * inputs for editing and adding records. The {@link getSearchControl SearchControl}
 * is a TScaffoldSearch responsible to the search user interface.
 *
 * Set the {@link setRecordClass RecordClass} property to the name of
 * the Active Record class to be displayed/edited/added.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\ActiveRecord\Scaffold
 * @since 3.0
 */
class TScaffoldView extends TScaffoldBase
{
	/**
	 * Copy basic record details to the list/edit/search controls.
	 * @param mixed $param
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getListView()->copyFrom($this);
		$this->getEditView()->copyFrom($this);
		$this->getSearchControl()->copyFrom($this);
	}

	/**
	 * @return TScaffoldListView scaffold list view.
	 */
	public function getListView()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_listView');
	}

	/**
	 * @return TScaffoldEditView scaffold edit view.
	 */
	public function getEditView()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_editView');
	}

	/**
	 * @return TScaffoldSearch scaffold search textbox and button.
	 */
	public function getSearchControl()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_search');
	}

	/**
	 * @return TButton "Add new record" button.
	 */
	public function getAddButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_newButton');
	}

	/**
	 * Handle the "edit" and "new" commands by displaying the edit view.
	 * Default command shows the list view.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function bubbleEvent($sender, $param)
	{
		switch (strtolower($param->getCommandName())) {
			case 'edit':
				return $this->showEditView($sender, $param);
			case 'new':
				return $this->showAddView($sender, $param);
			default:
				return $this->showListView($sender, $param);
		}
		return false;
	}

	/**
	 * Shows the edit record view.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function showEditView($sender, $param)
	{
		$this->getListView()->setVisible(false);
		$this->getEditView()->setVisible(true);
		$this->_panForNewButton->setVisible(false);
		$this->_panForSearch->setVisible(false);
		$this->getEditView()->getCancelButton()->setVisible(true);
		$this->getEditView()->getClearButton()->setVisible(false);
	}

	/**
	 * Shows the view for listing the records.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function showListView($sender, $param)
	{
		$this->getListView()->setVisible(true);
		$this->getEditView()->setVisible(false);
		$this->_panForNewButton->setVisible(true);
		$this->_panForSearch->setVisible(true);
	}

	/**
	 * Shows the add record view.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	protected function showAddView($sender, $param)
	{
		$this->getEditView()->setRecordPk(null);
		$this->getEditView()->initializeEditForm();
		$this->showEditView($sender, $param);
	}
}
