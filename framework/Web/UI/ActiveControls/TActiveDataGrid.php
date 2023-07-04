<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Includes the following used classes
 */
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\ISurroundable;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TDataGridPagerButtonType;
use Prado\Web\UI\WebControls\TLabel;

/**
 * TActiveDataGrid class
 *
 * TActiveDataGrid represents a data bound and updatable grid control which is the
 * active counterpart to the original {@see \Prado\Web\UI\WebControls\TDataGrid} control.
 *
 * This component can be used in the same way as the regular datagrid, the only
 * difference is that the active datagrid uses callbacks instead of postbacks
 * for interaction.
 *
 * There are also active datagrid columns to work with the TActiveDataGrid, which are
 * - {@see \Prado\Web\UI\ActiveControls\TActiveBoundColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TBoundColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveLiteralColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TLiteralColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveCheckBoxColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TCheckBoxColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveDropDownListColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TDropDownListColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveHyperLinkColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\THyperLinkColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveEditCommandColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TEditCommandColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveButtonColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TButtonColumn}.
 * - {@see \Prado\Web\UI\ActiveControls\TActiveTemplateColumn}, the active counterpart to {@see \Prado\Web\UI\WebControls\TTemplateColumn}.
 *
 * Please refer to the original documentation of the regular counterparts for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 3.1.9
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveDataGrid extends TDataGrid implements IActiveControl, ISurroundable
{
	/**
	 * @var string the tag used to render the surrounding container
	 */
	protected $_surroundingTag = 'div';

	/**
	 * @return string Name of the class used in AutoGenerateColumns mode
	 */
	protected function getAutoGenerateColumnName()
	{
		return 'TActiveBoundColumn';
	}

	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
	}

	/**
	 * @return TBaseActiveCallbackControl standard active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * Sets the data source object associated with the datagrid control.
	 * In addition, the render method of all connected pagers is called so they
	 * get updated when the data source is changed. Also the datagrid registers
	 * itself for rendering in order to get it's content replaced on client side.
	 * @param array|string|\Traversable $value data source object
	 */
	public function setDataSource($value)
	{
		parent::setDataSource($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->renderPager();
			$this->getPage()->getAdapter()->registerControlToRender($this, $this->getResponse()->createHtmlWriter());
		}
	}

	/**
	 * Gets the tag used to render the surrounding container. Defaults to 'div'.
	 * @return string container tag
	 */
	public function getSurroundingTag()
	{
		return $this->_surroundingTag;
	}

	/**
	 * Sets the tag used to render the surrounding container.
	 * @param string $value container tag
	 */
	public function setSurroundingTag($value)
	{
		$this->_surroundingTag = TPropertyValue::ensureString($value);
	}

	/**
	 * Returns the id of the surrounding container.
	 * @return string container id
	 */
	public function getSurroundingTagID()
	{
		return $this->getClientID() . '_Container';
	}

	/**
	 * Creates a pager button.
	 * Depending on the button type, a TActiveLinkButton or a TActiveButton may be created.
	 * If it is enabled (clickable), its command name and parameter will also be set.
	 * It overrides the datagrid's original method to create active controls instead, thus
	 * the pager will do callbacks instead of the regular postbacks.
	 * @param mixed $pager the container pager instance of TActiveDatagridPager
	 * @param string $buttonType button type, either LinkButton or PushButton
	 * @param bool $enabled whether the button should be enabled
	 * @param string $text caption of the button
	 * @param string $commandName CommandName corresponding to the OnCommand event of the button
	 * @param string $commandParameter CommandParameter corresponding to the OnCommand event of the button
	 * @return mixed the button instance
	 */
	protected function createPagerButton($pager, $buttonType, $enabled, $text, $commandName, $commandParameter)
	{
		if ($buttonType === TDataGridPagerButtonType::LinkButton) {
			if ($enabled) {
				$button = new TActiveLinkButton();
			} else {
				$button = new TLabel();
				$button->setText($text);
				return $button;
			}
		} else {
			$button = new TActiveButton();
			if (!$enabled) {
				$button->setEnabled(false);
			}
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		$button->getActiveControl()->setClientSide(
			$pager->getClientSide()
		);
		return $button;
	}

	protected function createPager()
	{
		$pager = new TActiveDataGridPager($this);
		$this->buildPager($pager);
		$this->onPagerCreated(new TActiveDataGridPagerEventParameter($pager));
		$this->getControls()->add($pager);
		return $pager;
	}

	/**
	 * Renders the datagrid.
	 * If the datagrid did not pass the prerender phase yet, it will register itself for rendering later.
	 * Else it will call the {@see renderDataGrid()} method which will do the rendering of the datagrid.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getHasPreRendered()) {
			$this->renderDataGrid($writer);
			if ($this->getActiveControl()->canUpdateClientSide()) {
				$this->getPage()->getCallbackClient()->replaceContent($this->getSurroundingTagId(), $writer);
			}
		} else {
			$this->getPage()->getAdapter()->registerControlToRender($this, $writer);
		}
	}

	/**
	 * Loops through all {@see \Prado\Web\UI\ActiveControls\TActivePager} on the page and registers the ones which are set to paginate
	 * the datagrid for rendering. This is to ensure that the connected pagers are also rendered if the
	 * data source changed.
	 */
	private function renderPager()
	{
		$pager = $this->getPage()->findControlsByType(\Prado\Web\UI\ActiveControls\TActivePager::class, false);
		foreach ($pager as $item) {
			if ($item->ControlToPaginate == $this->getID()) {
				$writer = $this->getResponse()->createHtmlWriter();
				$this->getPage()->getAdapter()->registerControlToRender($item, $writer);
			}
		}
	}

	/**
	 * Renders the datagrid by writing a {@see getSurroundingTag()} with the container id obtained
	 * from {@see getSurroundingTagId()} which will be called by the replacement method of the client
	 * script to update it's content.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for the rendering purpose
	 */
	private function renderDataGrid($writer)
	{
		$writer->addAttribute('id', $this->getSurroundingTagID());
		$writer->renderBeginTag($this->getSurroundingTag());
		parent::render($writer);
		$writer->renderEndTag();
	}
}
