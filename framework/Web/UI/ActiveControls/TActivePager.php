<?php
/**
 * TActivePager class file.
 *
 * @author "gevik" (forum contributor) and Christophe Boulain (Christophe.Boulain@gmail.com)
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

/**
 * Load active control adapter.
 */
use Prado\Web\UI\WebControls\TDisplayStyle;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TPager;
use Prado\Web\UI\WebControls\TPagerButtonType;
use Prado\Web\UI\WebControls\TPagerMode;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * TActivePager is the active control counter part of TPager.
 *
 * When a page change is requested, TActivePager raises a callback instead of the
 * traditional postback.
 *
 * The {@link onCallback OnCallback} event is raised during a callback request
 * and it is raise <b>after</b> the {@link onPageIndexChanged OnPageIndexChanged} event.
 *
 * @author "gevik" (forum contributor) and Christophe Boulain (Christophe.Boulain@gmail.com)
 * @since 3.1.2
 * @method TActiveControlAdapter getAdapter()
 */
class TActivePager extends TPager implements IActiveControl, ICallbackEventHandler
{
	/**
	 * Creates a new callback control, sets the adapter to
	 * TActiveControlAdapter. If you override this class, be sure to set the
	 * adapter appropriately by, for example, by calling this constructor.
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
	 * @return TCallbackClientSide client side request options.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * Raises the callback event. This method is required by {@link
	 * ICallbackEventHandler} interface.
	 * This method is mainly used by framework and control developers.
	 * @param TCallbackEventParameter $param the event parameter
	 */
	public function raiseCallbackEvent($param)
	{
		$this->onCallback($param);
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Builds a dropdown list pager
	 * Override parent implementation to build Active dropdown lists.
	 */
	protected function buildListPager()
	{
		$list = new TActiveDropDownList();

		$list->getActiveControl()->setClientSide(
			$this->getClientSide()
		);

		$this->getControls()->add($list);
		$list->setDataSource(range(1, $this->getPageCount()));
		$list->dataBind();
		$list->setSelectedIndex($this->getCurrentPageIndex());
		$list->setAutoPostBack(true);
		$list->attachEventHandler('OnSelectedIndexChanged', [$this, 'listIndexChanged']);
		$list->attachEventHandler('OnCallback', [$this, 'handleCallback']);
	}

	/**
	 * Creates a pager button.
	 * Override parent implementation to create, depending on the button type, a TActiveLinkButton,
	 * a TActiveButton or a TActiveImageButton may be created.
	 *
	 * @param string $buttonType button type, either LinkButton or PushButton
	 * @param bool $enabled whether the button should be enabled
	 * @param string $text caption of the button
	 * @param string $commandName CommandName corresponding to the OnCommand event of the button
	 * @param string $commandParameter CommandParameter corresponding to the OnCommand event of the button
	 * @return mixed the button instance
	 */
	protected function createPagerButton($buttonType, $enabled, $text, $commandName, $commandParameter)
	{
		if ($buttonType === TPagerButtonType::LinkButton) {
			if ($enabled) {
				$button = new TActiveLinkButton();
			} else {
				$button = new TLabel();
				$button->setText($text);
				$button->setCssClass($this->getButtonCssClass());
				return $button;
			}
		} elseif ($buttonType === TPagerButtonType::ImageButton) {
			$button = new TActiveImageButton();
			$button->setImageUrl($this->getPageImageUrl($text, $commandName));
			if ($enabled) {
				$button->setVisible(true);
			} else {
				$button->setVisible(false);
			}
		} else {
			$button = new TActiveButton();
			if (!$enabled) {
				$button->setEnabled(false);
			}
		}

		if ($buttonType === TPagerButtonType::ImageButton) {
			$button->setImageUrl($text);
		}

		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCommandParameter($commandParameter);
		$button->setCausesValidation(false);
		$button->setCssClass($this->getButtonCssClass());

		$button->attachEventHandler('OnCallback', [$this, 'handleCallback']);
		$button->getActiveControl()->setClientSide(
			$this->getClientSide()
		);

		return $button;
	}

	/**
	 * Event handler to the OnCallback active buttons or active dropdownlist.
	 * This handler will raise the {@link onCallback OnCallback} event
	 *
	 * @param mixed $sender
	 * @param TCallbackEventParameter $param
	 */
	public function handleCallback($sender, $param)
	{
		// Update all the buttons pagers attached to the same control.
		// Dropdown pagers doesn't need to be re-rendered.
		$controlToPaginate = $this->getControlToPaginate();
		foreach ($this->getNamingContainer()->findControlsByType(TActivePager::class, false) as $control) {
			if ($control->getMode() !== TPagerMode::DropDownList && $control->getControlToPaginate() === $controlToPaginate) {
				$control->render($param->getNewWriter());
				// FIXME : With some very fast machine, the getNewWriter() consecutive calls are in the same microsecond, resulting
				// of getting the same boundaries in ajax response. Wait 1 microsecond to avoid this.
				usleep(1);
			}
		}
		// Raise callback event
		$this->onCallback($param);
	}

	public function render($writer)
	{
		if ($this->getHasPreRendered()) {
			$this->setDisplay(($this->getPageCount() == 1) ? TDisplayStyle::None : TDisplayStyle::Dynamic);
			TWebControl::render($writer);
			if ($this->getActiveControl()->canUpdateClientSide()) {
				$this->getPage()->getCallbackClient()->replaceContent($this, $writer);
			}
		} else {
			$this->getPage()->getAdapter()->registerControlToRender($this, $writer);
		}
	}
}
