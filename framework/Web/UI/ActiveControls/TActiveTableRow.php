<?php
/**
 * TActiveTableRow and TActiveTableRowEventParameter class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Includes the following used classes
 */
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Web\UI\WebControls\TTable;
use Prado\Web\UI\WebControls\TTableRow;

/**
 * TActiveTableRow class.
 *
 * TActiveTableRow is the active counterpart to the original {@link TTableRow} control
 * and displays a table row. The table cells in the row can be accessed
 * via {@link getCells Cells}. The horizontal and vertical alignments of the row
 * are specified via {@link setHorizontalAlign HorizontalAlign} and
 * {@link setVerticalAlign VerticalAlign} properties, respectively.
 *
 * TActiveTableRow allows the contents of the table row to be changed during callback. When
 * {@link onRowSelected RowSelected} property is set, selecting (clicking on) the row will
 * perform a callback request causing {@link onRowSelected OnRowSelected} event to be fired.
 *
 * It will also respond to a bubbled {@link onCellSelected OnCellSelected} event of a
 * {@link TActiveTableCell} child control and fire a {@link onRowSelected OnRowSelected} event.
 *
 * TActiveTableRow allows the client-side row contents to be updated during a
 * callback response by getting a new writer, invoking the render method and flushing the
 * output, similar to a {@link TActivePanel} control.
 * <code>
 * function callback_request($sender, $param)
 * {
 *     $this->active_row->render($param->getNewWriter());
 * }
 * </code>
 *
 * Please refer to the original documentation of the regular counterpart for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
 */
class TActiveTableRow extends TTableRow implements ICallbackEventHandler, IActiveControl
{

	/**
	 * @var TTable parent table control containing the row
	 */
	private $_table;

	/**
	 * Creates a new callback control, sets the adapter to TActiveControlAdapter.
	 * */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveCallbackControl standard callback control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getAdapter()->getBaseActiveControl()->getClientSide();
	}

	/**
	 * @return string corresponding javascript class name for this TActiveTableRow.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveTableRow';
	}

	/**
	 * Raises the callback event. This method is required by {@link ICallbackEventHandler}
	 * interface. It will raise {@link onRowSelected OnRowSelected} event with a
	 * {@link TActiveTableRowEventParameter} containing the zero-based index of the
	 * TActiveTableRow.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$parameter = new TActiveTableRowEventParameter($this->getResponse(), $param->getCallbackParameter(), $this->getRowIndex());
		$this->onRowSelected($parameter);
	}

	/**
	 * This method overrides parent's implementation and raises the control's
	 * callback event. This will fire the {@link onRowSelected OnRowSelected}
	 * event if an appropriate event handler is implemented.
	 * @param TControl $sender the sender of the event
	 * @param TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof TActiveTableCellEventParameter) {
			$this->raiseCallbackEvent($param);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnRowSelected' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TActiveTableRowEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onRowSelected($param)
	{
		$this->raiseEvent('OnRowSelected', $this, $param);
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control if the event handler for the
	 * {@link onRowSelected OnRowSelected} event is set.
	 * @param THtmlWriter $writer the writer responsible for rendering
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		if ($this->hasEventHandler('OnRowSelected')) {
			$this->getActiveControl()->registerCallbackClientScript($this->getClientClassName(), $this->getPostBackOptions());
		}
	}

	/**
	 * Renders and replaces the row's content on the client-side. When render() is
	 * called before the OnPreRender event, such as when render() is called during
	 * a callback event handler, the rendering is defered until OnPreRender event
	 * is raised.
	 * @param THtmlWriter $writer html writer
	 */
	public function render($writer)
	{
		if ($this->getHasPreRendered()) {
			parent::render($writer);
			if ($this->getActiveControl()->canUpdateClientSide()) {
				$this->getPage()->getCallbackClient()->replaceContent($this, $writer);
			}
		} else {
			$this->getPage()->getAdapter()->registerControlToRender($this, $writer);
			// If we update a TActiveTableRow on callback, we shouldn't update all childs,
			// because the whole content will be replaced by the parent.
			if ($this->getHasControls()) {
				foreach ($this->findControlsByType('Prado\Web\UI\ActiveControls\IActiveControl', false) as $control) {
					$control->getActiveControl()->setEnableUpdate(false);
				}
			}
		}
	}

	/**
	 * Returns postback specifications for the table row.
	 * This method is used by framework and control developers.
	 * @return array parameters about how the row defines its postback behavior.
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Returns the zero-based index of the TActiveTableRow within the {@link TTableRowCollection}
	 * of the parent {@link TTable} control. Raises a {@link TConfigurationException} if the row
	 * is no member of the row collection.
	 * @return int the zero-based index of the row
	 */
	public function getRowIndex()
	{
		foreach ($this->getTable()->getRows() as $key => $row) {
			if ($row == $this) {
				return $key;
			}
		}
		throw new TConfigurationException('tactivetablerow_control_notincollection', get_class($this), $this->getUniqueID());
	}

	/**
	 * Returns the parent {@link TTable} control by looping through all parents until a {@link TTable}
	 * is found. Raises a {@link TConfigurationException} if no table control is found.
	 * @return TTable the parent table control
	 */
	public function getTable()
	{
		if ($this->_table === null) {
			$table = $this->getParent();
			while (!($table instanceof TTable) && $table !== null) {
				$table = $table->getParent();
			}
			if ($table instanceof TTable) {
				$this->_table = $table;
			} else {
				throw new TConfigurationException('tactivetablerow_control_outoftable', get_class($this), $this->getUniqueID());
			}
		}
		return $this->_table;
	}
}
