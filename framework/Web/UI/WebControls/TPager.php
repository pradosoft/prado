<?php
/**
 * TPager class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TPager class.
 *
 * TPager creates a pager that provides UI for end-users to interactively
 * specify which page of data to be rendered in a {@link TDataBoundControl}-derived control,
 * such as {@link TDataList}, {@link TRepeater}, {@link TCheckBoxList}, etc.
 * The target data-bound control is specified by {@link setControlToPaginate ControlToPaginate},
 * which must be the ID path of the target control reaching from the pager's
 * naming container. Note, the target control must have its {@link TDataBoundControl::setAllowPaging AllowPaging}
 * set to true.
 *
 * TPager can display three different UIs, specified via {@link setMode Mode}:
 * - NextPrev: a next page and a previous page button are rendered.
 * - Numeric: a list of page index buttons are rendered.
 * - List: a dropdown list of page indices are rendered.
 *
 * When the pager mode is either NextPrev or Numeric, the paging buttons may be displayed
 * in three types by setting {@link setButtonType ButtonType}:
 * - LinkButton: a hyperlink button
 * - PushButton: a normal button
 * - ImageButton: an image button (please set XXXPageImageUrl properties accordingly to specify the button images.)
 *
 * Since Prado 3.2.1, you can use the {@link setButtonCssClass ButtonCssClass} property to specify a css class
 * that will be applied to each button created by the pager in NextPrev or Numeric mode.
 *
 * TPager raises an {@link onPageIndexChanged OnPageIndexChanged} event when
 * the end-user interacts with it and specifies a new page (e.g. clicking
 * on a page button that leads to a new page.) The new page index may be obtained
 * from the event parameter's property {@link TPagerPageChangedEventParameter::getNewPageIndex NewPageIndex}.
 * Normally, in the event handler, one can set the {@link TDataBoundControl::getCurrentPageIndex CurrentPageIndex}
 * to this new page index so that the new page of data is rendered.
 *
 * Multiple pagers can be associated with the same data-bound control.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0.2
 */
class TPager extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\INamingContainer
{
	/**
	 * Command name that TPager understands.
	 */
	public const CMD_PAGE = 'Page';
	public const CMD_PAGE_NEXT = 'Next';
	public const CMD_PAGE_PREV = 'Previous';
	public const CMD_PAGE_FIRST = 'First';
	public const CMD_PAGE_LAST = 'Last';

	private $_pageCount = 0;

	/**
	 * Restores the pager state.
	 * This method overrides the parent implementation and is invoked when
	 * the control is loading persistent state.
	 */
	public function loadState()
	{
		parent::loadState();
		if ($this->getEnableViewState(true)) {
			$this->getControls()->clear();
			$this->buildPager();
		}
	}

	/**
	 * @return string the ID path of the control whose content would be paginated.
	 */
	public function getControlToPaginate()
	{
		return $this->getViewState('ControlToPaginate', '');
	}

	/**
	 * Sets the ID path of the control whose content would be paginated.
	 * The ID path is the dot-connected IDs of the controls reaching from
	 * the pager's naming container to the target control.
	 * @param string $value the ID path
	 */
	public function setControlToPaginate($value)
	{
		$this->setViewState('ControlToPaginate', $value, '');
	}

	/**
	 * @return string the css class of the buttons.
	 * @since 3.2.1
	 */
	public function getButtonCssClass()
	{
		return $this->getViewState('ButtonCssClass', '');
	}

	/**
	 * @param string $value Sets the css class of the buttons that will be rendered by this pager to $value.
	 * @since 3.2.1
	 */
	public function setButtonCssClass($value)
	{
		$this->setViewState('ButtonCssClass', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return TPagerMode pager mode. Defaults to TPagerMode::NextPrev.
	 */
	public function getMode()
	{
		return $this->getViewState('Mode', TPagerMode::NextPrev);
	}

	/**
	 * @param TPagerMode $value pager mode.
	 */
	public function setMode($value)
	{
		$this->setViewState('Mode', TPropertyValue::ensureEnum($value, TPagerMode::class), TPagerMode::NextPrev);
	}

	/**
	 * @return TPagerButtonType the type of command button for paging. Defaults to TPagerButtonType::LinkButton.
	 */
	public function getButtonType()
	{
		return $this->getViewState('ButtonType', TPagerButtonType::LinkButton);
	}

	/**
	 * @param TPagerButtonType $value the type of command button for paging.
	 */
	public function setButtonType($value)
	{
		$this->setViewState('ButtonType', TPropertyValue::ensureEnum($value, TPagerButtonType::class), TPagerButtonType::LinkButton);
	}

	/**
	 * @return string text for the next page button. Defaults to '>'.
	 */
	public function getNextPageText()
	{
		return $this->getViewState('NextPageText', '>');
	}

	/**
	 * @param string $value text for the next page button.
	 */
	public function setNextPageText($value)
	{
		$this->setViewState('NextPageText', $value, '>');
	}

	/**
	 * @return string text for the previous page button. Defaults to '<'.
	 */
	public function getPrevPageText()
	{
		return $this->getViewState('PrevPageText', '<');
	}

	/**
	 * @param string $value text for the next page button.
	 */
	public function setPrevPageText($value)
	{
		$this->setViewState('PrevPageText', $value, '<');
	}

	/**
	 * @return string text for the first page button. Defaults to '<<'.
	 */
	public function getFirstPageText()
	{
		return $this->getViewState('FirstPageText', '<<');
	}

	/**
	 * @param string $value text for the first page button. If empty, the first page button will not be rendered.
	 */
	public function setFirstPageText($value)
	{
		$this->setViewState('FirstPageText', $value, '<<');
	}

	/**
	 * @return string text for the last page button. Defaults to '>>'.
	 */
	public function getLastPageText()
	{
		return $this->getViewState('LastPageText', '>>');
	}

	/**
	 * @param string $value text for the last page button. If empty, the last page button will not be rendered.
	 */
	public function setLastPageText($value)
	{
		$this->setViewState('LastPageText', $value, '>>');
	}

	/**
	 * @return string the image URL for the first page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function getFirstPageImageUrl()
	{
		return $this->getViewState('FirstPageImageUrl', '');
	}

	/**
	 * @param string $value the image URL for the first page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function setFirstPageImageUrl($value)
	{
		$this->setViewState('FirstPageImageUrl', $value);
	}

	/**
	 * @return string the image URL for the last page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function getLastPageImageUrl()
	{
		return $this->getViewState('LastPageImageUrl', '');
	}

	/**
	 * @param string $value the image URL for the last page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function setLastPageImageUrl($value)
	{
		$this->setViewState('LastPageImageUrl', $value);
	}

	/**
	 * @return string the image URL for the next page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function getNextPageImageUrl()
	{
		return $this->getViewState('NextPageImageUrl', '');
	}

	/**
	 * @param string $value the image URL for the next page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function setNextPageImageUrl($value)
	{
		$this->setViewState('NextPageImageUrl', $value);
	}

	/**
	 * @return string the image URL for the previous page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function getPrevPageImageUrl()
	{
		return $this->getViewState('PrevPageImageUrl', '');
	}

	/**
	 * @param string $value the image URL for the previous page button. This is only used when {@link getButtonType ButtonType} is 'ImageButton'.
	 * @since 3.1.1
	 */
	public function setPrevPageImageUrl($value)
	{
		$this->setViewState('PrevPageImageUrl', $value);
	}

	/**
	 * @return string the image URL for the numeric page buttons. This is only used when {@link getButtonType ButtonType} is 'ImageButton' and {@link getMode Mode} is 'Numeric'.
	 * @see setNumericPageImageUrl
	 * @since 3.1.1
	 */
	public function getNumericPageImageUrl()
	{
		return $this->getViewState('NumericPageImageUrl', '');
	}

	/**
	 * Sets the image URL for the numeric page buttons.
	 * This is actually a template for generating a set of URLs corresponding to numeric button 1, 2, 3, .., etc.
	 * Use {0} as the placeholder for the numbers.
	 * For example, the image URL http://example.com/images/button{0}.gif
	 * will be replaced as http://example.com/images/button1.gif, http://example.com/images/button2.gif, etc.
	 * @param string $value the image URL for the numeric page buttons. This is only used when {@link getButtonType ButtonType} is 'ImageButton' and {@link getMode Mode} is 'Numeric'.
	 * @since 3.1.1
	 */
	public function setNumericPageImageUrl($value)
	{
		$this->setViewState('NumericPageImageUrl', $value);
	}

	/**
	 * @return int maximum number of pager buttons to be displayed. Defaults to 10.
	 */
	public function getPageButtonCount()
	{
		return $this->getViewState('PageButtonCount', 10);
	}

	/**
	 * @param int $value maximum number of pager buttons to be displayed
	 * @throws TInvalidDataValueException if the value is less than 1.
	 */
	public function setPageButtonCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 1) {
			throw new TInvalidDataValueException('pager_pagebuttoncount_invalid');
		}
		$this->setViewState('PageButtonCount', $value, 10);
	}

	/**
	 * @return int the zero-based index of the current page. Defaults to 0.
	 */
	public function getCurrentPageIndex()
	{
		return $this->getViewState('CurrentPageIndex', 0);
	}

	/**
	 * @param int $value the zero-based index of the current page
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	protected function setCurrentPageIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('pager_currentpageindex_invalid');
		}
		$this->setViewState('CurrentPageIndex', $value, 0);
	}

	/**
	 * @return int number of pages of data items available
	 */
	public function getPageCount()
	{
		return $this->getViewState('PageCount', 0);
	}

	/**
	 * @param int $value number of pages of data items available
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	protected function setPageCount($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			throw new TInvalidDataValueException('pager_pagecount_invalid');
		}
		$this->setViewState('PageCount', $value, 0);
	}

	/**
	 * @return bool whether the current page is the first page Defaults to false.
	 */
	public function getIsFirstPage()
	{
		return $this->getCurrentPageIndex() === 0;
	}

	/**
	 * @return bool whether the current page is the last page
	 */
	public function getIsLastPage()
	{
		return $this->getCurrentPageIndex() === $this->getPageCount() - 1;
	}

	/**
	 * Performs databinding to populate data items from data source.
	 * This method is invoked by {@link dataBind()}.
	 * You may override this function to provide your own way of data population.
	 * @param \Traversable $param the bound data
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);

		$controlID = $this->getControlToPaginate();
		if (($targetControl = $this->getNamingContainer()->findControl($controlID)) === null || !($targetControl instanceof TDataBoundControl)) {
			throw new TConfigurationException('pager_controltopaginate_invalid', $controlID);
		}

		if ($targetControl->getAllowPaging()) {
			$this->_pageCount = $targetControl->getPageCount();
			$this->getControls()->clear();
			$this->setPageCount($targetControl->getPageCount());
			$this->setCurrentPageIndex($targetControl->getCurrentPageIndex());
			$this->buildPager();
		} else {
			$this->_pageCount = 0;
		}
	}

	/**
	 * Renders the control.
	 * The method overrides the parent implementation by rendering
	 * the pager only when there are two or more pages.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer
	 */
	public function render($writer)
	{
		if ($this->_pageCount > 1) {
			parent::render($writer);
		}
	}

	/**
	 * Builds the pager content based on the pager mode.
	 * Current implementation includes building 'NextPrev', 'Numeric' and 'DropDownList' pagers.
	 * Derived classes may override this method to provide additional pagers.
	 */
	protected function buildPager()
	{
		switch ($this->getMode()) {
			case TPagerMode::NextPrev:
				$this->buildNextPrevPager();
				break;
			case TPagerMode::Numeric:
				$this->buildNumericPager();
				break;
			case TPagerMode::DropDownList:
				$this->buildListPager();
				break;
		}
	}

	/**
	 * Creates a pager button.
	 * Depending on the button type, a TLinkButton or a TButton may be created.
	 * If it is enabled (clickable), its command name and parameter will also be set.
	 * Derived classes may override this method to create additional types of buttons, such as TImageButton.
	 * @param string $buttonType button type, either LinkButton or PushButton
	 * @param bool $enabled whether the button should be enabled
	 * @param string $text caption of the button.
	 * @param string $commandName CommandName corresponding to the OnCommand event of the button.
	 * @param string $commandParameter CommandParameter corresponding to the OnCommand event of the button
	 * @return mixed the button instance
	 */
	protected function createPagerButton($buttonType, $enabled, $text, $commandName, $commandParameter)
	{
		if ($buttonType === TPagerButtonType::LinkButton) {
			if ($enabled) {
				$button = new TLinkButton();
			} else {
				$button = new TLabel();
				$button->setText($text);
				$button->setCssClass($this->getButtonCssClass());
				return $button;
			}
		} else {
			if ($buttonType === TPagerButtonType::ImageButton) {
				$button = new TImageButton();
				$button->setImageUrl($this->getPageImageUrl($text, $commandName));
			} else {
				$button = new TButton();
			}
			if (!$enabled) {
				$button->setEnabled(false);
			}
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		$button->setCssClass($this->getButtonCssClass());
		return $button;
	}

	/**
	 * @param string $text the caption of the image button
	 * @param string $commandName the command name associated with the image button
	 * @since 3.1.1
	 */
	protected function getPageImageUrl($text, $commandName)
	{
		switch ($commandName) {
			case self::CMD_PAGE:
				$url = $this->getNumericPageImageUrl();
				return str_replace('{0}', $text, $url);
			case self::CMD_PAGE_NEXT:
				return $this->getNextPageImageUrl();
			case self::CMD_PAGE_PREV:
				return $this->getPrevPageImageUrl();
			case self::CMD_PAGE_FIRST:
				return $this->getFirstPageImageUrl();
			case self::CMD_PAGE_LAST:
				return $this->getLastPageImageUrl();
			default:
				return '';
		}
	}

	/**
	 * Builds a next-prev pager
	 */
	protected function buildNextPrevPager()
	{
		$buttonType = $this->getButtonType();
		$controls = $this->getControls();
		if ($this->getIsFirstPage()) {
			if (($text = $this->getFirstPageText()) !== '') {
				$label = $this->createPagerButton($buttonType, false, $text, self::CMD_PAGE_FIRST, '');
				$controls->add($label);
				$controls->add("\n");
			}
			$label = $this->createPagerButton($buttonType, false, $this->getPrevPageText(), self::CMD_PAGE_PREV, '');
			$controls->add($label);
		} else {
			if (($text = $this->getFirstPageText()) !== '') {
				$button = $this->createPagerButton($buttonType, true, $text, self::CMD_PAGE_FIRST, '');
				$controls->add($button);
				$controls->add("\n");
			}
			$button = $this->createPagerButton($buttonType, true, $this->getPrevPageText(), self::CMD_PAGE_PREV, '');
			$controls->add($button);
		}
		$controls->add("\n");
		if ($this->getIsLastPage()) {
			$label = $this->createPagerButton($buttonType, false, $this->getNextPageText(), self::CMD_PAGE_NEXT, '');
			$controls->add($label);
			if (($text = $this->getLastPageText()) !== '') {
				$controls->add("\n");
				$label = $this->createPagerButton($buttonType, false, $text, self::CMD_PAGE_LAST, '');
				$controls->add($label);
			}
		} else {
			$button = $this->createPagerButton($buttonType, true, $this->getNextPageText(), self::CMD_PAGE_NEXT, '');
			$controls->add($button);
			if (($text = $this->getLastPageText()) !== '') {
				$controls->add("\n");
				$button = $this->createPagerButton($buttonType, true, $text, self::CMD_PAGE_LAST, '');
				$controls->add($button);
			}
		}
	}

	/**
	 * Builds a numeric pager
	 */
	protected function buildNumericPager()
	{
		$buttonType = $this->getButtonType();
		$controls = $this->getControls();
		$pageCount = $this->getPageCount();
		$pageIndex = $this->getCurrentPageIndex() + 1;
		$maxButtonCount = $this->getPageButtonCount();
		$buttonCount = $maxButtonCount > $pageCount ? $pageCount : $maxButtonCount;
		$startPageIndex = 1;
		$endPageIndex = $buttonCount;
		if ($pageIndex > $endPageIndex) {
			$startPageIndex = ((int) (($pageIndex - 1) / $maxButtonCount)) * $maxButtonCount + 1;
			if (($endPageIndex = $startPageIndex + $maxButtonCount - 1) > $pageCount) {
				$endPageIndex = $pageCount;
			}
			if ($endPageIndex - $startPageIndex + 1 < $maxButtonCount) {
				if (($startPageIndex = $endPageIndex - $maxButtonCount + 1) < 1) {
					$startPageIndex = 1;
				}
			}
		}

		if ($startPageIndex > 1) {
			if (($text = $this->getFirstPageText()) !== '') {
				$button = $this->createPagerButton($buttonType, true, $text, self::CMD_PAGE_FIRST, '');
				$controls->add($button);
				$controls->add("\n");
			}
			$prevPageIndex = $startPageIndex - 1;
			$button = $this->createPagerButton($buttonType, true, $this->getPrevPageText(), self::CMD_PAGE, "$prevPageIndex");
			$controls->add($button);
			$controls->add("\n");
		}

		for ($i = $startPageIndex; $i <= $endPageIndex; ++$i) {
			if ($i === $pageIndex) {
				$label = $this->createPagerButton($buttonType, false, "$i", self::CMD_PAGE, '');
				$controls->add($label);
			} else {
				$button = $this->createPagerButton($buttonType, true, "$i", self::CMD_PAGE, "$i");
				$controls->add($button);
			}
			if ($i < $endPageIndex) {
				$controls->add("\n");
			}
		}

		if ($pageCount > $endPageIndex) {
			$controls->add("\n");
			$nextPageIndex = $endPageIndex + 1;
			$button = $this->createPagerButton($buttonType, true, $this->getNextPageText(), self::CMD_PAGE, "$nextPageIndex");
			$controls->add($button);
			if (($text = $this->getLastPageText()) !== '') {
				$controls->add("\n");
				$button = $this->createPagerButton($buttonType, true, $text, self::CMD_PAGE_LAST, '');
				$controls->add($button);
			}
		}
	}

	/**
	 * Builds a dropdown list pager
	 */
	protected function buildListPager()
	{
		$list = new TDropDownList();
		$this->getControls()->add($list);
		$list->setDataSource(range(1, $this->getPageCount()));
		$list->dataBind();
		$list->setSelectedIndex($this->getCurrentPageIndex());
		$list->setAutoPostBack(true);
		$list->attachEventHandler('OnSelectedIndexChanged', [$this, 'listIndexChanged']);
	}

	/**
	 * Event handler to the OnSelectedIndexChanged event of the dropdown list.
	 * This handler will raise {@link onPageIndexChanged OnPageIndexChanged} event.
	 * @param TDropDownList $sender the dropdown list control raising the event
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function listIndexChanged($sender, $param)
	{
		$pageIndex = $sender->getSelectedIndex();
		$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, $pageIndex));
	}

	/**
	 * This event is raised when page index is changed due to a page button click.
	 * @param TPagerPageChangedEventParameter $param event parameter
	 */
	public function onPageIndexChanged($param)
	{
		$this->raiseEvent('OnPageIndexChanged', $this, $param);
	}

	/**
	 * Processes a bubbled event.
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param \Prado\Web\UI\TControl $sender the sender of the event
	 * @param \Prado\TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof \Prado\Web\UI\TCommandEventParameter) {
			$command = $param->getCommandName();
			if (strcasecmp($command, self::CMD_PAGE) === 0) {
				$pageIndex = TPropertyValue::ensureInteger($param->getCommandParameter()) - 1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, $pageIndex));
				return true;
			} elseif (strcasecmp($command, self::CMD_PAGE_NEXT) === 0) {
				$pageIndex = $this->getCurrentPageIndex() + 1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, $pageIndex));
				return true;
			} elseif (strcasecmp($command, self::CMD_PAGE_PREV) === 0) {
				$pageIndex = $this->getCurrentPageIndex() - 1;
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, $pageIndex));
				return true;
			} elseif (strcasecmp($command, self::CMD_PAGE_FIRST) === 0) {
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, 0));
				return true;
			} elseif (strcasecmp($command, self::CMD_PAGE_LAST) === 0) {
				$this->onPageIndexChanged(new TPagerPageChangedEventParameter($sender, $this->getPageCount() - 1));
				return true;
			}
			return false;
		} else {
			return false;
		}
	}
}
