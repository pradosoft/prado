<?php
/**
 * TActiveDataGrid class file
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
 * active counterpart to the original {@link TDataGrid} control.
 *
 * This component can be used in the same way as the regular datagrid, the only
 * difference is that the active datagrid uses callbacks instead of postbacks
 * for interaction.
 *
 * There are also active datagrid columns to work with the TActiveDataGrid, which are
 * - {@link TActiveBoundColumn}, the active counterpart to {@link TBoundColumn}.
 * - {@link TActiveLiteralColumn}, the active counterpart to {@link TLiteralColumn}.
 * - {@link TActiveCheckBoxColumn}, the active counterpart to {@link TCheckBoxColumn}.
 * - {@link TActiveDropDownListColumn}, the active counterpart to {@link TDropDownListColumn}.
 * - {@link TActiveHyperLinkColumn}, the active counterpart to {@link THyperLinkColumn}.
 * - {@link TActiveEditCommandColumn}, the active counterpart to {@link TEditCommandColumn}.
 * - {@link TActiveButtonColumn}, the active counterpart to {@link TButtonColumn}.
 * - {@link TActiveTemplateColumn}, the active counterpart to {@link TTemplateColumn}.
 *
 * Please refer to the original documentation of the regular counterparts for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
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
	 * @return TBaseActiveControl standard active control options.
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
	 * @param array|string|Traversable $value data source object
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
				$button = new TActiveLinkButton;
			} else {
				$button = new TLabel;
				$button->setText($text);
				return $button;
			}
		} else {
			$button = new TActiveButton;
			if (!$enabled) {
				$button->setEnabled(false);
			}
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		$button->getAdapter()->getBaseActiveControl()->setClientSide(
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
	 * Else it will call the {@link renderDataGrid()} method which will do the rendering of the datagrid.
	 * @param THtmlWriter $writer writer for the rendering purpose
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
	 * Loops through all {@link TActivePager} on the page and registers the ones which are set to paginate
	 * the datagrid for rendering. This is to ensure that the connected pagers are also rendered if the
	 * data source changed.
	 */
	private function renderPager()
	{
		$pager = $this->getPage()->findControlsByType('Prado\Web\UI\ActiveControls\TActivePager', false);
		foreach ($pager as $item) {
			if ($item->ControlToPaginate == $this->ID) {
				$writer = $this->getResponse()->createHtmlWriter();
				$this->getPage()->getAdapter()->registerControlToRender($item, $writer);
			}
		}
	}

	/**
	 * Renders the datagrid by writing a {@link getSurroundingTag()} with the container id obtained
	 * from {@link getSurroundingTagId()} which will be called by the replacement method of the client
	 * script to update it's content.
	 * @param THtmlWriter $writer writer for the rendering purpose
	 */
	private function renderDataGrid($writer)
	{
		$writer->addAttribute('id', $this->getSurroundingTagID());
		$writer->renderBeginTag($this->getSurroundingTag());
		parent::render($writer);
		$writer->renderEndTag();
	}
}
