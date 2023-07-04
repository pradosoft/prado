<?php
/**
 * TActiveTableCell class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Includes the following used classes
 */
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Web\UI\WebControls\TTableCell;
use Prado\Web\UI\WebControls\TTableRow;

/**
 * TActiveTableCell class.
 *
 * TActiveTableCell is the active counterpart to the original {@see \Prado\Web\UI\WebControls\TTableCell} control
 * and displays a table cell. The horizontal and vertical alignments of the cell
 * are specified via {@see setHorizontalAlign HorizontalAlign} and
 * {@see setVerticalAlign VerticalAlign} properties, respectively.
 *
 * TActiveTableCell allows the contents of the table cell to be changed during callback. When
 * {@see onCellSelected CellSelected} property is set, selecting (clicking on) the cell will
 * perform a callback request causing {@see onCellSelected OnCellSelected} event to be fired.
 *
 * It will also bubble the {@see onCellSelected OnCellSelected} event up to it's parent
 * {@see \Prado\Web\UI\ActiveControls\TActiveTableRow} control which will fire up the event handlers if implemented.
 *
 * TActiveTableCell allows the client-side cell contents to be updated during a
 * callback response by getting a new writer, invoking the render method and flushing the
 * output, similar to a {@see \Prado\Web\UI\ActiveControls\TActivePanel} control.
 * ```php
 * function callback_request($sender, $param)
 * {
 *     $this->active_cell->render($param->getNewWriter());
 * }
 * ```
 *
 * Please refer to the original documentation of the regular counterpart for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 3.1.9
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveTableCell extends TTableCell implements IActiveControl, ICallbackEventHandler
{
	/**
	 * @var \Prado\Web\UI\WebControls\TTableRow parent row control containing the cell
	 */
	private $_row;

	/**
	 * Creates a new callback control, sets the adapter to TActiveControlAdapter.
	 */
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
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * @return string corresponding javascript class name for this TActiveTableCell.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TActiveTableCell';
	}

	/**
	 * Raises the callback event. This method is required by {@see \Prado\Web\UI\ActiveControls\ICallbackEventHandler}
	 * interface. It will raise {@see onCellSelected OnCellSelected} event with a
	 * {@see \Prado\Web\UI\ActiveControls\TActiveTableCellEventParameter} containing the zero-based index of the
	 * TActiveTableCell.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$parameter = new TActiveTableCellEventParameter($this->getResponse(), $param->getCallbackParameter(), $this->getCellIndex());
		$this->onCellSelected($parameter);
		$this->raiseBubbleEvent($this, $parameter);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCellSelected' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TActiveTableCellEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCellSelected($param)
	{
		$this->raiseEvent('OnCellSelected', $this, $param);
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control if the event handler for the
	 * {@see onCellSelected OnCellSelected} event is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer responsible for rendering
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		if ($this->hasEventHandler('OnCellSelected')) {
			$this->getActiveControl()->registerCallbackClientScript($this->getClientClassName(), $this->getPostBackOptions());
		}
	}

	/**
	 * Renders and replaces the cell's content on the client-side. When render() is
	 * called before the OnPreRender event, such as when render() is called during
	 * a callback event handler, the rendering is defered until OnPreRender event
	 * is raised.
	 * @param \Prado\Web\UI\THtmlWriter $writer html writer
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
			// If we update a TActiveTableCell on callback, we shouldn't update all childs,
			// because the whole content will be replaced by the parent.
			if ($this->getHasControls()) {
				foreach ($this->findControlsByType(\Prado\Web\UI\ActiveControls\IActiveControl::class, false) as $control) {
					$control->getActiveControl()->setEnableUpdate(false);
				}
			}
		}
	}

	/**
	 * Returns postback specifications for the table cell.
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
	 * Returns the zero-based index of the TActiveTableCell within the {@see \Prado\Web\UI\WebControls\TTableCellCollection}
	 * of the parent {@see \Prado\Web\UI\WebControls\TTableRow} control. Raises a {@see \Prado\Exceptions\TConfigurationException} if the cell
	 * is no member of the cell collection.
	 * @return int the zero-based index of the cell
	 */
	public function getCellIndex()
	{
		foreach ($this->getRow()->getCells() as $key => $row) {
			if ($row == $this) {
				return $key;
			}
		}
		throw new TConfigurationException('tactivetablecell_control_notincollection', $this::class, $this->getUniqueID());
	}

	/**
	 * Returns the parent {@see \Prado\Web\UI\WebControls\TTableRow} control by looping through all parents until a {@see \Prado\Web\UI\WebControls\TTableRow}
	 * is found. Raises a {@see \Prado\Exceptions\TConfigurationException} if no row control is found.
	 * @return TTableRow the parent row control
	 */
	public function getRow()
	{
		if ($this->_row === null) {
			$row = $this->getParent();
			while (!($row instanceof TTableRow) && $row !== null) {
				$row = $row->getParent();
			}
			if ($row instanceof TTableRow) {
				$this->_row = $row;
			} else {
				throw new TConfigurationException('tactivetablecell_control_outoftable', $this::class, $this->getUniqueID());
			}
		}
		return $this->_row;
	}
}
